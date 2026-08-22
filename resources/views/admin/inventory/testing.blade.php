@extends('layouts.admin')

@section('title', 'Testing · '.($appliance->unit_label ?: '#'.$appliance->id))
@section('page-title', 'Testing')

@section('page-actions')
    <a href="{{ route('admin.inventory.show', $appliance) }}" class="inline-flex items-center justify-center rounded-md bg-gray-500 px-3 py-2 text-sm font-semibold text-white hover:bg-gray-600">Back to unit</a>
@endsection

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <div class="bg-white rounded-lg shadow p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">
                    {{ $appliance->brand }} {{ $appliance->model?->model_number }}
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    {{ $appliance->category?->name ?: 'No category' }}
                    · Serial {{ $appliance->serial_number ?: '—' }}
                    · Label {{ $appliance->unit_label ?: '—' }}
                </p>
            </div>
            <span class="inline-flex rounded-full bg-sky-100 px-3 py-1 text-xs font-bold text-sky-800">Testing</span>
        </div>
    </div>

    @if(! $flow)
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-5 text-amber-950">
            <p class="font-semibold">No testing flow for this category.</p>
            <p class="mt-1 text-sm">
                Category “{{ $appliance->category?->name ?: 'unset' }}”
                @if($flowSlug)
                    mapped to slug <code>{{ $flowSlug }}</code>, but that flow file is missing.
                @else
                    could not be mapped to a flow slug.
                @endif
            </p>
            @canAccess('testing-flows.manage')
            <a href="{{ route('admin.testing-flows.index') }}" class="inline-flex mt-3 text-sm font-semibold text-amber-900 underline">Manage testing flows</a>
            @endcanAccess
        </div>
    @else
        <div class="bg-white rounded-lg shadow overflow-hidden"
             id="appliance-testing"
             data-flow='@json($flow)'>
            <div class="bg-blue-600 px-5 py-4 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-white">{{ $flow['name'] }} checklist</h2>
                    <p class="text-sm text-blue-100">Flow version v{{ $flow['version'] }}</p>
                </div>
                <button type="button" id="wizard-restart" class="rounded-md bg-white/10 px-3 py-1.5 text-sm font-semibold text-white hover:bg-white/20">Restart</button>
            </div>
            <div class="p-5 space-y-4">
                <div id="wizard-root" class="min-h-[10rem]"></div>
                <div id="wizard-complete" class="hidden space-y-4 rounded-md border border-emerald-200 bg-emerald-50 p-4">
                    <p class="font-semibold text-emerald-900">
                        Next status: <span id="wizard-final-status"></span>
                    </p>
                    <form method="POST" action="{{ route('admin.inventory.testing.store', $appliance) }}" id="testing-submit-form" class="space-y-3">
                        @csrf
                        <input type="hidden" name="resulting_status" id="resulting-status">
                        <input type="hidden" name="answers_json" id="answers-json">
                        <input type="hidden" name="flow_slug" value="{{ $flow['slug'] }}">
                        <input type="hidden" name="flow_version" value="{{ $flow['version'] }}">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Notes (optional)</label>
                            <textarea name="notes" rows="2" class="w-full rounded-md border border-gray-300 px-3 py-2"></textarea>
                        </div>
                        <button type="submit" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                            Complete testing
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@if($flow)
@push('scripts')
<script src="{{ asset('js/testing-wizard.js') }}"></script>
<script>
(function () {
    const root = document.getElementById('appliance-testing');
    const flow = JSON.parse(root.dataset.flow);
    const wizard = window.TestingWizard.create({
        root: document.getElementById('wizard-root'),
        completeEl: document.getElementById('wizard-complete'),
        finalStatusEl: document.getElementById('wizard-final-status'),
        flow: flow,
        onComplete: function (result) {
            document.getElementById('resulting-status').value = result.status;
            document.getElementById('answers-json').value = JSON.stringify(result.answers);
        },
    });
    document.getElementById('wizard-restart').addEventListener('click', function () {
        wizard.restart();
    });
})();
</script>
@endpush
@endif
