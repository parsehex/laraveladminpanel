<script>
(function () {
    if (typeof jQuery === 'undefined') {
        return;
    }

    const $ = jQuery;
    let partSearchTimer = null;
    const $partDescription = $('#part-description-input');
    const $partResults = $('#part-search-results');
    const $partId = $('#selected-part-id');
    const $partCost = $('#part-cost-input');
    const $partNumberPreview = $('#part-number-preview');

    if (! $partDescription.length) {
        return;
    }

    function hidePartResults() {
        $partResults.addClass('hidden').empty();
    }

    function clearSelectedPart() {
        $partId.val('');
        $partNumberPreview.val('Auto-generated after save');
    }

    $partDescription.on('input', function () {
        const query = $(this).val().trim();
        clearSelectedPart();
        clearTimeout(partSearchTimer);

        if (query.length < 2) {
            hidePartResults();
            return;
        }

        partSearchTimer = setTimeout(function () {
            $.getJSON(@json(route('admin.inventory.parts.search')), { q: query })
                .done(function (parts) {
                    $partResults.empty();

                    parts.forEach(function (part) {
                        const $row = $('<button type="button" class="block w-full px-3 py-2 text-left hover:bg-blue-50 border-b border-gray-100"></button>');
                        $row.append($('<div class="font-semibold text-gray-900"></div>').text(part.label));
                        $row.append($('<div class="text-gray-500"></div>').text('Cost: $' + Number(part.cost).toFixed(2) + ' | Stock: ' + part.stock));
                        $row.on('click', function () {
                            $partId.val(part.id);
                            $partDescription.val(part.description);
                            $partCost.val(Number(part.cost).toFixed(2));
                            $partNumberPreview.val(part.part_number);
                            hidePartResults();
                        });
                        $partResults.append($row);
                    });

                    $partResults.removeClass('hidden');
                });
        }, 250);
    });

    $(document).on('click', function (event) {
        if (! $(event.target).closest('#add-part-form').length) {
            hidePartResults();
        }
    });
})();
</script>
