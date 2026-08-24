import type {
    TestingFlow,
    TestingWizard,
    TestingWizardOptions,
    WizardAnswers,
} from './types';

function escapeHtml(value: unknown): string {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

export function createTestingWizard(options: TestingWizardOptions): TestingWizard {
    const root = options.root;
    const completeEl = options.completeEl ?? null;
    const finalStatusEl = options.finalStatusEl ?? null;
    let flow = options.flow;
    let answers: WizardAnswers = {};

    function setFlow(nextFlow: TestingFlow): void {
        flow = nextFlow;
        restart();
    }

    function restart(): void {
        answers = {};
        completeEl?.classList.add('hidden');
        showStep(String(flow.start || ''));
    }

    function finish(status: string): void {
        completeEl?.classList.remove('hidden');
        if (finalStatusEl) {
            finalStatusEl.textContent = status;
        }
        root.innerHTML = '';
        options.onComplete?.({ status, answers });
    }

    function showStep(stepId: string): void {
        const step = flow.steps?.[stepId];
        if (!step) {
            root.innerHTML = `<p class="text-red-600">Step not found: ${escapeHtml(stepId)}</p>`;
            return;
        }

        let html = `<h3 class="text-base font-semibold text-gray-900 mb-3">${escapeHtml(step.question)}</h3>`;

        if (step.type === 'none') {
            html += `<p class="text-sm text-gray-600 mb-4">Confirm to finish with status <strong>${escapeHtml(step.status || '')}</strong>.</p>`;
            html += '<button type="button" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700" data-confirm-terminal>Confirm</button>';
            root.innerHTML = html;
            root.querySelector('[data-confirm-terminal]')?.addEventListener('click', () => {
                answers[stepId] = { answer: null, note: '' };
                finish(step.status || 'Repair');
            });
            return;
        }

        html += '<div class="space-y-2 mb-4">';
        (step.options ?? []).forEach((option, index) => {
            const inputId = `tw-${stepId}-${index}`;
            html += '<label class="flex items-start gap-3 rounded-md border border-gray-200 px-3 py-2 hover:bg-slate-50 cursor-pointer">';
            html += `<input class="mt-1" type="radio" name="tw_answer" value="${escapeHtml(option.key)}" id="${inputId}">`;
            html += `<span class="text-sm text-gray-800">${escapeHtml(option.text)}</span></label>`;
        });
        html += '</div>';

        if (step.note) {
            html += '<div class="mb-4"><label class="block text-sm font-medium text-gray-700 mb-1">Notes (optional)</label>';
            html += '<textarea class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" data-note rows="2"></textarea></div>';
        }

        html += '<button type="button" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-40" data-next disabled>Next</button>';
        root.innerHTML = html;

        const nextBtn = root.querySelector<HTMLButtonElement>('[data-next]');
        if (!nextBtn) {
            return;
        }

        root.querySelectorAll<HTMLInputElement>('input[name="tw_answer"]').forEach((input) => {
            input.addEventListener('change', () => {
                nextBtn.disabled = false;
            });
        });

        nextBtn.addEventListener('click', () => {
            const selected = root.querySelector<HTMLInputElement>('input[name="tw_answer"]:checked');
            if (!selected) {
                return;
            }

            const key = selected.value;
            const option = (step.options ?? []).find((item) => String(item.key) === String(key));
            if (!option) {
                return;
            }

            const noteEl = root.querySelector<HTMLTextAreaElement>('[data-note]');
            answers[stepId] = { answer: key, note: noteEl?.value ?? '' };

            if (!option.next) {
                finish(option.status || 'Repair');
                return;
            }

            showStep(String(option.next));
        });
    }

    restart();

    return {
        restart,
        setFlow,
        getAnswers: () => answers,
    };
}
