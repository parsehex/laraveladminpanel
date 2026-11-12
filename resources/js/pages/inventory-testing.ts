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
    if (flow) {
        const wizard = createTestingWizard({
            root: wizardRoot,
            completeEl: document.getElementById('wizard-complete'),
            flow,
            onComplete: (result) => {
                const option = Array.from(resultingStatus.options).find(
                    (entry) => entry.value === result.status,
                );

                if (option) {
                    resultingStatus.value = result.status;
                } else if (result.status) {
                    const fallback = new Option(result.status, result.status, true, true);
                    resultingStatus.add(fallback);
                    resultingStatus.value = result.status;
                }

                if (resultingStatusHint && resultingStatusSuggested) {
                    if (result.status) {
                        resultingStatusSuggested.textContent = result.status;
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
