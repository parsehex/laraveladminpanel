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
const resultingStatus = document.getElementById('resulting-status') as HTMLInputElement | null;
const answersJson = document.getElementById('answers-json') as HTMLInputElement | null;

if (pageRoot && wizardRoot && restartBtn && resultingStatus && answersJson) {
    const flow = parseFlow(pageRoot.dataset.flow);
    if (flow) {
        const wizard = createTestingWizard({
            root: wizardRoot,
            completeEl: document.getElementById('wizard-complete'),
            finalStatusEl: document.getElementById('wizard-final-status'),
            flow,
            onComplete: (result) => {
                resultingStatus.value = result.status;
                answersJson.value = JSON.stringify(result.answers);
            },
        });

        restartBtn.addEventListener('click', () => {
            wizard.restart();
        });
    }
}
