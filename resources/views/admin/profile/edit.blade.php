@extends('layouts.admin')

@section('title', 'Admin Profile')
@section('page-title', 'Profile')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-6">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">Profile</h2>
                <p class="text-sm text-gray-500">Update your display name and review account details.</p>
            </div>
            <a href="{{ route('admin.profile.password.edit') }}" class="inline-flex items-center justify-center rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                <i class="fas fa-lock mr-2"></i>Change Password
            </a>
        </div>

        <form method="POST" action="{{ route('admin.profile.update') }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <x-form.input name="name" label="Full Name" type="text" :value="$user->name" required="true" />

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                    <input type="email" value="{{ $user->email }}" disabled class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 text-gray-600 shadow-sm">
                    <p class="mt-1 text-xs text-gray-500">Email address cannot be edited.</p>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-3">
                <a href="{{ route(\App\Support\PanelRedirector::routeNameFor(auth()->user())) }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md">Cancel</a>
                <x-form.button type="submit" variant="primary"><i class="fas fa-save mr-2"></i>Update Profile</x-form.button>
            </div>
        </form>
    </div>
</div>
@endsection
