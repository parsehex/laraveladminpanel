@extends('layouts.admin')

@section('title', 'Edit '.$flow['name'].' Deman Flow')
@section('page-title', 'Edit '.$flow['name'])

@section('page-actions')
    <a href="{{ route('admin.deman-flows.index') }}" class="inline-flex items-center justify-center rounded-md bg-gray-500 px-3 py-2 text-sm font-semibold text-white hover:bg-gray-600">Back</a>
@endsection

@section('content')
<div class="space-y-6" id="deman-flow-editor">
    <form method="POST" action="{{ route('admin.deman-flows.update', $flow['slug']) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-lg shadow p-6 space-y-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-sm text-gray-500">Slug <code class="text-gray-800">{{ $flow['slug'] }}</code></p>
                    <p class="text-xs text-gray-400 mt-1">These prompts appear as salvage checklist rows on the demanufacture form.</p>
                </div>
                <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Save prompts</button>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input type="text" name="name" value="{{ old('name', $flow['name']) }}" class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md" required>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="bg-slate-800 px-4 py-3 flex items-center justify-between">
                <h2 class="text-white font-semibold">Prompts</h2>
                <button type="button" id="add-prompt" class="rounded-md bg-white/10 px-3 py-1.5 text-sm font-semibold text-white hover:bg-white/20">Add prompt</button>
            </div>
            <div id="prompts-editor" class="p-4 space-y-3">
                @php
                    $promptRows = old('prompts', $flow['prompts']);
                @endphp
                @foreach($promptRows as $index => $prompt)
                <div class="prompt-row grid grid-cols-1 md:grid-cols-12 gap-3 items-start rounded-md border border-gray-200 p-3">
                    <div class="md:col-span-3">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Key</label>
                        <input type="text" name="prompts[{{ $index }}][key]" value="{{ $prompt['key'] ?? '' }}" class="w-full px-3 py-2 border border-gray-300 rounded-md font-mono text-sm" placeholder="compressor" required>
                    </div>
                    <div class="md:col-span-8">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Description</label>
                        <input type="text" name="prompts[{{ $index }}][description]" value="{{ $prompt['description'] ?? '' }}" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Compressor system…" required>
                    </div>
                    <div class="md:col-span-1 flex md:justify-end md:pt-6">
                        <button type="button" class="remove-prompt rounded-md px-2 py-2 text-sm font-semibold text-red-700 hover:bg-red-50" title="Remove">&times;</button>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const editor = document.getElementById('prompts-editor');
    const addBtn = document.getElementById('add-prompt');
    if (!editor || !addBtn) return;

    const reindex = () => {
        [...editor.querySelectorAll('.prompt-row')].forEach((row, index) => {
            row.querySelectorAll('input').forEach((input) => {
                const field = input.name.includes('[key]') ? 'key' : 'description';
                input.name = `prompts[${index}][${field}]`;
            });
        });
    };

    const bindRemove = (row) => {
        row.querySelector('.remove-prompt')?.addEventListener('click', () => {
            if (editor.querySelectorAll('.prompt-row').length <= 1) return;
            row.remove();
            reindex();
        });
    };

    editor.querySelectorAll('.prompt-row').forEach(bindRemove);

    addBtn.addEventListener('click', () => {
        const index = editor.querySelectorAll('.prompt-row').length;
        const row = document.createElement('div');
        row.className = 'prompt-row grid grid-cols-1 md:grid-cols-12 gap-3 items-start rounded-md border border-gray-200 p-3';
        row.innerHTML = `
            <div class="md:col-span-3">
                <label class="block text-xs font-medium text-gray-500 mb-1">Key</label>
                <input type="text" name="prompts[${index}][key]" class="w-full px-3 py-2 border border-gray-300 rounded-md font-mono text-sm" placeholder="compressor" required>
            </div>
            <div class="md:col-span-8">
                <label class="block text-xs font-medium text-gray-500 mb-1">Description</label>
                <input type="text" name="prompts[${index}][description]" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Compressor system…" required>
            </div>
            <div class="md:col-span-1 flex md:justify-end md:pt-6">
                <button type="button" class="remove-prompt rounded-md px-2 py-2 text-sm font-semibold text-red-700 hover:bg-red-50" title="Remove">&times;</button>
            </div>
        `;
        editor.appendChild(row);
        bindRemove(row);
    });
})();
</script>
@endpush
