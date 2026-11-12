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
    let history: string[] = [];
    let currentStepId: string | null = null;

    function setFlow(nextFlow: TestingFlow): void {
        flow = nextFlow;
        restart();
    }

    function restart(): void {
        answers = {};
        history = [];
        currentStepId = null;
        completeEl?.classList.add('hidden');
        root.classList.remove('hidden');
        showStep(String(flow.start || ''));
    }

    function pruneTerminalAnswers(): void {
        for (const stepId of Object.keys(answers)) {
            if (flow.steps?.[stepId]?.type === 'none') {
                delete answers[stepId];
            }
        }
    }

    function finish(status: string): void {
        completeEl?.classList.remove('hidden');
        if (finalStatusEl) {
            finalStatusEl.textContent = status;
        }
        root.innerHTML = '';
        root.classList.add('hidden');
        options.onComplete?.({ status, answers });
    }

    function currentStepHasInput(): boolean {
        const selected = root.querySelector<HTMLInputElement>('input[name="tw_answer"]:checked');
        const noteEl = root.querySelector<HTMLTextAreaElement>('[data-note]');
        const note = noteEl?.value.trim() ?? '';

        return Boolean(selected) || note !== '';
    }

    function goBack(): void {
        if (history.length === 0 || currentStepId === null) {
            return;
        }

        if (currentStepHasInput()) {
            const confirmed = window.confirm(
                'Leave this step? Your selection or note on this step will be discarded.',
            );
            if (!confirmed) {
                return;
            }
        }

        delete answers[currentStepId];
        const previousStepId = history.pop();
        if (!previousStepId) {
            return;
        }

        showStep(previousStepId);
    }

    function backFromComplete(): void {
        if (currentStepId === null) {
            return;
        }

        pruneTerminalAnswers();
        completeEl?.classList.add('hidden');
        root.classList.remove('hidden');

        if (history[history.length - 1] === currentStepId) {
            history.pop();
        }

        showStep(currentStepId);
    }

    function showStep(stepId: string): void {
        const step = flow.steps?.[stepId];
        if (!step) {
            root.innerHTML = `<p class="text-red-600">Step not found: ${escapeHtml(stepId)}</p>`;
            return;
        }

        if (step.type === 'none') {
            // Terminal outcomes jump straight to the complete panel so the
            // operator can pick/confirm Next status there (not a locked Confirm).
            answers[stepId] = { answer: null, note: '' };
            finish(step.status || 'Repair');
            return;
        }

        currentStepId = stepId;
        const saved = answers[stepId];

        let html = `<h3 class="text-base font-semibold text-gray-900 mb-3">${escapeHtml(step.question)}</h3>`;

        html += '<div class="space-y-2 mb-4">';
        (step.options ?? []).forEach((option, index) => {
            const inputId = `tw-${stepId}-${index}`;
            const checked = saved?.answer !== null && saved?.answer !== undefined && String(saved.answer) === String(option.key);
            html += '<label class="flex items-start gap-3 rounded-md border border-gray-200 px-3 py-2 hover:bg-slate-50 cursor-pointer">';
            html += `<input class="mt-1" type="radio" name="tw_answer" value="${escapeHtml(option.key)}" id="${inputId}"${checked ? ' checked' : ''}>`;
            html += `<span class="text-sm text-gray-800">${escapeHtml(option.text)}</span></label>`;
        });
        html += '</div>';

        if (step.note) {
            html += '<div class="mb-4"><label class="block text-sm font-medium text-gray-700 mb-1">Notes (optional)</label>';
            html += `<textarea class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" data-note rows="2">${escapeHtml(saved?.note ?? '')}</textarea></div>`;
        }

        html += '<div class="flex flex-wrap items-center gap-2">';
        if (history.length > 0) {
            html += '<button type="button" class="rounded-md bg-white border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50" data-back>Previous</button>';
        }
        html += `<button type="button" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-40" data-next${saved?.answer ? '' : ' disabled'}>Next</button>`;
        html += '</div>';
        root.innerHTML = html;

        const nextBtn = root.querySelector<HTMLButtonElement>('[data-next]');
        if (!nextBtn) {
            return;
        }

        root.querySelector('[data-back]')?.addEventListener('click', () => {
            goBack();
        });

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
            history.push(stepId);

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
        backFromComplete,
    };
}
