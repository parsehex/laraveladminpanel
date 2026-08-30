@extends('layouts.admin')

@section('title', 'Edit '.$flow['name'].' Testing Flow')
@section('page-title', 'Edit Testing Flow')
@section('page-subtitle', $flow['name'])

@section('page-actions')
    <a href="{{ route('admin.testing-flows.index') }}" class="inline-flex items-center justify-center rounded-md bg-gray-500 px-3 py-2 text-sm font-semibold text-white hover:bg-gray-600">Back</a>
@endsection

@section('content')
<div class="space-y-6"
     id="flow-editor"
     data-flow='@json($flow)'
     data-statuses='@json($statuses)'>
    <form method="POST" action="{{ route('admin.testing-flows.update', $flow['slug']) }}" id="flow-edit-form" class="space-y-6">
        @csrf
        @method('PUT')
        <input type="hidden" name="flow_json" id="flow-json-input">

        <div class="bg-white rounded-lg shadow p-6 space-y-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-sm text-gray-500">Slug <code class="text-gray-800">{{ $flow['slug'] }}</code> · Version <strong>v{{ $flow['version'] }}</strong></p>
                    <p class="text-xs text-gray-400 mt-1">Saving bumps the version and archives the previous definition in the database.</p>
                </div>
                <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Save flow</button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                    <input type="text" name="name" id="flow-name" value="{{ old('name', $flow['name']) }}" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Start step</label>
                    <select name="start" id="flow-start" class="w-full px-3 py-2 border border-gray-300 rounded-md" required></select>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="bg-slate-800 px-4 py-3 flex items-center justify-between">
                    <h2 class="text-white font-semibold">Steps</h2>
                    <button type="button" id="add-step" class="rounded-md bg-white/10 px-3 py-1.5 text-sm font-semibold text-white hover:bg-white/20">Add step</button>
                </div>
                <div id="steps-editor" class="p-4 space-y-4 max-h-[70vh] overflow-y-auto"></div>
            </div>

            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="bg-blue-600 px-4 py-3 flex items-center justify-between">
                    <h2 class="text-white font-semibold">Preview</h2>
                    <button type="button" id="preview-reset" class="rounded-md bg-white/10 px-3 py-1.5 text-sm font-semibold text-white hover:bg-white/20">Restart</button>
                </div>
                <div class="p-4 space-y-4">
                    <div id="wizard-root" class="min-h-[12rem]"></div>
                    <div id="wizard-complete" class="hidden rounded-md border border-emerald-200 bg-emerald-50 p-4">
                        <p class="font-semibold text-emerald-900">Would set status to: <span id="wizard-final-status"></span></p>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
    @vite('resources/js/pages/testing-flow-editor.ts')
@endpush
