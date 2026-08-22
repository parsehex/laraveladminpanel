(function () {
    const editorRoot = document.getElementById('flow-editor');
    if (!editorRoot || !window.TestingWizard) {
        return;
    }

    const flow = JSON.parse(editorRoot.dataset.flow);
    const statuses = JSON.parse(editorRoot.dataset.statuses || '[]');
    const stepsEditor = document.getElementById('steps-editor');
    const startSelect = document.getElementById('flow-start');
    const nameInput = document.getElementById('flow-name');
    const jsonInput = document.getElementById('flow-json-input');
    const form = document.getElementById('flow-edit-form');

    let steps = JSON.parse(JSON.stringify(flow.steps || {}));
    let previewWizard = null;

    function stepIds() {
        return Object.keys(steps);
    }

    function escapeAttr(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;');
    }

    function statusOptionsHtml(selected) {
        return statuses.map(function (status) {
            return '<option value="' + escapeAttr(status) + '"' + (status === selected ? ' selected' : '') + '>' + escapeAttr(status) + '</option>';
        }).join('');
    }

    function nextOptionsHtml(selected) {
        let html = '<option value="">End → set status</option>';
        stepIds().forEach(function (id) {
            html += '<option value="' + escapeAttr(id) + '"' + (String(selected || '') === id ? ' selected' : '') + '>' + escapeAttr(id) + '</option>';
        });
        return html;
    }

    function refreshStartOptions() {
        const current = startSelect.value || flow.start;
        startSelect.innerHTML = stepIds().map(function (id) {
            return '<option value="' + escapeAttr(id) + '"' + (id === current ? ' selected' : '') + '>' + escapeAttr(id) + '</option>';
        }).join('');
        if (!startSelect.value && stepIds()[0]) {
            startSelect.value = stepIds()[0];
        }
    }

    function readDom() {
        const next = {};
        stepsEditor.querySelectorAll('[data-step-card]').forEach(function (card) {
            const previousId = card.dataset.stepId;
            let id = (card.querySelector('[data-field="id"]').value || previousId).trim() || previousId;
            while (next[id] && id !== previousId) {
                id += '_2';
            }

            const type = card.querySelector('[data-field="type"]').value === 'none' ? 'none' : 'radio';
            const step = {
                id: id,
                question: card.querySelector('[data-field="question"]').value,
                type: type,
                note: card.querySelector('[data-field="note"]').checked,
            };

            if (type === 'none') {
                step.status = card.querySelector('[data-field="status"]').value || null;
                step.next = null;
                step.options = [];
            } else {
                step.options = [];
                card.querySelectorAll('[data-option-row]').forEach(function (row) {
                    const nextStep = row.querySelector('[data-opt="next"]').value || null;
                    step.options.push({
                        key: row.querySelector('[data-opt="key"]').value,
                        text: row.querySelector('[data-opt="text"]').value,
                        next: nextStep,
                        status: nextStep ? null : (row.querySelector('[data-opt="status"]').value || null),
                    });
                });
            }

            next[id] = step;
        });
        steps = next;
    }

    function buildPayload() {
        readDom();
        return {
            slug: flow.slug,
            name: nameInput.value,
            version: flow.version || 1,
            updated_at: flow.updated_at || null,
            start: startSelect.value,
            steps: steps,
        };
    }

    function syncPreview() {
        const data = buildPayload();
        if (!previewWizard) {
            previewWizard = window.TestingWizard.create({
                root: document.getElementById('wizard-root'),
                completeEl: document.getElementById('wizard-complete'),
                finalStatusEl: document.getElementById('wizard-final-status'),
                flow: data,
            });
        } else {
            previewWizard.setFlow(data);
        }
    }

    function createOptionRow(option) {
        const row = document.createElement('div');
        row.className = 'grid grid-cols-1 md:grid-cols-12 gap-2 rounded-md bg-slate-50 p-2';
        row.dataset.optionRow = '1';
        row.innerHTML =
            '<div class="md:col-span-2"><input data-opt="key" placeholder="Key" value="' + escapeAttr(option.key || '') + '" class="w-full rounded-md border border-gray-300 px-2 py-1.5 text-sm"></div>' +
            '<div class="md:col-span-4"><input data-opt="text" placeholder="Label" value="' + escapeAttr(option.text || '') + '" class="w-full rounded-md border border-gray-300 px-2 py-1.5 text-sm"></div>' +
            '<div class="md:col-span-3"><select data-opt="next" class="w-full rounded-md border border-gray-300 px-2 py-1.5 text-sm">' + nextOptionsHtml(option.next) + '</select></div>' +
            '<div class="md:col-span-2"><select data-opt="status" class="w-full rounded-md border border-gray-300 px-2 py-1.5 text-sm"><option value="">Status</option>' + statusOptionsHtml(option.status || '') + '</select></div>' +
            '<div class="md:col-span-1 flex items-center justify-end"><button type="button" data-remove-option class="text-red-600 text-sm">✕</button></div>';
        return row;
    }

    function bindCard(card) {
        card.querySelector('[data-field="type"]').addEventListener('change', function (event) {
            const terminal = event.target.value === 'none';
            card.querySelector('[data-terminal-wrap]').classList.toggle('hidden', !terminal);
            card.querySelector('[data-options-wrap]').classList.toggle('hidden', terminal);
            readDom();
            render();
        });

        card.querySelector('[data-remove-step]').addEventListener('click', function () {
            if (stepIds().length <= 1) {
                alert('A flow needs at least one step.');
                return;
            }
            readDom();
            delete steps[card.dataset.stepId];
            render();
        });

        const addOption = card.querySelector('[data-add-option]');
        if (addOption) {
            addOption.addEventListener('click', function () {
                readDom();
                const id = card.dataset.stepId;
                steps[id].options = steps[id].options || [];
                steps[id].options.push({
                    key: 'opt' + (steps[id].options.length + 1),
                    text: 'New option',
                    next: null,
                    status: statuses[0] || 'Ready',
                });
                render();
            });
        }

        card.querySelectorAll('[data-remove-option]').forEach(function (button) {
            button.addEventListener('click', function () {
                readDom();
                const id = card.dataset.stepId;
                const rows = Array.from(card.querySelectorAll('[data-option-row]'));
                const index = rows.indexOf(button.closest('[data-option-row]'));
                if (index >= 0) {
                    steps[id].options.splice(index, 1);
                }
                render();
            });
        });

        card.querySelectorAll('input, select, textarea').forEach(function (input) {
            input.addEventListener('change', function () {
                readDom();
                if (input.dataset.field === 'id') {
                    render();
                } else {
                    syncPreview();
                }
            });
        });
    }

    function render() {
        refreshStartOptions();
        stepsEditor.innerHTML = '';

        stepIds().forEach(function (id) {
            const step = steps[id];
            const card = document.createElement('div');
            card.className = 'rounded-lg border border-gray-200 p-4 space-y-3';
            card.dataset.stepCard = '1';
            card.dataset.stepId = id;

            card.innerHTML =
                '<div class="flex flex-wrap items-center gap-2 justify-between">' +
                '<div class="flex items-center gap-2"><label class="text-xs font-semibold uppercase text-gray-500">Step id</label>' +
                '<input data-field="id" value="' + escapeAttr(id) + '" class="rounded-md border border-gray-300 px-2 py-1 text-sm font-mono w-40"></div>' +
                '<button type="button" data-remove-step class="text-sm text-red-600 hover:text-red-800">Remove</button></div>' +
                '<div><label class="block text-sm font-medium text-gray-700 mb-1">Question</label>' +
                '<input data-field="question" value="' + escapeAttr(step.question || '') + '" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm"></div>' +
                '<div class="grid grid-cols-1 md:grid-cols-3 gap-3">' +
                '<div><label class="block text-sm font-medium text-gray-700 mb-1">Type</label>' +
                '<select data-field="type" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">' +
                '<option value="radio"' + (step.type !== 'none' ? ' selected' : '') + '>Radio</option>' +
                '<option value="none"' + (step.type === 'none' ? ' selected' : '') + '>Terminal</option>' +
                '</select></div>' +
                '<div class="flex items-end pb-2"><label class="inline-flex items-center gap-2 text-sm text-gray-700">' +
                '<input type="checkbox" data-field="note"' + (step.note ? ' checked' : '') + '> Allow note</label></div>' +
                '<div data-terminal-wrap class="' + (step.type === 'none' ? '' : 'hidden') + '">' +
                '<label class="block text-sm font-medium text-gray-700 mb-1">Terminal status</label>' +
                '<select data-field="status" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">' + statusOptionsHtml(step.status || '') + '</select>' +
                '</div></div>' +
                '<div data-options-wrap class="' + (step.type === 'none' ? 'hidden' : '') + ' space-y-2">' +
                '<div class="flex items-center justify-between"><h4 class="text-sm font-semibold text-gray-800">Options</h4>' +
                '<button type="button" data-add-option class="text-sm text-blue-600 hover:text-blue-800">Add option</button></div>' +
                '<div data-options class="space-y-2"></div></div>';

            const optionsWrap = card.querySelector('[data-options]');
            (step.options || []).forEach(function (option) {
                optionsWrap.appendChild(createOptionRow(option));
            });

            bindCard(card);
            stepsEditor.appendChild(card);
        });

        syncPreview();
    }

    document.getElementById('add-step').addEventListener('click', function () {
        readDom();
        let id = 'step_' + (stepIds().length + 1);
        while (steps[id]) {
            id += '_x';
        }
        steps[id] = {
            id: id,
            question: 'New question',
            type: 'radio',
            note: false,
            options: [{ key: 'yes', text: 'Yes', next: null, status: statuses[0] || 'Ready' }],
        };
        render();
    });

    document.getElementById('preview-reset').addEventListener('click', function () {
        syncPreview();
    });

    form.addEventListener('submit', function () {
        jsonInput.value = JSON.stringify(buildPayload());
    });

    startSelect.value = flow.start;
    render();
})();
