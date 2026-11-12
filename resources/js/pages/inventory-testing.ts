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
const resultingStatus = document.getElementById('resulting-status') as HTMLSelectElement | null;
const answersJson = document.getElementById('answers-json') as HTMLInputElement | null;
const resultingStatusHint = document.getElementById('resulting-status-hint');
const resultingStatusSuggested = document.getElementById('resulting-status-suggested');
const backFromCompleteBtn = document.getElementById('wizard-back-from-complete');

if (pageRoot && wizardRoot && restartBtn && resultingStatus && answersJson) {
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

                resultingStatus.innerHTML = '';
                for (const status of choices) {
                    resultingStatus.add(new Option(status, status));
                }

                if (suggested) {
                    resultingStatus.value = suggested;
                }

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
