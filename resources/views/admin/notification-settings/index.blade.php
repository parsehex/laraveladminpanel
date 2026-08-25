@extends('layouts.admin')

@section('title', 'Notifications')
@section('page-title', 'Notifications')

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css">
<style>
    .select2-container { width: 100% !important; }
    .select2-container--default .select2-selection--multiple {
        min-height: 42px;
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        padding: 2px 6px;
    }
    .select2-container--default.select2-container--focus .select2-selection--multiple {
        border-color: #3b82f6;
        outline: 2px solid transparent;
        box-shadow: 0 0 0 1px #3b82f6;
    }
</style>
@endpush

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="bg-slate-700 px-6 py-4">
            <h2 class="text-xl font-semibold text-white">Module subscribers</h2>
        </div>
        <div class="p-6">
            <p class="text-sm text-gray-600 mb-6">
                Choose which staff get in-app notifications for each module. The navbar bell shows those alerts.
            </p>

            <div class="space-y-6">
                @foreach($modules as $moduleKey => $module)
                    <form method="POST" action="{{ route('admin.notification-settings.update') }}" class="rounded-lg border border-gray-200 p-5 space-y-4">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="module" value="{{ $moduleKey }}">

                        <div>
                            <h3 class="text-base font-semibold text-gray-900">{{ $module['label'] }}</h3>
                            <p class="mt-1 text-sm text-gray-500">{{ $module['description'] }}</p>
                        </div>

                        <div>
                            <label for="subscribers-{{ $moduleKey }}" class="block text-sm font-medium text-gray-700 mb-1">Notify these users</label>
                            <select id="subscribers-{{ $moduleKey }}"
                                    name="subscribers[{{ $moduleKey }}][]"
                                    multiple
                                    class="w-full js-subscriber-select"
                                    data-placeholder="Select staff to notify…">
                                @foreach($staffUsers as $user)
                                    <option value="{{ $user->id }}"
                                        @selected(in_array($user->id, $subscriberIdsByModule[$moduleKey] ?? [], true))>
                                        {{ $user->name }} ({{ $user->email }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="px-4 py-2 rounded-md bg-slate-700 text-white font-semibold">
                                Save {{ $module['label'] }}
                            </button>
                        </div>
                    </form>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script>
(function ($) {
    $(function () {
        $('.js-subscriber-select').each(function () {
            const $select = $(this);
            if ($select.data('select2')) {
                return;
            }
            $select.select2({
                placeholder: $select.data('placeholder') || 'Select staff…',
                width: '100%',
                allowClear: true
            });
        });
    });
})(jQuery);
</script>
@endpush
