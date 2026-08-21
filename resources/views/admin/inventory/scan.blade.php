@extends('layouts.admin')

@section('title', 'Scan Appliance')
@section('page-title', 'Scan Appliance')

@section('content')
<div class="mx-auto max-w-3xl space-y-4">
    <div class="rounded-lg bg-white p-4 shadow sm:p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Scan sticker</h2>
                <p class="mt-1 text-sm text-gray-600">
                    Point at the QR and model barcode. If the appliance ID matches that model, you go straight there; otherwise pick from model matches.
                </p>
            </div>
            <button type="button" id="scan-reset" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                <i class="fas fa-rotate-right mr-2"></i>Scan again
            </button>
        </div>

        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            <div class="rounded-md border border-gray-200 bg-gray-50 px-3 py-2">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">QR / Appliance ID</div>
                <div id="scan-qr-status" class="mt-1 text-sm font-semibold text-gray-400">Waiting…</div>
            </div>
            <div class="rounded-md border border-gray-200 bg-gray-50 px-3 py-2">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Model barcode</div>
                <div id="scan-model-status" class="mt-1 text-sm font-semibold text-gray-400">Waiting…</div>
            </div>
        </div>

        <div id="scan-reader-wrap" class="mt-4 overflow-hidden rounded-lg border border-gray-200 bg-black relative">
            <div id="scan-reader" class="w-full"></div>
            <button type="button" id="scan-refocus"
                    class="absolute bottom-3 right-3 inline-flex items-center rounded-md bg-black/70 px-3 py-2 text-xs font-semibold text-white backdrop-blur hover:bg-black/80"
                    title="Nudge autofocus">
                <i class="fas fa-crosshairs mr-2"></i>Refocus
            </button>
        </div>

        <div id="scan-zoom-wrap" class="mt-3 hidden">
            <label for="scan-zoom" class="flex items-center justify-between text-sm font-medium text-gray-700">
                <span><i class="fas fa-search-plus mr-1 text-gray-500"></i>Zoom</span>
                <span id="scan-zoom-label" class="tabular-nums text-gray-500">1.0×</span>
            </label>
            <input id="scan-zoom" type="range" min="1" max="1" step="0.1" value="1"
                   class="mt-2 w-full accent-indigo-600">
            <p class="mt-1 text-xs text-gray-500">Hardware zoom when the phone supports it — helps thin model barcodes.</p>
        </div>

        <p id="scan-camera-error" class="mt-3 hidden text-sm font-medium text-red-600"></p>
        <p id="scan-status" class="mt-3 text-sm text-gray-600">Starting camera…</p>
    </div>

    <div id="scan-results" class="hidden space-y-3">
        <div class="rounded-lg bg-white p-4 shadow sm:p-5">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h3 id="scan-results-title" class="text-base font-semibold text-gray-900">Matches</h3>
                <span id="scan-results-meta" class="text-sm text-gray-500"></span>
            </div>
            <div id="scan-results-list" class="mt-3 divide-y divide-gray-100"></div>
            <p id="scan-results-empty" class="mt-3 hidden text-sm text-gray-500">No inventory units found for that model.</p>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    #scan-reader video {
        width: 100% !important;
        border-radius: 0.5rem;
        object-fit: cover;
        max-height: 70vh;
    }

    #scan-reader img {
        display: none !important;
    }

    #scan-reader__dashboard_section,
    #scan-reader__dashboard_section_csr,
    #scan-reader__header_message,
    #scan-reader__scan_region_arrow {
        display: none !important;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>
