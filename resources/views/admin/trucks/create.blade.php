@extends('layouts.admin')

@section('title', 'Create truck')
@section('page-title', 'Create truck')

@section('page-actions')
    <a href="{{ route('admin.trucks.index') }}" class="inline-flex items-center text-sm font-semibold text-slate-600 hover:text-slate-900">
        <i class="fas fa-arrow-left mr-1"></i>Back
    </a>
@endsection

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" action="{{ route('admin.trucks.store') }}" class="space-y-6">
            @csrf
            @include('admin.trucks.form', ['truck' => null])

            <div class="flex justify-end gap-2 pt-4">
                <a href="{{ route('admin.trucks.index') }}" class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600">Cancel</a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    <i class="fas fa-save mr-2"></i>Save
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
