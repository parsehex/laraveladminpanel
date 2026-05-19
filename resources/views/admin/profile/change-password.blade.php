@extends('layouts.admin')

@section('title', 'Change Password')
@section('page-title', 'Change Password')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">Change Password</h2>
                <p class="text-sm text-gray-500">Set a new password for {{ $user->name }}.</p>
            </div>
            <a href="{{ route('admin.profile.edit') }}" class="text-gray-600 hover:text-gray-900">
                <i class="fas fa-arrow-left mr-1"></i>Back to Profile
            </a>
        </div>

        <form method="POST" action="{{ route('admin.profile.password.update') }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <x-form.input name="password" label="New Password" type="password" required="true" />
                <x-form.input name="password_confirmation" label="Confirm Password" type="password" required="true" />
            </div>

            <div class="flex items-center justify-end space-x-3">
                <a href="{{ route('admin.profile.edit') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md">Cancel</a>
                <x-form.button type="submit" variant="primary"><i class="fas fa-save mr-2"></i>Update Password</x-form.button>
            </div>
        </form>
    </div>
</div>
@endsection
