@props([
    'id' => 'csv-import-modal',
    'exampleUrl' => null,
    'exampleLabel' => 'Download example CSV',
    'description' => null,
])

<div
    id="{{ $id }}"
    class="hidden fixed inset-0 z-50 bg-black/50 items-center justify-center p-6"
    data-csv-import-modal
    aria-hidden="true"
>
    <div class="bg-white rounded-lg shadow max-w-md w-full" role="dialog" aria-modal="true" aria-labelledby="{{ $id }}-title">
        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
            <h3 id="{{ $id }}-title" class="text-lg font-semibold text-gray-900" data-csv-import-title>Import CSV</h3>
            <button type="button" class="text-gray-500 hover:text-gray-700" data-csv-import-close aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form method="POST" enctype="multipart/form-data" class="space-y-4 px-6 py-5" data-csv-import-form>
            @csrf
            <div>
                <label for="{{ $id }}-file" class="block text-sm font-medium text-gray-700 mb-1">CSV file</label>
                <input
                    type="file"
                    id="{{ $id }}-file"
                    name="csv_file"
                    accept=".csv,text/csv"
                    required
                    class="block w-full text-sm text-gray-700 file:mr-3 file:rounded-md file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-blue-700 hover:file:bg-blue-100"
                >
            </div>

            @if($description)
                <p class="text-sm text-gray-500">{{ $description }}</p>
            @endif

            @if($exampleUrl)
                <a
                    href="{{ $exampleUrl }}"
                    class="inline-flex items-center text-sm font-semibold text-blue-600 hover:text-blue-800"
                    download
                >
                    <i class="fas fa-download mr-1"></i>{{ $exampleLabel }}
                </a>
            @endif

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600" data-csv-import-close>Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    <i class="fas fa-file-import mr-1"></i>Import
                </button>
            </div>
        </form>
    </div>
</div>

@once
@push('scripts')
<script>
    (function () {
        function openCsvImportModal($trigger) {
            const modalId = $trigger.data('csv-import-modal') || 'csv-import-modal';
            const $modal = $('#' + modalId);

            if (! $modal.length) {
                return;
            }

            const title = $trigger.data('csv-import-title') || 'Import CSV';
            const action = $trigger.data('csv-import-action');

            $modal.find('[data-csv-import-title]').text(title);
            $modal.find('[data-csv-import-form]').attr('action', action);
            $modal.find('input[type="file"]').val('');
            $modal.removeClass('hidden').addClass('flex').attr('aria-hidden', 'false');
        }

        function closeCsvImportModal($modal) {
            $modal.addClass('hidden').removeClass('flex').attr('aria-hidden', 'true');
            $modal.find('input[type="file"]').val('');
        }

        $(document).on('click', '[data-csv-import-open]', function () {
            openCsvImportModal($(this));
        });

        $(document).on('click', '[data-csv-import-close]', function () {
            closeCsvImportModal($(this).closest('[data-csv-import-modal]'));
        });

        $(document).on('click', '[data-csv-import-modal]', function (event) {
            if (event.target === this) {
                closeCsvImportModal($(this));
            }
        });
    })();
</script>
@endpush
@endonce
