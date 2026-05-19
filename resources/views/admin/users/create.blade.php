@extends('layouts.admin')

@section('title', 'Create User')
@section('page-title', 'Create New User')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-semibold text-gray-900">Create New User</h2>
            <a href="{{ route('admin.users.index') }}" class="text-gray-600 hover:text-gray-900">
                <i class="fas fa-arrow-left mr-1"></i> Back to Users
            </a>
        </div>
        
        <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-6">
            @csrf
            
            <x-form.input name="name" label="Full Name" type="text" required="true" />
            <x-form.input name="email" label="Email Address" type="email" required="true" />
            <x-form.input name="password" label="Password" type="password" required="true" />
            <x-form.input name="password_confirmation" label="Confirm Password" type="password" required="true" />
            <x-form.input name="registration_code" label="Registration Code" type="text" required="true" />
            
            <x-form.select name="role" label="Role" :options="$roles" required="true" />
            <x-form.select name="platform" label="Kit Platform" :options="['' => 'All / Not assigned', 'amazon' => 'Amazon', 'shopify' => 'Shopify']" />
            
            <x-form.select name="status" label="Status" :options="['active' => 'Active', 'inactive' => 'Inactive']" value="active" required="true" />

            <div class="flex items-center justify-end space-x-3">
                <a href="{{ route('admin.users.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md">Cancel</a>
                <x-form.button type="submit" variant="primary"><i class="fas fa-save mr-2"></i>Create User</x-form.button>
            </div>
        </form>
    </div>
</div>
@endsection
