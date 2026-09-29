import { createApp } from 'vue';
import ProcedureEditorApp from '../testing/ProcedureEditorApp.vue';
import type { TestingFlow } from '../testing/types';

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

const mountEl = document.getElementById('procedure-editor');

if (mountEl) {
    const flow = parseJsonAttr<TestingFlow>(mountEl.dataset.flow, {
        slug: '',
        name: '',
        version: 1,
        updated_at: null,
        start: '',
        steps: {},
    });
    const statuses = parseJsonAttr<string[]>(mountEl.dataset.statuses, []);
    const formAction = mountEl.dataset.formAction ?? '';
    const csrfToken = mountEl.dataset.csrf ?? '';

    createApp(ProcedureEditorApp, {
        initialFlow: flow,
        statuses,
        formAction,
        csrfToken,
    }).mount(mountEl);
}
