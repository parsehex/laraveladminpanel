@once
@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css">
<style>
    .select2-container {
        width: 100% !important;
    }

    .select2-container--default .select2-selection--single {
        height: 42px;
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        padding-left: 0.75rem;
        padding-right: 2rem;
        line-height: 40px;
        color: #111827;
    }

    .select2-container--default .select2-selection--single .select2-selection__placeholder {
        color: #6b7280;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 40px;
        right: 0.5rem;
    }

    .select2-container--default.select2-container--focus .select2-selection--single,
    .select2-container--default.select2-container--open .select2-selection--single {
        border-color: #3b82f6;
        outline: 2px solid transparent;
        box-shadow: 0 0 0 1px #3b82f6;
    }

    .select2-dropdown {
        border-color: #d1d5db;
    }

    .select2-search--dropdown .select2-search__field {
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        padding: 0.5rem 0.75rem;
        outline: none;
    }
</style>
@endpush

<div id="quick-create-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-40 px-4">
    <div class="w-full max-w-md rounded-lg bg-white shadow-lg">
        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
            <h3 id="quick-create-title" class="text-lg font-semibold text-gray-900">Add Item</h3>
            <button type="button" class="text-gray-500 hover:text-gray-700" data-close-quick-create>
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="quick-create-form" class="space-y-4 px-6 py-5">
            <div>
                <label id="quick-create-label" for="quick-create-name" class="mb-2 block text-sm font-medium text-gray-700">Name</label>
                <input type="text" id="quick-create-name" class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500">
                <p id="quick-create-error" class="mt-1 hidden text-sm text-red-600"></p>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" class="rounded-md bg-gray-500 px-4 py-2 text-white hover:bg-gray-600" data-close-quick-create>Cancel</button>
                <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Save</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script>
    (function () {
        const endpoints = {
            category: {
                list: '{{ route('admin.dropdowns.categories') }}',
                store: '{{ route('admin.dropdowns.categories.store') }}',
                title: 'Add Category',
                label: 'Category Name',
                field: 'name',
                placeholder: 'Search categories...'
            },
            model: {
                list: '{{ route('admin.dropdowns.models') }}',
                store: '{{ route('admin.dropdowns.models.store') }}',
                title: 'Add Model',
                label: 'Model Name',
                field: 'model_number',
                placeholder: 'Search models...'
            },
            brand: {
                list: '{{ route('admin.dropdowns.brands') }}',
                store: '{{ route('admin.dropdowns.brands.store') }}',
                title: 'Add Brand',
                label: 'Brand Name',
                field: 'name',
                placeholder: 'Search brands...'
            },
            kit_part: {
                list: '{{ route('admin.dropdowns.kit-parts') }}',
                store: '{{ route('admin.dropdowns.kit-parts.store') }}',
                title: 'Add Part',
                label: 'Part Name',
                field: 'part_name',
                placeholder: 'Search parts...'
            }
        };

        let quickCreateTarget = null;
        let quickCreateType = null;

        function optionIdForSelect($select, item) {
            if ($select.data('valueField') === 'id' && item.id) {
                return item.id;
            }

            return item.value || item.id;
        }

        function addOption($select, item, selected) {
            const optionId = optionIdForSelect($select, item);
            const exists = $select.find('option').filter(function () {
                return String($(this).val()) === String(optionId);
            }).length > 0;

            if (! exists) {
                const option = new Option(item.text, optionId, false, false);
                if (item.stock !== undefined) {
                    $(option).attr('data-stock', item.stock);
                }
                $select.append(option);
            }

            if (selected) {
                $select.val(optionId).trigger('change');
                $select.trigger({
                    type: 'select2:select',
                    params: {
                        data: item
                    }
                });
            }
        }

        function initializeAjaxDropdowns() {
            $('[data-ajax-dropdown]').each(function () {
                const $select = $(this);

                if ($select.data('ajaxReady')) {
                    return;
                }

                const type = $select.data('ajaxDropdown');
                $select.data('ajaxReady', true);

                $select.select2({
                    ajax: {
                        url: endpoints[type].list,
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return {
                                q: params.term || '',
                                page: params.page || 1,
                                value_field: $select.data('valueField') || ''
                            };
                        },
                        processResults: function (response) {
                            return {
                                results: response.data,
                                pagination: {
                                    more: !!response.next_page
                                }
                            };
                        }
                    },
                    allowClear: true,
                    placeholder: $select.find('option[value=""]').text() || endpoints[type].placeholder,
                    width: '100%'
                });
            });
        }

        window.initializeAjaxDropdowns = initializeAjaxDropdowns;

        $(document).on('click', '[data-open-quick-create]', function () {
            quickCreateType = $(this).data('openQuickCreate');
            quickCreateTarget = $(this).data('target')
                ? $($(this).data('target'))
                : $(this).closest('[data-quick-create-wrapper]').find('[data-ajax-dropdown]').first();

            $('#quick-create-title').text(endpoints[quickCreateType].title);
            $('#quick-create-label').text(endpoints[quickCreateType].label);
            $('#quick-create-name').val('').attr('name', endpoints[quickCreateType].field).trigger('focus');
            $('#quick-create-error').addClass('hidden').text('');
            $('#quick-create-modal').removeClass('hidden').addClass('flex');
        });

        $(document).on('click', '[data-close-quick-create]', function () {
            $('#quick-create-modal').addClass('hidden').removeClass('flex');
        });

        $('#quick-create-form').on('submit', function (event) {
            event.preventDefault();

            const config = endpoints[quickCreateType];
            const payload = {};
            payload[config.field] = $('#quick-create-name').val();

            $.ajax({
                url: config.store,
                method: 'POST',
                data: payload,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json'
                }
            }).done(function (response) {
                $('[data-ajax-dropdown="' + quickCreateType + '"]').each(function () {
                    addOption($(this), response.item, this === quickCreateTarget.get(0));
                });

                $('#quick-create-modal').addClass('hidden').removeClass('flex');
                toastr.success(response.message || 'Saved successfully.');
            }).fail(function (xhr) {
                const errors = xhr.responseJSON && xhr.responseJSON.errors;
                const message = errors ? Object.values(errors)[0][0] : (xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Unable to save.');
                $('#quick-create-error').removeClass('hidden').text(message);
            });
        });

        $(initializeAjaxDropdowns);
    })();
</script>
@endpush
@endonce
