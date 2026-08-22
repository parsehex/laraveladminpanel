import type { FlowOption, FlowStep, TestingFlow, TestingWizard } from './types';
import { createTestingWizard } from './wizard';

function parseJsonAttr<T>(value: string | undefined, fallback: T): T {
    if (!value) {
        return fallback;
    }

    try {
        return JSON.parse(value) as T;
    } catch {
        return fallback;
    }
}

function escapeAttr(value: unknown): string {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;');
}

export function bootTestingFlowEditor(): void {
    const editorRoot = document.getElementById('flow-editor');
    const stepsEditor = document.getElementById('steps-editor');
    const startSelect = document.getElementById('flow-start') as HTMLSelectElement | null;
    const nameInput = document.getElementById('flow-name') as HTMLInputElement | null;
    const jsonInput = document.getElementById('flow-json-input') as HTMLInputElement | null;
    const form = document.getElementById('flow-edit-form') as HTMLFormElement | null;
    const addStepBtn = document.getElementById('add-step');
    const previewResetBtn = document.getElementById('preview-reset');
    const wizardRoot = document.getElementById('wizard-root');

    if (
        !editorRoot ||
        !stepsEditor ||
        !startSelect ||
        !nameInput ||
        !jsonInput ||
        !form ||
        !addStepBtn ||
        !previewResetBtn ||
        !wizardRoot
    ) {
        return;
    }

    // Narrowed aliases so nested functions stay strict-null-safe.
    const rootEl = editorRoot;
    const stepsEl = stepsEditor;
    const startEl = startSelect;
    const nameEl = nameInput;
    const jsonEl = jsonInput;
    const formEl = form;
    const addBtn = addStepBtn;
    const resetBtn = previewResetBtn;
    const wizardEl = wizardRoot;

    const flow = parseJsonAttr<TestingFlow>(rootEl.dataset.flow, {
        slug: '',
        name: '',
        version: 1,
        updated_at: null,
        start: '',
        steps: {},
    });
    const statuses = parseJsonAttr<string[]>(rootEl.dataset.statuses, []);

    let steps: Record<string, FlowStep> = structuredClone(flow.steps ?? {});
    let previewWizard: TestingWizard | null = null;

    const stepIds = (): string[] => Object.keys(steps);

    function statusOptionsHtml(selected: string): string {
        return statuses
            .map(
                (status) =>
                    `<option value="${escapeAttr(status)}"${status === selected ? ' selected' : ''}>${escapeAttr(status)}</option>`,
            )
            .join('');
    }

    function nextOptionsHtml(selected: string | null | undefined): string {
        let html = '<option value="">End → set status</option>';
        for (const id of stepIds()) {
            html += `<option value="${escapeAttr(id)}"${String(selected || '') === id ? ' selected' : ''}>${escapeAttr(id)}</option>`;
        }
        return html;
    }

    function refreshStartOptions(): void {
        const current = startEl.value || flow.start;
        startEl.innerHTML = stepIds()
            .map(
                (id) =>
                    `<option value="${escapeAttr(id)}"${id === current ? ' selected' : ''}>${escapeAttr(id)}</option>`,
            )
            .join('');
        if (!startEl.value && stepIds()[0]) {
            startEl.value = stepIds()[0];
        }
    }

    function readDom(): void {
        const next: Record<string, FlowStep> = {};

        stepsEl.querySelectorAll<HTMLElement>('[data-step-card]').forEach((card) => {
            const previousId = card.dataset.stepId ?? '';
            const idInput = card.querySelector<HTMLInputElement>('[data-field="id"]');
            let id = (idInput?.value || previousId).trim() || previousId;
            while (next[id] && id !== previousId) {
                id += '_2';
            }

            const typeSelect = card.querySelector<HTMLSelectElement>('[data-field="type"]');
            const type = typeSelect?.value === 'none' ? 'none' : 'radio';
            const question = card.querySelector<HTMLInputElement>('[data-field="question"]')?.value ?? '';
            const note = card.querySelector<HTMLInputElement>('[data-field="note"]')?.checked ?? false;

            const step: FlowStep = { id, question, type, note };

            if (type === 'none') {
                step.status = card.querySelector<HTMLSelectElement>('[data-field="status"]')?.value || null;
                step.next = null;
                step.options = [];
            } else {
                step.options = [];
                card.querySelectorAll<HTMLElement>('[data-option-row]').forEach((row) => {
                    const nextStep = row.querySelector<HTMLSelectElement>('[data-opt="next"]')?.value || null;
                    step.options!.push({
                        key: row.querySelector<HTMLInputElement>('[data-opt="key"]')?.value ?? '',
                        text: row.querySelector<HTMLInputElement>('[data-opt="text"]')?.value ?? '',
                        next: nextStep,
                        status: nextStep
                            ? null
                            : row.querySelector<HTMLSelectElement>('[data-opt="status"]')?.value || null,
                    });
                });
            }

            next[id] = step;
        });

        steps = next;
    }

    function buildPayload(): TestingFlow {
        readDom();
        return {
            slug: flow.slug,
            name: nameEl.value,
            version: flow.version || 1,
            updated_at: flow.updated_at || null,
            start: startEl.value,
            steps,
        };
    }

    function syncPreview(): void {
        const data = buildPayload();
        if (!previewWizard) {
            previewWizard = createTestingWizard({
                root: wizardEl,
                completeEl: document.getElementById('wizard-complete'),
                finalStatusEl: document.getElementById('wizard-final-status'),
                flow: data,
            });
        } else {
            previewWizard.setFlow(data);
        }
    }

    function createOptionRow(option: FlowOption): HTMLElement {
        const row = document.createElement('div');
        row.className = 'grid grid-cols-1 md:grid-cols-12 gap-2 rounded-md bg-slate-50 p-2';
        row.dataset.optionRow = '1';
        row.innerHTML =
            `<div class="md:col-span-2"><input data-opt="key" placeholder="Key" value="${escapeAttr(option.key || '')}" class="w-full rounded-md border border-gray-300 px-2 py-1.5 text-sm"></div>` +
            `<div class="md:col-span-4"><input data-opt="text" placeholder="Label" value="${escapeAttr(option.text || '')}" class="w-full rounded-md border border-gray-300 px-2 py-1.5 text-sm"></div>` +
            `<div class="md:col-span-3"><select data-opt="next" class="w-full rounded-md border border-gray-300 px-2 py-1.5 text-sm">${nextOptionsHtml(option.next)}</select></div>` +
            `<div class="md:col-span-2"><select data-opt="status" class="w-full rounded-md border border-gray-300 px-2 py-1.5 text-sm"><option value="">Status</option>${statusOptionsHtml(option.status || '')}</select></div>` +
            '<div class="md:col-span-1 flex items-center justify-end"><button type="button" data-remove-option class="text-red-600 text-sm">✕</button></div>';
        return row;
    }

    function bindCard(card: HTMLElement): void {
        card.querySelector('[data-field="type"]')?.addEventListener('change', (event) => {
            const target = event.target as HTMLSelectElement;
            const terminal = target.value === 'none';
            card.querySelector('[data-terminal-wrap]')?.classList.toggle('hidden', !terminal);
            card.querySelector('[data-options-wrap]')?.classList.toggle('hidden', terminal);
            readDom();
            render();
        });

        card.querySelector('[data-remove-step]')?.addEventListener('click', () => {
            if (stepIds().length <= 1) {
                alert('A flow needs at least one step.');
                return;
            }
            readDom();
            const id = card.dataset.stepId;
            if (id) {
                delete steps[id];
            }
            render();
        });

        card.querySelector('[data-add-option]')?.addEventListener('click', () => {
            readDom();
            const id = card.dataset.stepId;
            if (!id || !steps[id]) {
                return;
            }
            steps[id].options = steps[id].options ?? [];
            steps[id].options.push({
                key: `opt${steps[id].options.length + 1}`,
                text: 'New option',
                next: null,
                status: statuses[0] || 'Ready',
            });
            render();
        });

        card.querySelectorAll('[data-remove-option]').forEach((button) => {
            button.addEventListener('click', () => {
                readDom();
                const id = card.dataset.stepId;
                if (!id || !steps[id]?.options) {
                    return;
                }
                const rows = Array.from(card.querySelectorAll('[data-option-row]'));
                const row = (button as HTMLElement).closest('[data-option-row]');
                const index = row ? rows.indexOf(row) : -1;
                if (index >= 0) {
                    steps[id].options!.splice(index, 1);
                }
                render();
            });
        });

        card.querySelectorAll('input, select, textarea').forEach((input) => {
            input.addEventListener('change', () => {
                readDom();
                if ((input as HTMLElement).dataset.field === 'id') {
                    render();
                } else {
                    syncPreview();
                }
            });
        });
    }

    function render(): void {
        refreshStartOptions();
        stepsEl.innerHTML = '';

        for (const id of stepIds()) {
            const step = steps[id];
            const card = document.createElement('div');
            card.className = 'rounded-lg border border-gray-200 p-4 space-y-3';
            card.dataset.stepCard = '1';
            card.dataset.stepId = id;

            card.innerHTML =
                '<div class="flex flex-wrap items-center gap-2 justify-between">' +
                '<div class="flex items-center gap-2"><label class="text-xs font-semibold uppercase text-gray-500">Step id</label>' +
                `<input data-field="id" value="${escapeAttr(id)}" class="rounded-md border border-gray-300 px-2 py-1 text-sm font-mono w-40"></div>` +
                '<button type="button" data-remove-step class="text-sm text-red-600 hover:text-red-800">Remove</button></div>' +
                '<div><label class="block text-sm font-medium text-gray-700 mb-1">Question</label>' +
                `<input data-field="question" value="${escapeAttr(step.question || '')}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm"></div>` +
                '<div class="grid grid-cols-1 md:grid-cols-3 gap-3">' +
                '<div><label class="block text-sm font-medium text-gray-700 mb-1">Type</label>' +
                '<select data-field="type" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">' +
                `<option value="radio"${step.type !== 'none' ? ' selected' : ''}>Radio</option>` +
                `<option value="none"${step.type === 'none' ? ' selected' : ''}>Terminal</option>` +
                '</select></div>' +
                '<div class="flex items-end pb-2"><label class="inline-flex items-center gap-2 text-sm text-gray-700">' +
                `<input type="checkbox" data-field="note"${step.note ? ' checked' : ''}> Allow note</label></div>` +
                `<div data-terminal-wrap class="${step.type === 'none' ? '' : 'hidden'}">` +
                '<label class="block text-sm font-medium text-gray-700 mb-1">Terminal status</label>' +
                `<select data-field="status" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">${statusOptionsHtml(step.status || '')}</select>` +
                '</div></div>' +
                `<div data-options-wrap class="${step.type === 'none' ? 'hidden' : ''} space-y-2">` +
                '<div class="flex items-center justify-between"><h4 class="text-sm font-semibold text-gray-800">Options</h4>' +
                '<button type="button" data-add-option class="text-sm text-blue-600 hover:text-blue-800">Add option</button></div>' +
                '<div data-options class="space-y-2"></div></div>';

            const optionsWrap = card.querySelector('[data-options]');
            (step.options ?? []).forEach((option) => {
                optionsWrap?.appendChild(createOptionRow(option));
            });

            bindCard(card);
            stepsEl.appendChild(card);
        }

        syncPreview();
    }

    addBtn.addEventListener('click', () => {
        readDom();
        let id = `step_${stepIds().length + 1}`;
        while (steps[id]) {
            id += '_x';
        }
        steps[id] = {
            id,
            question: 'New question',
            type: 'radio',
            note: false,
            options: [{ key: 'yes', text: 'Yes', next: null, status: statuses[0] || 'Ready' }],
        };
        render();
    });

    resetBtn.addEventListener('click', () => {
        syncPreview();
    });

    formEl.addEventListener('submit', () => {
        jsonEl.value = JSON.stringify(buildPayload());
    });

    startEl.value = flow.start;
    render();
}
