@extends('layouts.user')

@section('title', 'User Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Welcome Card -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="h-16 w-16 rounded-full bg-blue-500 flex items-center justify-center">
                    <i class="fas fa-user text-white text-2xl"></i>
                </div>
            </div>
            <div class="ml-6">
                <h1 class="text-2xl font-bold text-gray-900">Welcome back, {{ $user->name }}!</h1>
                <p class="text-gray-600">Here's what's happening with your account today.</p>
            </div>
        </div>
    </div>
    
    <!-- User Info Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-500 bg-opacity-10">
                    <i class="fas fa-envelope text-blue-500 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Email</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $user->email }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-500 bg-opacity-10">
                    <i class="fas fa-calendar text-green-500 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Member Since</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $user->created_at->format('M d, Y') }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-500 bg-opacity-10">
                    <i class="fas fa-check-circle text-purple-500 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Account Status</p>
                    <p class="text-lg font-semibold text-green-600">{{ ucfirst($user->status) }}</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Quick Actions</h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <a href="{{ route('user.account.edit', ['account' => encrypt($user->id)]) }}" class="flex items-center p-4 border border-gray-200 rounded-lg hover:border-blue-500 hover:shadow-md transition duration-150">
                    <div class="p-2 bg-blue-500 bg-opacity-10 rounded">
                        <i class="fas fa-user-edit text-blue-500"></i>
                    </div>
                    <div class="ml-3">
                        <p class="font-medium text-gray-900">Edit Profile</p>
                        <p class="text-sm text-gray-500">Update your information</p>
                    </div>
                </a>
                
                <a href="{{ route("user.account.changePassword",['uid' => encrypt($user->id)]) }}" class="flex items-center p-4 border border-gray-200 rounded-lg hover:border-green-500 hover:shadow-md transition duration-150">
                    <div class="p-2 bg-green-500 bg-opacity-10 rounded">
                        <i class="fas fa-lock text-green-500"></i>
                    </div>
                    <div class="ml-3">
                        <p class="font-medium text-gray-900">Change Password</p>
                        <p class="text-sm text-gray-500">Update your password</p>
                    </div>
                </a>
                
                {{-- <a href="#" class="flex items-center p-4 border border-gray-200 rounded-lg hover:border-purple-500 hover:shadow-md transition duration-150">
                    <div class="p-2 bg-purple-500 bg-opacity-10 rounded">
                        <i class="fas fa-cog text-purple-500"></i>
                    </div>
                    <div class="ml-3">
                        <p class="font-medium text-gray-900">Settings</p>
                        <p class="text-sm text-gray-500">Configure your preferences</p>
                    </div>
                </a> --}}
            </div>
        </div>
    </div>
</div>
@endsection