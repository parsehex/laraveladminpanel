@props([
    'dataTable',
    'sort' => null,
    'direction' => null,
])

@foreach($dataTable->columnsForView() as $column)
    @if($column['sortable'])
        <x-admin.data-table.sortable-th
            :column="$column['key']"
            :label="$column['label']"
            :align="$column['align']"
            :sort="$sort"
            :direction="$direction"
        />
    @else
        <x-admin.data-table.th
            :column="$column['key']"
            :label="$column['label']"
            :align="$column['align']"
        />
    @endif
@endforeach
