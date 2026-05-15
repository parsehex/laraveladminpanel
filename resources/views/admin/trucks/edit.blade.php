@extends('layouts.admin')

@section('title', 'Edit truck')
@section('page-title', 'Edit truck')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-semibold text-gray-900">Edit: {{ $truck->name }}</h2>
            <a href="{{ route('admin.trucks.index') }}" class="text-gray-600 hover:text-gray-900 text-sm">
                <i class="fas fa-arrow-left mr-1"></i>Back
            </a>
        </div>

        <form method="POST" action="{{ route('admin.trucks.update', $truck) }}" class="space-y-6">
            @csrf
            @method('PUT')
            @include('admin.trucks.form', ['truck' => $truck])

            <div class="flex justify-end gap-2 pt-4">
                <a href="{{ route('admin.trucks.show', $truck) }}" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">View</a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    <i class="fas fa-save mr-2"></i>Update
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
