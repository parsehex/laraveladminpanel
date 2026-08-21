@extends('layouts.admin')

@section('title', 'Scan Appliance')
@section('page-title', 'Scan Appliance')

@section('content')
<div class="mx-auto max-w-3xl space-y-4">
    <div class="rounded-lg bg-white p-4 shadow sm:p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Scan QR sticker</h2>
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
            <video id="scan-video" class="block w-full max-h-[70vh] object-cover bg-black" playsinline muted autoplay></video>
            <div class="pointer-events-none absolute inset-0 flex items-center justify-center">
                <div id="scan-guide" class="rounded-md border-2 border-white/70 shadow-[0_0_0_9999px_rgba(0,0,0,0.28)]" style="width:88%;height:58%;"></div>
            </div>
        </div>

        <p id="scan-engine" class="mt-2 text-xs text-gray-500"></p>
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

@push('scripts')
<script type="module">
const resolveUrl = @json(route('admin.inventory.scan.resolve'));
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
const ZXING_VERSION = '2.2.1';

const qrStatusEl = document.getElementById('scan-qr-status');
const modelStatusEl = document.getElementById('scan-model-status');
const statusEl = document.getElementById('scan-status');
const engineEl = document.getElementById('scan-engine');
const cameraErrorEl = document.getElementById('scan-camera-error');
const resultsEl = document.getElementById('scan-results');
const resultsTitleEl = document.getElementById('scan-results-title');
const resultsMetaEl = document.getElementById('scan-results-meta');
const resultsListEl = document.getElementById('scan-results-list');
const resultsEmptyEl = document.getElementById('scan-results-empty');
const resetBtn = document.getElementById('scan-reset');
const videoEl = document.getElementById('scan-video');
const guideEl = document.getElementById('scan-guide');
const readerWrapEl = document.getElementById('scan-reader-wrap');

const COLLECT_MS = 1600;
const SCAN_INTERVAL_MS = 120;
const ZXING_FORMATS = ['QRCode', 'Code128', 'Code39', 'EAN-13', 'EAN-8'];

let mediaStream = null;
let scanning = false;
let resolving = false;
let collectTimer = null;
let scanTimer = null;
let detectBusy = false;
let qrPayload = null;
let modelNumber = null;
let decoder = null;
let canvas = null;
let canvasCtx = null;
let readBarcodesFn = null;

function setStatus(message) {
    statusEl.textContent = message;
}

function setEngine(message) {
    engineEl.textContent = message;
}

function showCamera() {
    readerWrapEl.classList.remove('hidden');
    engineEl.classList.remove('hidden');
}

function hideCamera() {
    readerWrapEl.classList.add('hidden');
    engineEl.classList.add('hidden');
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
    guideEl.style.height = '58%';
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
        guideEl.style.height = '28%';
        markReady(qrStatusEl, text.length > 48 ? text.slice(0, 48) + '…' : text);
        setStatus('QR captured. Looking for model barcode…');
        scheduleResolve();
        return;
    }

    if (modelNumber === text) return;
    modelNumber = text;
    guideEl.style.height = '58%';
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
            guideEl.style.height = '28%';
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
    hideCamera();
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

async function createZxingDecoder() {
    const moduleUrl = `https://cdn.jsdelivr.net/npm/zxing-wasm@${ZXING_VERSION}/dist/es/reader/index.js`;
    const wasmUrl = `https://cdn.jsdelivr.net/npm/zxing-wasm@${ZXING_VERSION}/dist/reader/zxing_reader.wasm`;

    const zxing = await import(moduleUrl);

    if (typeof zxing.prepareZXingModule === 'function') {
        await zxing.prepareZXingModule({
            fireImmediately: true,
            overrides: {
                locateFile: (path, prefix) => {
                    if (String(path).endsWith('.wasm')) {
                        return wasmUrl;
                    }
                    return prefix + path;
                },
            },
        });
    }

    readBarcodesFn = zxing.readBarcodes;
    if (typeof readBarcodesFn !== 'function') {
        throw new Error('zxing-wasm readBarcodes export missing');
    }

    if (!canvas) {
        canvas = document.createElement('canvas');
        canvasCtx = canvas.getContext('2d', { willReadFrequently: true });
    }

    return {
        name: 'zxing-wasm',
        async detect(video) {
            if (!video.videoWidth || !video.videoHeight) {
                return [];
            }

            // Cap longest edge so decode stays responsive on phones.
            const maxEdge = 1280;
            const scale = Math.min(1, maxEdge / Math.max(video.videoWidth, video.videoHeight));
            const width = Math.max(1, Math.floor(video.videoWidth * scale));
            const height = Math.max(1, Math.floor(video.videoHeight * scale));

            if (canvas.width !== width || canvas.height !== height) {
                canvas.width = width;
                canvas.height = height;
            }

            canvasCtx.drawImage(video, 0, 0, width, height);
            const imageData = canvasCtx.getImageData(0, 0, width, height);
            const results = await readBarcodesFn(imageData, {
                tryHarder: true,
                tryRotate: true,
                formats: ZXING_FORMATS,
                maxNumberOfSymbols: 4,
            });

            return (results || [])
                .map((result) => String(result.text || '').trim())
                .filter(Boolean);
        },
    };
}

