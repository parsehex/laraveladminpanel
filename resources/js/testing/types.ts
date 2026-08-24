export type StepType = 'radio' | 'none';

export type FlowOption = {
    key: string;
    text: string;
    next: string | null;
    status: string | null;
};

export type FlowStep = {
    id: string;
    question: string;
    type: StepType;
    note: boolean;
    options?: FlowOption[];
    next?: string | null;
    status?: string | null;
};

export type TestingFlow = {
    slug: string;
    name: string;
    version: number;
    updated_at: string | null;
    start: string;
    steps: Record<string, FlowStep>;
};

export type StepAnswer = {
    answer: string | null;
    note: string;
};

export type WizardAnswers = Record<string, StepAnswer>;

export type WizardCompleteResult = {
    status: string;
    answers: WizardAnswers;
};

export type TestingWizard = {
    restart: () => void;
    setFlow: (flow: TestingFlow) => void;
    getAnswers: () => WizardAnswers;
};

export type TestingWizardOptions = {
    root: HTMLElement;
    completeEl?: HTMLElement | null;
    finalStatusEl?: HTMLElement | null;
    flow: TestingFlow;
    onComplete?: (result: WizardCompleteResult) => void;
};
