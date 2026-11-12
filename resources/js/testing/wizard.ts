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

function scrollFocusIntoView(target: HTMLElement | null): void {
    if (!target) {
        return;
    }

    // Wait a frame so layout reflects the newly rendered path/step before scrolling.
    requestAnimationFrame(() => {
        target.scrollIntoView({ behavior: 'smooth', block: 'start', inline: 'nearest' });
    });
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

    function answerLabel(stepId: string): string {
        const step = flow.steps?.[stepId];
        const saved = answers[stepId];
        if (!step || !saved || saved.answer === null || saved.answer === undefined) {
            return '—';
        }

        const option = (step.options ?? []).find((item) => String(item.key) === String(saved.answer));

        return option?.text ?? '—';
    }

    function renderPathHtml(): string {
        if (history.length === 0) {
            return '';
        }

        let html = '<div data-wizard-path class="mb-4 space-y-2">';
        html += '<p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Previous steps</p>';

        history.forEach((stepId, index) => {
            const step = flow.steps?.[stepId];
            const note = (answers[stepId]?.note ?? '').trim();

            html += '<div class="rounded-md border border-gray-200 bg-white px-3 py-2">';
            html += '<div class="flex items-start justify-between gap-3">';
            html += '<div class="min-w-0">';
            html += `<p class="text-xs text-gray-500">Step ${index + 1}</p>`;
            html += `<p class="text-sm font-medium text-gray-900">${escapeHtml(step?.question ?? stepId)}</p>`;
            html += `<p class="text-sm text-gray-700">${escapeHtml(answerLabel(stepId))}</p>`;
            if (note !== '') {
                html += `<p class="mt-1 text-xs text-gray-500 truncate">${escapeHtml(note)}</p>`;
            }
            html += '</div>';
            html += `<button type="button" class="shrink-0 text-sm font-semibold text-blue-700 hover:text-blue-900" data-edit-step="${escapeHtml(stepId)}">Edit</button>`;
            html += '</div></div>';
        });

        html += '</div>';

        return html;
    }

    function bindPathEditHandlers(): void {
        root.querySelectorAll<HTMLButtonElement>('[data-edit-step]').forEach((button) => {
            button.addEventListener('click', () => {
                const stepId = button.dataset.editStep;
                if (!stepId) {
                    return;
                }

                jumpTo(stepId);
            });
        });
    }

    /**
     * Rewind to a completed step. Clears later path answers; keeps the target's answer for prefill.
     */
    function jumpTo(targetStepId: string, opts: { skipConfirm?: boolean } = {}): void {
        const index = history.indexOf(targetStepId);
        if (index === -1) {
            return;
        }

        if (!opts.skipConfirm) {
            const confirmed = window.confirm(
                'Change this answer? Later steps will be cleared.',
            );
            if (!confirmed) {
                return;
            }
        }

        for (const stepId of history.slice(index + 1)) {
            delete answers[stepId];
        }

        if (currentStepId && currentStepId !== targetStepId) {
            delete answers[currentStepId];
        }

        pruneTerminalAnswers();
        history = history.slice(0, index);
        completeEl?.classList.add('hidden');
        root.classList.remove('hidden');
        showStep(targetStepId);
    }

    function finish(status: string): void {
        completeEl?.classList.remove('hidden');
        if (finalStatusEl) {
            finalStatusEl.textContent = status;
        }

        // Keep the collapsed path visible above the complete panel; hide the active step card.
        root.classList.remove('hidden');
        root.innerHTML = `${renderPathHtml()}<div data-wizard-step class="hidden"></div>`;
        bindPathEditHandlers();
        options.onComplete?.({ status, answers });
        scrollFocusIntoView(completeEl ?? root);
    }

    function currentStepHasInput(): boolean {
        const stepCard = root.querySelector<HTMLElement>('[data-wizard-step]') ?? root;
        const selected = stepCard.querySelector<HTMLInputElement>('input[name="tw_answer"]:checked');
        const noteEl = stepCard.querySelector<HTMLTextAreaElement>('[data-note]');
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

        jumpTo(history[history.length - 1], { skipConfirm: true });
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

        let stepHtml = `<h3 class="text-base font-semibold text-gray-900 mb-3">${escapeHtml(step.question)}</h3>`;

        stepHtml += '<div class="space-y-2 mb-4">';
        (step.options ?? []).forEach((option, index) => {
            const inputId = `tw-${stepId}-${index}`;
            const checked = saved?.answer !== null && saved?.answer !== undefined && String(saved.answer) === String(option.key);
            stepHtml += '<label class="flex items-start gap-3 rounded-md border border-gray-200 px-3 py-2 hover:bg-slate-50 cursor-pointer">';
            stepHtml += `<input class="mt-1" type="radio" name="tw_answer" value="${escapeHtml(option.key)}" id="${inputId}"${checked ? ' checked' : ''}>`;
            stepHtml += `<span class="text-sm text-gray-800">${escapeHtml(option.text)}</span></label>`;
        });
        stepHtml += '</div>';

        if (step.note) {
            stepHtml += '<div class="mb-4"><label class="block text-sm font-medium text-gray-700 mb-1">Notes (optional)</label>';
            stepHtml += `<textarea class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" data-note rows="2">${escapeHtml(saved?.note ?? '')}</textarea></div>`;
        }

        stepHtml += '<div class="flex flex-wrap items-center gap-2">';
        if (history.length > 0) {
            stepHtml += '<button type="button" class="rounded-md bg-white border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50" data-back>Previous</button>';
        }
        stepHtml += `<button type="button" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-40" data-next${saved?.answer ? '' : ' disabled'}>Next</button>`;
        stepHtml += '</div>';

        root.innerHTML = `${renderPathHtml()}<div data-wizard-step>${stepHtml}</div>`;
        bindPathEditHandlers();

        const stepCard = root.querySelector<HTMLElement>('[data-wizard-step]');
        if (!stepCard) {
            return;
        }

        scrollFocusIntoView(stepCard);

        const nextBtn = stepCard.querySelector<HTMLButtonElement>('[data-next]');
        if (!nextBtn) {
            return;
        }

        stepCard.querySelector('[data-back]')?.addEventListener('click', () => {
            goBack();
        });

        stepCard.querySelectorAll<HTMLInputElement>('input[name="tw_answer"]').forEach((input) => {
            input.addEventListener('change', () => {
                nextBtn.disabled = false;
            });
        });

        nextBtn.addEventListener('click', () => {
            const selected = stepCard.querySelector<HTMLInputElement>('input[name="tw_answer"]:checked');
            if (!selected) {
                return;
            }

            const key = selected.value;
            const option = (step.options ?? []).find((item) => String(item.key) === String(key));
            if (!option) {
                return;
            }

            const noteEl = stepCard.querySelector<HTMLTextAreaElement>('[data-note]');
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
