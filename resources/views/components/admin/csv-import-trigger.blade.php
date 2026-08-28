@props([
    'action',
    'modalTitle' => 'Import CSV',
    'modalId' => 'csv-import-modal',
])

<button
    type="button"
    data-csv-import-open
    data-csv-import-action="{{ $action }}"
    data-csv-import-title="{{ $modalTitle }}"
    data-csv-import-modal="{{ $modalId }}"
    {{ $attributes }}
>
    {{ $slot }}
</button>
