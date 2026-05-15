@extends('layouts.admin')

@section('title', 'Create truck')
@section('page-title', 'Create truck')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-semibold text-gray-900">New truck</h2>
            <a href="{{ route('admin.trucks.index') }}" class="text-gray-600 hover:text-gray-900 text-sm">
                <i class="fas fa-arrow-left mr-1"></i>Back
            </a>
        </div>

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