async function ensureDecoder() {
    if (decoder) {
        return decoder;
    }

    setStatus('Loading barcode engine…');
    decoder = await createZxingDecoder();
    setEngine('Decoder: ' + decoder.name);
    return decoder;
}

async function stopScanner() {
    scanning = false;
    detectBusy = false;

    if (scanTimer) {
        clearInterval(scanTimer);
        scanTimer = null;
    }

    if (mediaStream) {
        mediaStream.getTracks().forEach((track) => track.stop());
        mediaStream = null;
    }

    videoEl.removeAttribute('srcObject');
    videoEl.srcObject = null;
}

async function tickDetect() {
    if (!scanning || resolving || detectBusy || !decoder) {
        return;
    }

    if (videoEl.readyState < 2) {
        return;
    }

    detectBusy = true;

    try {
        const texts = await decoder.detect(videoEl);
        texts.forEach(onDecoded);
    } catch (error) {
        // Frame decode misses are normal; only log unexpected failures.
        if (error && error.name !== 'NotFoundException') {
            console.warn('Detect frame failed', error);
        }
    } finally {
        detectBusy = false;
    }
}

async function startScanner() {
    cameraErrorEl.classList.add('hidden');
    cameraErrorEl.textContent = '';
    showCamera();

    if (scanning) {
        return;
    }

    if (!window.isSecureContext) {
        cameraErrorEl.textContent = 'Camera requires HTTPS or localhost.';
        cameraErrorEl.classList.remove('hidden');
        setStatus('Camera blocked by browser security.');
        return;
    }

    try {
        await ensureDecoder();
    } catch (error) {
        console.error(error);
        cameraErrorEl.textContent = 'Could not load barcode decoder: ' + (error.message || error);
        cameraErrorEl.classList.remove('hidden');
        setStatus('Decoder unavailable.');
        return;
    }

    try {
        mediaStream = await navigator.mediaDevices.getUserMedia({
            audio: false,
            video: {
                facingMode: { ideal: 'environment' },
                width: { ideal: 1920 },
                height: { ideal: 1080 },
            },
        });
    } catch (error) {
        console.warn('Ideal camera constraints failed, retrying simple constraints', error);
        try {
            mediaStream = await navigator.mediaDevices.getUserMedia({
                audio: false,
                video: { facingMode: 'environment' },
            });
        } catch (retryError) {
            console.error(retryError);
            cameraErrorEl.textContent = 'Camera permission denied or unavailable. Allow camera access and try again.';
            cameraErrorEl.classList.remove('hidden');
            setStatus('Camera not available.');
            return;
        }
    }

    videoEl.srcObject = mediaStream;
    videoEl.setAttribute('playsinline', 'true');
    videoEl.muted = true;

    try {
        await videoEl.play();
    } catch (error) {
        console.warn('video.play() failed', error);
    }

    scanning = true;
    scanTimer = setInterval(tickDetect, SCAN_INTERVAL_MS);
    setStatus('Point the camera at the sticker.');
}

resetBtn.addEventListener('click', async function () {
    resetMarkers();
    showCamera();
    setStatus('Restarting scanner…');
    await stopScanner();
    await startScanner();
});

window.addEventListener('beforeunload', function () {
    stopScanner();
});

resetMarkers();
startScanner();
</script>
@endpush
