import type { TestingFlow } from '../testing/types';
import { createTestingWizard } from '../testing/wizard';

function parseFlow(raw: string | undefined): TestingFlow | null {
    if (!raw) {
        return null;
    }

    try {
        return JSON.parse(raw) as TestingFlow;
    } catch {
        return null;
    }
}

function parseStatusList(raw: string | undefined): string[] {
    if (!raw) {
        return [];
    }

    try {
        const parsed = JSON.parse(raw);
        return Array.isArray(parsed) ? parsed.map(String) : [];
    } catch {
        return [];
    }
}

const FAIL_OUTCOME_STATUSES = new Set(['Repair', 'Demanufacture']);

const pageRoot = document.getElementById('appliance-testing');
const wizardRoot = document.getElementById('wizard-root');
const restartBtn = document.getElementById('wizard-restart');
const resultingStatusRoot = document.querySelector(
    '[data-status-select="resulting-status"]',
) as HTMLElement | null;
const answersJson = document.getElementById('answers-json') as HTMLInputElement | null;
const resultingStatusHint = document.getElementById('resulting-status-hint');
const resultingStatusSuggested = document.getElementById('resulting-status-suggested');
const backFromCompleteBtn = document.getElementById('wizard-back-from-complete');

function setResultingStatusOptions(choices: string[], suggested: string): void {
    const alpine = (window as Window & { Alpine?: { $data: (el: HTMLElement) => { setOptions: (list: string[], selected?: string | null) => void } } }).Alpine;

    if (resultingStatusRoot && alpine) {
        alpine.$data(resultingStatusRoot).setOptions(choices, suggested || null);

        return;
    }

    resultingStatusRoot?.dispatchEvent(
        new CustomEvent('status-select-set', {
            bubbles: true,
            detail: {
                id: 'resulting-status',
                options: choices,
                value: suggested || '',
            },
        }),
    );
}

if (pageRoot && wizardRoot && restartBtn && resultingStatusRoot && answersJson) {
    const flow = parseFlow(pageRoot.dataset.flow);
    const passStatuses = parseStatusList(pageRoot.dataset.passStatuses);
    const failStatuses = parseStatusList(pageRoot.dataset.failStatuses);

    if (flow) {
        const wizard = createTestingWizard({
            root: wizardRoot,
            completeEl: document.getElementById('wizard-complete'),
            flow,
            onComplete: (result) => {
                const choices = FAIL_OUTCOME_STATUSES.has(result.status) ? failStatuses : passStatuses;
                const suggested = choices[0] ?? '';

                setResultingStatusOptions(choices, suggested);

                if (resultingStatusHint && resultingStatusSuggested) {
                    if (suggested) {
                        resultingStatusSuggested.textContent = suggested;
                        resultingStatusHint.classList.remove('hidden');
                    } else {
                        resultingStatusSuggested.textContent = '';
                        resultingStatusHint.classList.add('hidden');
                    }
                }

                answersJson.value = JSON.stringify(result.answers);
            },
        });

        restartBtn.addEventListener('click', () => {
            wizard.restart();
        });

        backFromCompleteBtn?.addEventListener('click', () => {
            wizard.backFromComplete();
        });
    }
}
