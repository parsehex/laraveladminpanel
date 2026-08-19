@extends('layouts.admin')

@section('title', 'User Details')
@section('page-title', $user->name)

@section('page-actions')
    @canAccess('users.edit')
    <a href="{{ route('admin.users.edit', $user) }}" class="inline-flex items-center justify-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700">
        <i class="fas fa-edit mr-2"></i>Edit User
    </a>
    @endcanAccess
    <a href="{{ route('admin.users.index') }}" class="inline-flex items-center justify-center rounded-md bg-gray-500 px-3 py-2 text-sm font-semibold text-white hover:bg-gray-600">
        <i class="fas fa-arrow-left mr-2"></i>Back to Users
    </a>
@endsection

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-1">
                    <div class="text-center">
                        <div class="mx-auto h-32 w-32 rounded-full bg-gray-300 flex items-center justify-center mb-4">
                            <i class="fas fa-user text-gray-600 text-4xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900">{{ $user->name }}</h3>
                        <p class="text-gray-600">{{ $user->email }}</p>
                        
                        <div class="mt-4 space-y-2">
                            @foreach($user->roles as $r)
                                <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-purple-100 text-purple-800 mr-1">{{ $r->name }}</span>
                            @endforeach
                            @if($user->roles->isEmpty())
                                <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-gray-100 text-gray-800">{{ $user->role }}</span>
                            @endif
                            <br>
                            <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full {{ $user->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ ucfirst($user->status) }}
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="lg:col-span-2">
                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <h4 class="text-lg font-medium text-gray-900 mb-4">Account Details</h4>
                            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Full Name</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $user->name }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Email Address</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $user->email }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Primary role (column)</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $user->role }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Status</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ ucfirst($user->status) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Member Since</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $user->created_at->format('F d, Y') }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $user->updated_at->format('F d, Y') }}</dd>
                                </div>
                            </dl>
                        </div>
                        
                        @canAccess('users.delete')
                        @if($user->id !== auth()->id())
                        <div class="border-t border-gray-200 pt-6">
                            <h4 class="text-lg font-medium text-gray-900 mb-4">Danger Zone</h4>
                            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h5 class="text-sm font-medium text-red-800">Delete User Account</h5>
                                        <p class="text-sm text-red-600">This action cannot be undone.</p>
                                    </div>
                                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Delete this user?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm">
                                            <i class="fas fa-trash mr-2"></i>Delete User
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @endif
                        @endcanAccess
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
