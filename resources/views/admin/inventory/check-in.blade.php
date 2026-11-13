@extends('layouts.admin')

@section('title', 'Check in')
@section('page-title', 'Check in')
@section('page-subtitle', 'One item')

@section('content')
<div class="mx-auto max-w-lg">
    <form id="check-in-form" method="POST" action="{{ route('admin.inventory.check-in.store') }}" enctype="multipart/form-data" class="rounded-lg bg-white p-4 shadow space-y-4">
        @csrf
        <p class="text-sm text-gray-600">Take one or more photos of this item’s label. The truck is chosen only if the item is not already in the system.</p>

        <div id="check-in-camera" class="hidden overflow-hidden rounded-lg border border-gray-200 bg-black">
            <video id="check-in-video" class="block w-full max-h-[70vh] bg-black object-cover" playsinline muted autoplay></video>
            <div class="flex gap-2 bg-white p-3">
                <button type="button" id="check-in-capture" class="inline-flex flex-1 items-center justify-center rounded-md bg-slate-900 px-3 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                    Capture
                </button>
                <button type="button" id="check-in-camera-close" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm font-semibold text-gray-800 hover:bg-gray-50">
                    Done
                </button>
            </div>
        </div>

        <div class="flex gap-2">
            <button type="button" id="check-in-camera-open" class="inline-flex flex-1 items-center justify-center rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm font-semibold text-gray-800 hover:bg-gray-50">
                Take photo
            </button>
            <label for="photos" class="inline-flex flex-1 cursor-pointer items-center justify-center rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm font-semibold text-gray-800 hover:bg-gray-50">
                Choose photos
            </label>
        </div>
        <input id="photos" type="file" name="photos[]" accept="image/*" multiple class="sr-only">

        <div id="check-in-previews" class="flex gap-2 overflow-x-auto"></div>
        <p id="check-in-count" class="text-xs text-gray-500">Up to 5 photos, 8MB each.</p>
        <p id="check-in-camera-error" class="hidden text-sm font-medium text-red-600"></p>

        <button type="submit" id="check-in-submit" disabled class="inline-flex w-full items-center justify-center rounded-md bg-slate-900 px-3 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-300">
            Read label
        </button>
    </form>
</div>
@endsection

@push('scripts')
<script>
const maxPhotos = 5;
const maxBytes = 8 * 1024 * 1024;
const form = document.getElementById('check-in-form');
const input = document.getElementById('photos');
const openButton = document.getElementById('check-in-camera-open');
const closeButton = document.getElementById('check-in-camera-close');
const captureButton = document.getElementById('check-in-capture');
const cameraPanel = document.getElementById('check-in-camera');
const video = document.getElementById('check-in-video');
const previews = document.getElementById('check-in-previews');
const count = document.getElementById('check-in-count');
const error = document.getElementById('check-in-camera-error');
const submit = document.getElementById('check-in-submit');
const photos = [];
let stream = null;

function showError(message) {
    error.textContent = message;
    error.classList.remove('hidden');
}

function clearError() {
    error.textContent = '';
    error.classList.add('hidden');
}

function syncInput() {
    const transfer = new DataTransfer();
    photos.forEach((photo) => transfer.items.add(photo.file));
    input.files = transfer.files;
    submit.disabled = photos.length === 0;
    captureButton.disabled = photos.length >= maxPhotos;
    count.textContent = photos.length === 0
        ? 'Up to 5 photos, 8MB each.'
        : photos.length + ' of 5 photos.';
}

function renderPreviews() {
    previews.replaceChildren();
    photos.forEach((photo, index) => {
        const frame = document.createElement('div');
        frame.className = 'relative h-20 w-20 flex-shrink-0';
        const image = document.createElement('img');
        image.src = photo.url;
        image.alt = 'Label photo ' + (index + 1);
        image.className = 'h-20 w-20 rounded-md border border-gray-200 object-cover';
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'absolute right-1 top-1 rounded bg-black/70 px-1.5 text-xs font-semibold text-white';
        remove.textContent = 'Remove';
        remove.addEventListener('click', () => {
            URL.revokeObjectURL(photo.url);
            photos.splice(index, 1);
            renderPreviews();
            syncInput();
        });
        frame.append(image, remove);
        previews.append(frame);
    });
}

function addFiles(fileList) {
    clearError();
    Array.from(fileList).forEach((file) => {
        if (photos.length >= maxPhotos) {
            showError('Use at most 5 photos.');
            return;
        }
        if (!file.type.startsWith('image/')) {
            showError('Each photo must be an image.');
            return;
        }
        if (file.size > maxBytes) {
            showError('Each photo must be 8MB or smaller.');
            return;
        }
        photos.push({ file, url: URL.createObjectURL(file) });
    });
    renderPreviews();
    syncInput();
}

async function openCamera() {
    clearError();
    if (!window.isSecureContext || !navigator.mediaDevices?.getUserMedia) {
        showError('Camera requires HTTPS or localhost.');
        return;
    }
    try {
        stream = await navigator.mediaDevices.getUserMedia({
            audio: false,
            video: { facingMode: { ideal: 'environment' }, width: { ideal: 1920 }, height: { ideal: 1080 } },
        });
    } catch {
        try {
            stream = await navigator.mediaDevices.getUserMedia({
                audio: false,
                video: { facingMode: 'environment' },
            });
        } catch (retryError) {
            showError('Camera permission denied or unavailable. Allow camera access, or choose photos instead.');
            return;
        }
    }
    video.srcObject = stream;
    cameraPanel.classList.remove('hidden');
    openButton.classList.add('hidden');
}

function closeCamera() {
    if (stream) {
        stream.getTracks().forEach((track) => track.stop());
        stream = null;
    }
    video.srcObject = null;
    cameraPanel.classList.add('hidden');
    openButton.classList.remove('hidden');
}

function capturePhoto() {
    if (photos.length >= maxPhotos || !video.videoWidth) {
        return;
    }
    const longest = Math.max(video.videoWidth, video.videoHeight);
    const scale = Math.min(1, 1920 / longest);
    const canvas = document.createElement('canvas');
    canvas.width = Math.round(video.videoWidth * scale);
    canvas.height = Math.round(video.videoHeight * scale);
    canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
    canvas.toBlob((blob) => {
        if (!blob) {
            showError('The photo could not be saved. Try again.');
            return;
        }
        addFiles([new File([blob], 'label-' + Date.now() + '.jpg', { type: 'image/jpeg' })]);
    }, 'image/jpeg', 0.85);
}

openButton.addEventListener('click', openCamera);
closeButton.addEventListener('click', closeCamera);
captureButton.addEventListener('click', capturePhoto);
input.addEventListener('change', () => addFiles(input.files));
window.addEventListener('pagehide', closeCamera);
</script>
@endpush