<script>
(function () {
    const resolveUrl = @json(route('admin.inventory.scan.resolve'));
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    const qrStatusEl = document.getElementById('scan-qr-status');
    const modelStatusEl = document.getElementById('scan-model-status');
    const statusEl = document.getElementById('scan-status');
    const cameraErrorEl = document.getElementById('scan-camera-error');
    const resultsEl = document.getElementById('scan-results');
    const resultsTitleEl = document.getElementById('scan-results-title');
    const resultsMetaEl = document.getElementById('scan-results-meta');
    const resultsListEl = document.getElementById('scan-results-list');
    const resultsEmptyEl = document.getElementById('scan-results-empty');
    const resetBtn = document.getElementById('scan-reset');
    const refocusBtn = document.getElementById('scan-refocus');
    const zoomWrap = document.getElementById('scan-zoom-wrap');
    const zoomInput = document.getElementById('scan-zoom');
    const zoomLabel = document.getElementById('scan-zoom-label');
    const readerWrap = document.getElementById('scan-reader-wrap');

    const COLLECT_MS = 1800;
    let scanner = null;
    let scanning = false;
    let resolving = false;
    let collectTimer = null;
    let focusTimer = null;
    let qrPayload = null;
    let modelNumber = null;
    let preferBarcodeBox = false;
    let zoomSupported = false;
    let zoomMin = 1;
    let zoomMax = 1;
    let zoomStep = 0.1;
    let currentZoom = 1;
    let pinchStartDistance = null;
    let pinchStartZoom = 1;

    function setStatus(message) {
        statusEl.textContent = message;
    }

    function markReady(el, value) {
        el.textContent = value;
        el.classList.remove('text-gray-400');
        el.classList.add('text-emerald-700');
    }

    function getVideoTrack() {
        if (!scanner || typeof scanner.getRunningTrack !== 'function') {
            const video = document.querySelector('#scan-reader video');
            const stream = video && video.srcObject;
            return stream && stream.getVideoTracks ? stream.getVideoTracks()[0] : null;
        }

        try {
            return scanner.getRunningTrack();
        } catch (e) {
            return null;
        }
    }

    function getTrackCapabilities() {
        if (scanner && typeof scanner.getRunningTrackCapabilities === 'function') {
            try {
                return scanner.getRunningTrackCapabilities() || {};
            } catch (e) {
                // fall through
            }
        }

        const track = getVideoTrack();
        if (track && typeof track.getCapabilities === 'function') {
            try {
                return track.getCapabilities() || {};
            } catch (e) {
                return {};
            }
        }

        return {};
    }

    async function applyTrackConstraints(constraints) {
        if (!scanning) return false;

        if (scanner && typeof scanner.applyVideoConstraints === 'function') {
            try {
                await scanner.applyVideoConstraints(constraints);
                return true;
            } catch (e) {
                // fall through to raw track
            }
        }

        const track = getVideoTrack();
        if (!track || typeof track.applyConstraints !== 'function') {
            return false;
        }

        try {
            await track.applyConstraints(constraints);
            return true;
        } catch (e) {
            return false;
        }
    }

    function hideZoomControls() {
        zoomSupported = false;
        zoomWrap.classList.add('hidden');
        zoomInput.value = '1';
        zoomLabel.textContent = '1.0×';
        currentZoom = 1;
    }

    function setupZoomControls() {
        const capabilities = getTrackCapabilities();
        const zoom = capabilities.zoom;

        if (!zoom || typeof zoom.min !== 'number' || typeof zoom.max !== 'number' || zoom.max <= zoom.min) {
            hideZoomControls();
            return;
        }

        zoomSupported = true;
        zoomMin = zoom.min;
        zoomMax = zoom.max;
        zoomStep = typeof zoom.step === 'number' && zoom.step > 0 ? zoom.step : 0.1;
        currentZoom = Math.min(Math.max(zoomMin, currentZoom || zoomMin), zoomMax);

        zoomInput.min = String(zoomMin);
        zoomInput.max = String(zoomMax);
        zoomInput.step = String(zoomStep);
        zoomInput.value = String(currentZoom);
        zoomLabel.textContent = currentZoom.toFixed(1) + '×';
        zoomWrap.classList.remove('hidden');
    }

    async function setZoom(value, announce) {
        if (!zoomSupported || !scanning) return;

        const next = Math.min(zoomMax, Math.max(zoomMin, Number(value)));
        if (!Number.isFinite(next)) return;

        currentZoom = next;
        zoomInput.value = String(next);
        zoomLabel.textContent = next.toFixed(1) + '×';

        const ok = await applyTrackConstraints({
            advanced: [{ zoom: next }],
        });

        if (!ok) {
            await applyTrackConstraints({ zoom: next });
        }

        if (announce) {
            setStatus('Zoom ' + next.toFixed(1) + '×');
        }
    }

    async function nudgeFocus(point) {
        if (!scanning) return;

        const capabilities = getTrackCapabilities();
        const focusModes = capabilities.focusMode || [];
        const supportsPoints = Array.isArray(capabilities.pointsOfInterest);
        const advanced = [];

        if (supportsPoints) {
            advanced.push({
                pointsOfInterest: [point || { x: 0.5, y: 0.5 }],
            });
        }

        if (focusModes.includes('single-shot')) {
            advanced.push({ focusMode: 'single-shot' });
        } else if (focusModes.includes('continuous')) {
            advanced.push({ focusMode: 'continuous' });
        } else if (focusModes.includes('manual') && typeof capabilities.focusDistance === 'object') {
            const mid = (capabilities.focusDistance.min + capabilities.focusDistance.max) / 2;
            advanced.push({ focusMode: 'manual', focusDistance: mid });
        }

        if (!advanced.length && !focusModes.length) {
            setStatus('This camera does not expose focus controls in the browser.');
            return;
        }

        const payload = advanced.length ? { advanced: advanced } : { focusMode: 'continuous' };
        const ok = await applyTrackConstraints(payload);

        if (ok) {
            setStatus('Refocusing… hold steady on the barcode.');
            if (focusTimer) clearTimeout(focusTimer);
            focusTimer = setTimeout(async function () {
                if (focusModes.includes('continuous')) {
                    await applyTrackConstraints({ advanced: [{ focusMode: 'continuous' }] });
                }
            }, 700);
        } else {
            setStatus('Could not nudge focus on this device/browser.');
        }
    }

    async function configureCameraTrack() {
        const capabilities = getTrackCapabilities();
        const focusModes = capabilities.focusMode || [];
        const advanced = [];

        if (focusModes.includes('continuous')) {
            advanced.push({ focusMode: 'continuous' });
        } else if (focusModes.includes('single-shot')) {
            advanced.push({ focusMode: 'single-shot' });
        }

        if (capabilities.zoom && typeof capabilities.zoom.min === 'number') {
            // Mild default zoom helps thin CODE128 bars without losing the QR.
            const mild = Math.min(
                capabilities.zoom.max,
                Math.max(capabilities.zoom.min, capabilities.zoom.min + (capabilities.zoom.max - capabilities.zoom.min) * 0.15)
            );
            currentZoom = mild;
            advanced.push({ zoom: mild });
        }

        if (advanced.length) {
            await applyTrackConstraints({ advanced: advanced });
        }

        setupZoomControls();
    }

    function qrboxFor(viewfinderWidth, viewfinderHeight) {
        if (preferBarcodeBox) {
            return {
                width: Math.floor(viewfinderWidth * 0.92),
                height: Math.max(90, Math.floor(viewfinderHeight * 0.28)),
            };
        }

        return {
            width: Math.floor(viewfinderWidth * 0.9),
            height: Math.floor(viewfinderHeight * 0.7),
        };
    }

    function resetMarkers() {
        qrPayload = null;
        modelNumber = null;
        resolving = false;
        preferBarcodeBox = false;
        if (collectTimer) {
            clearTimeout(collectTimer);
            collectTimer = null;
        }
        qrStatusEl.textContent = 'Waiting…';
        modelStatusEl.textContent = 'Waiting…';
        qrStatusEl.classList.add('text-gray-400');
        modelStatusEl.classList.add('text-gray-400');
        qrStatusEl.classList.remove('text-emerald-700');
        modelStatusEl.classList.remove('text-emerald-700');
        resultsEl.classList.add('hidden');
        resultsListEl.innerHTML = '';
        resultsEmptyEl.classList.add('hidden');
        cameraErrorEl.classList.add('hidden');
        cameraErrorEl.textContent = '';
    }

    function looksLikeQrPayload(text) {
        const value = String(text || '').trim();
        if (!value) return false;
        if (/^https?:\/\//i.test(value)) return true;
        if (/appliance\.php/i.test(value)) return true;
        if (/\/inventory\/\d+/i.test(value)) return true;
        if (/[?&]id=\d+/i.test(value)) return true;
        return false;
    }

    function scheduleResolve() {
        if (resolving) return;

        if (qrPayload && modelNumber) {
            resolveScan();
            return;
        }

        if (collectTimer) return;

        collectTimer = setTimeout(function () {
            collectTimer = null;
            resolveScan();
        }, COLLECT_MS);
    }

    function onDecoded(decodedText) {
        if (resolving) return;

        const text = String(decodedText || '').trim();
        if (!text) return;

        if (looksLikeQrPayload(text)) {
            if (qrPayload === text) return;
            qrPayload = text;
            preferBarcodeBox = !modelNumber;
            markReady(qrStatusEl, text.length > 48 ? text.slice(0, 48) + '…' : text);
            setStatus('QR captured. Zoom/refocus on the model barcode if needed…');
            scheduleResolve();
            return;
        }

        if (modelNumber === text) return;
        modelNumber = text;
        preferBarcodeBox = false;
        markReady(modelStatusEl, text);
        setStatus(qrPayload ? 'Model captured. Resolving…' : 'Model captured. Looking for QR…');
        scheduleResolve();
    }

    async function resolveScan() {
        if (resolving) return;
        if (!qrPayload && !modelNumber) return;

        resolving = true;
        if (collectTimer) {
            clearTimeout(collectTimer);
            collectTimer = null;
        }

        setStatus('Looking up inventory…');
        await stopScanner();

        try {
            const response = await fetch(resolveUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    qr_payload: qrPayload,
                    model_number: modelNumber,
                }),
            });

            const data = await response.json().catch(function () {
                return {};
            });

            if (!response.ok) {
                setStatus(data.message || 'Could not resolve that scan.');
                resolving = false;
                await startScanner();
                return;
            }

            if (data.mode === 'exact' && data.url) {
                setStatus('Exact match — opening appliance…');
                window.location.href = data.url;
                return;
            }

            if (data.mode === 'need_model') {
                setStatus(data.message || 'Scan the model barcode too.');
                if (data.scanned_id) {
                    markReady(qrStatusEl, 'ID ' + data.scanned_id);
                }
                preferBarcodeBox = true;
                resolving = false;
                await startScanner();
                return;
            }

            if (data.mode === 'suggestions') {
                renderSuggestions(data);
                return;
            }

            setStatus(data.message || 'No matches.');
            resolving = false;
            await startScanner();
        } catch (error) {
            console.error(error);
            setStatus('Lookup failed. Try scanning again.');
            resolving = false;
            await startScanner();
        }
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function renderSuggestions(data) {
        const matches = Array.isArray(data.matches) ? data.matches : [];
        resultsEl.classList.remove('hidden');
        resultsListEl.innerHTML = '';

        const modelLabel = data.model_number || modelNumber || 'unknown model';
        resultsTitleEl.textContent = matches.length === 1 ? 'Possible match' : 'Possible matches';
        resultsMetaEl.textContent = data.scanned_id
            ? ('Model ' + modelLabel + ' · scanned ID ' + data.scanned_id + ' (no exact match)')
            : ('Model ' + modelLabel);

        if (!matches.length) {
            resultsEmptyEl.classList.remove('hidden');
            setStatus('No units found for that model. Scan again or search inventory.');
            return;
        }

        resultsEmptyEl.classList.add('hidden');
        matches.forEach(function (item) {
            const highlight = data.scanned_id && Number(item.id) === Number(data.scanned_id);
            const row = document.createElement('a');
            row.href = item.url;
            row.className = 'flex items-start justify-between gap-3 py-3 hover:bg-gray-50 px-1 rounded-md ' +
                (highlight ? 'bg-amber-50' : '');
            row.innerHTML =
                '<div class="min-w-0">' +
                    '<div class="text-sm font-semibold text-gray-900">' +
                        escapeHtml(item.unit_label || ('Appliance #' + item.id)) +
                        (highlight ? ' <span class="text-amber-700">(same ID as QR)</span>' : '') +
                    '</div>' +
                    '<div class="mt-1 text-sm text-gray-600">' +
                        escapeHtml([item.brand, item.model_number].filter(Boolean).join(' · ') || '—') +
                    '</div>' +
                    '<div class="mt-1 text-xs text-gray-500">' +
                        'Serial ' + escapeHtml(item.serial_number || 'N/A') +
                        ' · ' + escapeHtml(item.truck_name || 'No truck') +
                        ' · ' + escapeHtml(item.status || 'Triage') +
                    '</div>' +
                '</div>' +
                '<span class="mt-1 shrink-0 text-indigo-600 text-sm font-semibold">Open <i class="fas fa-chevron-right ml-1"></i></span>';
            resultsListEl.appendChild(row);
        });

        setStatus('Pick the correct unit, or tap Scan again.');
    }

    async function stopScanner() {
        if (!scanner || !scanning) return;
        try {
            await scanner.stop();
        } catch (e) {
            // ignore stop races
        }
        scanning = false;
        hideZoomControls();
    }

    async function startScanner() {
        cameraErrorEl.classList.add('hidden');

        if (!window.Html5Qrcode) {
            cameraErrorEl.textContent = 'Scanner library failed to load.';
            cameraErrorEl.classList.remove('hidden');
            setStatus('Unable to start camera.');
            return;
        }

        if (!scanner) {
            scanner = new Html5Qrcode('scan-reader', { verbose: false });
        }

        if (scanning) return;

        const formats = [
            Html5QrcodeSupportedFormats.QR_CODE,
            Html5QrcodeSupportedFormats.CODE_128,
            Html5QrcodeSupportedFormats.CODE_39,
            Html5QrcodeSupportedFormats.EAN_13,
            Html5QrcodeSupportedFormats.EAN_8,
        ];

        const cameraConfig = {
            facingMode: { ideal: 'environment' },
            width: { ideal: 1920 },
            height: { ideal: 1080 },
            advanced: [{ focusMode: 'continuous' }],
        };

        try {
            await scanner.start(
                cameraConfig,
                {
                    fps: 15,
                    qrbox: qrboxFor,
                    aspectRatio: 1.333,
                    formatsToSupport: formats,
                    experimentalFeatures: {
                        useBarCodeDetectorIfSupported: true,
                    },
                },
                onDecoded,
                function () {}
            );
            scanning = true;
            await configureCameraTrack();
            setStatus('Point the camera at the sticker. Use zoom/refocus if the barcode is soft.');
        } catch (error) {
            console.error(error);
            // Retry without advanced focus constraint — some browsers reject the whole start.
            try {
                await scanner.start(
                    {
                        facingMode: { ideal: 'environment' },
                        width: { ideal: 1920 },
                        height: { ideal: 1080 },
                    },
                    {
                        fps: 15,
                        qrbox: qrboxFor,
                        aspectRatio: 1.333,
                        formatsToSupport: formats,
                        experimentalFeatures: {
                            useBarCodeDetectorIfSupported: true,
                        },
                    },
                    onDecoded,
                    function () {}
                );
                scanning = true;
                await configureCameraTrack();
                setStatus('Point the camera at the sticker. Use zoom/refocus if the barcode is soft.');
            } catch (retryError) {
                console.error(retryError);
                cameraErrorEl.textContent = 'Camera permission denied or unavailable. Allow camera access and try again.';
                cameraErrorEl.classList.remove('hidden');
                setStatus('Camera not available.');
            }
        }
    }

    function pinchDistance(touches) {
        const dx = touches[0].clientX - touches[1].clientX;
        const dy = touches[0].clientY - touches[1].clientY;
        return Math.hypot(dx, dy);
    }

    zoomInput.addEventListener('input', function () {
        setZoom(zoomInput.value, false);
    });

    refocusBtn.addEventListener('click', function () {
        nudgeFocus({ x: 0.5, y: preferBarcodeBox ? 0.62 : 0.5 });
    });

    readerWrap.addEventListener('click', function (event) {
        if (!scanning || event.target.closest('#scan-refocus')) return;

        const rect = readerWrap.getBoundingClientRect();
        if (!rect.width || !rect.height) return;

        nudgeFocus({
            x: Math.min(1, Math.max(0, (event.clientX - rect.left) / rect.width)),
            y: Math.min(1, Math.max(0, (event.clientY - rect.top) / rect.height)),
        });
    });

    readerWrap.addEventListener('touchstart', function (event) {
        if (!zoomSupported || event.touches.length !== 2) return;
        pinchStartDistance = pinchDistance(event.touches);
        pinchStartZoom = currentZoom;
    }, { passive: true });

    readerWrap.addEventListener('touchmove', function (event) {
        if (!zoomSupported || pinchStartDistance === null || event.touches.length !== 2) return;
        event.preventDefault();
        const scale = pinchDistance(event.touches) / pinchStartDistance;
        setZoom(pinchStartZoom * scale, false);
    }, { passive: false });

    readerWrap.addEventListener('touchend', function () {
        pinchStartDistance = null;
    });

    resetBtn.addEventListener('click', async function () {
        resetMarkers();
        setStatus('Restarting scanner…');
        await stopScanner();
        await startScanner();
    });

    resetMarkers();
    startScanner();

    window.addEventListener('beforeunload', function () {
        if (scanner && scanning) {
            scanner.stop().catch(function () {});
        }
    });
})();
</script>
@endpush
