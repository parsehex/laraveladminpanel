@extends('layouts.user')

@section('title', 'Edit User')
@section('page-title', 'Edit User')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-semibold text-gray-900">Edit User: {{ $user->name }}</h2>
            <a href="{{ route('user.dashboard') }}" class="text-gray-600 hover:text-gray-900">
                <i class="fas fa-arrow-left mr-1"></i> Back to dashboard
            </a>
        </div>
        
        <form method="POST" action="{{ route('user.account.update', ['account' => encrypt($user->id)]) }}" class="space-y-6">
            @csrf
            @method('PUT')
            
            <x-form.input 
                name="name" 
                label="Full Name" 
                type="text" 
                :value="$user->name"
                required="true" />
            
            <x-form.input 
                name="email" 
                label="Email Address" 
                type="email" 
                :value="$user->email"
                required="true" />
            
            <div class="flex items-center justify-end space-x-3">
                <a href="{{ route('user.dashboard') }}" 
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