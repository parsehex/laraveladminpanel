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

        <div id="scan-reader-wrap" class="mt-4 overflow-hidden rounded-lg border border-gray-200 bg-black">
            <div id="scan-reader" class="w-full"></div>
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
<script src="https://cdn.jsdelivr.net/npm/@undecaf/zbar-wasm@0.9.15/dist/index.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@undecaf/barcode-detector-polyfill@0.9.23/dist/index.js"></script>
<script>
    try {
        window['BarcodeDetector'].getSupportedFormats()
    } catch {
        window['BarcodeDetector'] = barcodeDetectorPolyfill.BarcodeDetectorPolyfill
    }
</script>
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

    const COLLECT_MS = 1600;
    let scanner = null;
    let scanning = false;
    let resolving = false;
    let collectTimer = null;
    let qrPayload = null;
    let modelNumber = null;

    function setStatus(message) {
        statusEl.textContent = message;
    }

    function markReady(el, value) {
        el.textContent = value;
        el.classList.remove('text-gray-400');
        el.classList.add('text-emerald-700');
    }

    function resetMarkers() {
        qrPayload = null;
        modelNumber = null;
        resolving = false;
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
            markReady(qrStatusEl, text.length > 48 ? text.slice(0, 48) + '…' : text);
            setStatus('QR captured. Looking for model barcode…');
            scheduleResolve();
            return;
        }

        if (modelNumber === text) return;
        modelNumber = text;
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
            scanner = new Html5Qrcode('scan-reader');
        }

        if (scanning) return;

        const formats = [
            Html5QrcodeSupportedFormats.QR_CODE,
            Html5QrcodeSupportedFormats.CODE_128,
            Html5QrcodeSupportedFormats.CODE_39,
            Html5QrcodeSupportedFormats.EAN_13,
            Html5QrcodeSupportedFormats.EAN_8,
        ];

        try {
            await scanner.start(
                { facingMode: 'environment' },
                {
                    fps: 12,
                    qrbox: function (viewfinderWidth, viewfinderHeight) {
                        return {
                            width: Math.floor(viewfinderWidth * 0.88),
                            height: Math.floor(viewfinderHeight * 0.62),
                        };
                    },
                    aspectRatio: 1.333,
                    formatsToSupport: formats,
                },
                onDecoded,
                function () {}
            );
            scanning = true;
            setStatus('Point the camera at the sticker.');
        } catch (error) {
            console.error(error);
            cameraErrorEl.textContent = 'Camera permission denied or unavailable. Allow camera access and try again.';
            cameraErrorEl.classList.remove('hidden');
            setStatus('Camera not available.');
        }
    }

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
