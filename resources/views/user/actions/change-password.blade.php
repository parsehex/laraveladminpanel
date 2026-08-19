@extends('layouts.admin')

@section('title', 'Change Password')
@section('page-title', 'Change Password')

@section('page-actions')
    <a href="{{ route(\App\Support\PanelRedirector::routeNameFor(auth()->user())) }}" class="inline-flex items-center text-sm font-semibold text-slate-600 hover:text-slate-900">
        <i class="fas fa-arrow-left mr-1"></i>Back to dashboard
    </a>
@endsection

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" action="{{ route('admin.profile.password.update') }}" class="space-y-6">
            @csrf
            @method('PUT')

            <x-form.input 
                name="password" 
                label="Password" 
                type="password" 
                placeholder="Leave blank to keep current password" />
            
            <x-form.input 
                name="password_confirmation" 
                label="Confirm Password" 
                type="password" 
                placeholder="Leave blank to keep current password" />
            
            <div class="flex items-center justify-end space-x-3">
                <a href="{{ route(\App\Support\PanelRedirector::routeNameFor(auth()->user())) }}"
                   class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md">
                    Cancel
                </a>
                <x-form.button type="submit" variant="primary">
                    <i class="fas fa-save mr-2"></i>Update User
                </x-form.button>
            </div>
        </form>
    </div>
</div>
@endsection
