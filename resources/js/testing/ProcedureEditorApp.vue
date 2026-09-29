<script setup lang="ts">
import { computed, nextTick, onMounted, onBeforeUnmount, ref, watch } from 'vue';
import {
    VueFlow,
    useVueFlow,
    type Connection,
    type Edge,
    type Node,
} from '@vue-flow/core';
import { Background } from '@vue-flow/background';
import { Controls } from '@vue-flow/controls';
import { MiniMap } from '@vue-flow/minimap';
import {
    graphToTestingFlow,
    testingFlowToGraph,
    type ProcedureNodeData,
    type QuestionNodeData,
    type TerminalNodeData,
} from './flow/adapters';
import { layoutProcedureGraph } from './flow/layout';
import QuestionNode from './nodes/QuestionNode.vue';
import TerminalNode from './nodes/TerminalNode.vue';
import { createTestingWizard } from './wizard';
import type { TestingFlow, TestingWizard } from './types';

import '@vue-flow/core/dist/style.css';
import '@vue-flow/core/dist/theme-default.css';
import '@vue-flow/controls/dist/style.css';
import '@vue-flow/minimap/dist/style.css';

const props = defineProps<{
    initialFlow: TestingFlow;
    statuses: string[];
    formAction: string;
    csrfToken: string;
}>();

const name = ref(props.initialFlow.name);
const start = ref(props.initialFlow.start);
const flowJsonInput = ref<HTMLInputElement | null>(null);
const wizardRoot = ref<HTMLElement | null>(null);
const wizardComplete = ref<HTMLElement | null>(null);
const wizardFinalStatus = ref<HTMLElement | null>(null);

const seeded = testingFlowToGraph(props.initialFlow);
const nodes = ref<Node<ProcedureNodeData>[]>(layoutProcedureGraph(seeded.nodes, seeded.edges));
const edges = ref<Edge[]>(seeded.edges);

const { onConnect, getSelectedNodes, fitView } = useVueFlow({
    id: 'procedure-editor',
});

let previewWizard: TestingWizard | null = null;
let previewTimer: ReturnType<typeof setTimeout> | null = null;

const startOptions = computed(() =>
    nodes.value.map((node) => ({
        id: node.id,
        label: `${node.id}${node.type === 'terminal' ? ' (terminal)' : ''}`,
    })),
);

function markStartFlags(startId: string): void {
    nodes.value = nodes.value.map((node) => ({
        ...node,
        data: {
            ...node.data,
            isStart: node.id === startId,
        },
    }));
}

function buildPayload(): TestingFlow {
    return graphToTestingFlow(
        {
            slug: props.initialFlow.slug,
            name: name.value,
            version: props.initialFlow.version || 1,
            updated_at: props.initialFlow.updated_at || null,
            start: start.value,
        },
        nodes.value,
        edges.value,
    );
}

function syncPreview(): void {
    if (!wizardRoot.value) {
        return;
    }

    const data = buildPayload();
    if (!previewWizard) {
        previewWizard = createTestingWizard({
            root: wizardRoot.value,
            completeEl: wizardComplete.value,
            finalStatusEl: wizardFinalStatus.value,
            flow: data,
        });
    } else {
        previewWizard.setFlow(data);
    }
}

function schedulePreviewSync(): void {
    if (previewTimer) {
        clearTimeout(previewTimer);
    }
    previewTimer = setTimeout(() => {
        syncPreview();
    }, 200);
}

function uniqueStepId(prefix: string): string {
    let index = nodes.value.length + 1;
    let id = `${prefix}_${index}`;
    const existing = new Set(nodes.value.map((node) => node.id));
    while (existing.has(id)) {
        index += 1;
        id = `${prefix}_${index}`;
    }
    return id;
}

function addQuestion(): void {
    const id = uniqueStepId('step');
    const defaultStatus = props.statuses[0] || 'Ready';
    nodes.value = [
        ...nodes.value,
        {
            id,
            type: 'question',
            position: { x: 80 + nodes.value.length * 24, y: 80 + nodes.value.length * 24 },
            data: {
                question: 'New question',
                note: false,
                options: [{ key: 'yes', text: 'Yes', next: null, status: defaultStatus }],
                isStart: false,
            } satisfies QuestionNodeData,
        },
    ];
    if (!start.value) {
        start.value = id;
        markStartFlags(id);
    }
    schedulePreviewSync();
}

function addTerminal(): void {
    const id = uniqueStepId('result');
    const status = props.statuses[0] || 'Ready';
    nodes.value = [
        ...nodes.value,
        {
            id,
            type: 'terminal',
            position: { x: 120 + nodes.value.length * 24, y: 120 + nodes.value.length * 24 },
            data: {
                question: status,
                note: false,
                status,
                isStart: false,
            } satisfies TerminalNodeData,
        },
    ];
    schedulePreviewSync();
}

function deleteSelected(): void {
    const selected = getSelectedNodes.value;
    if (selected.length === 0) {
        return;
    }

    if (nodes.value.length - selected.length < 1) {
        window.alert('A flow needs at least one step.');
        return;
    }

    const ids = new Set(selected.map((node) => node.id));
    edges.value = edges.value.filter((edge) => !ids.has(edge.source) && !ids.has(edge.target));
    nodes.value = nodes.value.filter((node) => !ids.has(node.id));

    if (ids.has(start.value)) {
        start.value = nodes.value[0]?.id ?? '';
        markStartFlags(start.value);
    }

    schedulePreviewSync();
}

function setSelectedAsStart(): void {
    const selected = getSelectedNodes.value;
    if (selected.length !== 1) {
        window.alert('Select a single node to mark as start.');
        return;
    }
    start.value = selected[0].id;
    markStartFlags(start.value);
    schedulePreviewSync();
}

function relayout(): void {
    nodes.value = layoutProcedureGraph(nodes.value, edges.value);
    nextTick(() => {
        fitView({ padding: 0.2 });
    });
}

function onFormSubmit(): void {
    if (flowJsonInput.value) {
        flowJsonInput.value.value = JSON.stringify(buildPayload());
    }
}

onConnect((connection: Connection) => {
    if (!connection.source || !connection.target || !connection.sourceHandle) {
        return;
    }

    const optionKey = connection.sourceHandle.replace(/^opt-/, '');
    const sourceNode = nodes.value.find((node) => node.id === connection.source);
    const optionText =
        sourceNode?.type === 'question'
            ? ((sourceNode.data as QuestionNodeData).options ?? []).find((option) => option.key === optionKey)
                  ?.text || optionKey
            : optionKey;

    edges.value = [
        ...edges.value.filter(
            (edge) => !(edge.source === connection.source && edge.sourceHandle === connection.sourceHandle),
        ),
        {
            id: `e-${connection.source}-${optionKey}->${connection.target}`,
            source: connection.source,
            sourceHandle: connection.sourceHandle,
            target: connection.target,
            label: optionText,
            data: { optionKey },
        },
    ];
    schedulePreviewSync();
});

watch(start, (value) => {
    markStartFlags(value);
    schedulePreviewSync();
});

watch([nodes, edges], () => schedulePreviewSync(), { deep: true });

onMounted(() => {
    markStartFlags(start.value);
    nextTick(() => {
        fitView({ padding: 0.2 });
        syncPreview();
    });
});

onBeforeUnmount(() => {
    if (previewTimer) {
        clearTimeout(previewTimer);
    }
});
</script>

<template>
    <form
        method="POST"
        :action="formAction"
        class="space-y-6"
        @submit="onFormSubmit"
    >
        <input type="hidden" name="_token" :value="csrfToken" />
        <input type="hidden" name="_method" value="PUT" />
        <input type="hidden" name="editor" value="canvas" />
        <input ref="flowJsonInput" type="hidden" name="flow_json" value="" />

        <div class="space-y-4 rounded-lg bg-white p-6 shadow">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-sm text-gray-500">
                        Slug <code class="text-gray-800">{{ initialFlow.slug }}</code>
                        · Version <strong>v{{ initialFlow.version }}</strong>
                    </p>
                    <p class="mt-1 text-xs text-gray-400">
                        Saving bumps the version and archives the previous definition in the database.
                    </p>
                </div>
                <button
                    type="submit"
                    class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                >
                    Save flow
                </button>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Name</label>
                    <input
                        v-model="name"
                        type="text"
                        name="name"
                        required
                        class="w-full rounded-md border border-gray-300 px-3 py-2"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Start step</label>
                    <select
                        v-model="start"
                        name="start"
                        required
                        class="w-full rounded-md border border-gray-300 px-3 py-2"
                    >
                        <option v-for="option in startOptions" :key="option.id" :value="option.id">
                            {{ option.label }}
                        </option>
                    </select>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <div class="overflow-hidden rounded-lg bg-white shadow xl:col-span-2">
                <div class="flex flex-wrap items-center justify-between gap-2 bg-slate-800 px-4 py-3">
                    <h2 class="font-semibold text-white">Procedure canvas</h2>
                    <div class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            class="rounded-md bg-white/10 px-3 py-1.5 text-sm font-semibold text-white hover:bg-white/20"
                            @click="addQuestion"
                        >
                            Add question
                        </button>
                        <button
                            type="button"
                            class="rounded-md bg-white/10 px-3 py-1.5 text-sm font-semibold text-white hover:bg-white/20"
                            @click="addTerminal"
                        >
                            Add terminal
                        </button>
                        <button
                            type="button"
                            class="rounded-md bg-white/10 px-3 py-1.5 text-sm font-semibold text-white hover:bg-white/20"
                            @click="setSelectedAsStart"
                        >
                            Set start
                        </button>
                        <button
                            type="button"
                            class="rounded-md bg-white/10 px-3 py-1.5 text-sm font-semibold text-white hover:bg-white/20"
                            @click="deleteSelected"
                        >
                            Delete
                        </button>
                        <button
                            type="button"
                            class="rounded-md bg-white/10 px-3 py-1.5 text-sm font-semibold text-white hover:bg-white/20"
                            @click="relayout"
                        >
                            Auto-layout
                        </button>
                    </div>
                </div>

                <div class="h-[70vh] min-h-[28rem] w-full">
                    <VueFlow
                        id="procedure-editor"
                        v-model:nodes="nodes"
                        v-model:edges="edges"
                        :default-edge-options="{ type: 'smoothstep', animated: false }"
                        :fit-view-on-init="false"
                        class="procedure-flow"
                    >
                        <Background pattern-color="#cbd5e1" :gap="18" />
                        <Controls />
                        <MiniMap />

                        <template #node-question="nodeProps">
                            <QuestionNode v-bind="nodeProps" />
                        </template>
                        <template #node-terminal="nodeProps">
                            <TerminalNode v-bind="nodeProps" :statuses="statuses" />
                        </template>
                    </VueFlow>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg bg-white shadow">
                <div class="flex items-center justify-between bg-blue-600 px-4 py-3">
                    <h2 class="font-semibold text-white">Preview</h2>
                    <button
                        type="button"
                        class="rounded-md bg-white/10 px-3 py-1.5 text-sm font-semibold text-white hover:bg-white/20"
                        @click="syncPreview"
                    >
                        Restart
                    </button>
                </div>
                <div class="space-y-4 p-4">
                    <div ref="wizardRoot" class="min-h-[12rem]" />
                    <div
                        ref="wizardComplete"
                        class="hidden rounded-md border border-emerald-200 bg-emerald-50 p-4"
                    >
                        <p class="font-semibold text-emerald-900">
                            Would set status to:
                            <span ref="wizardFinalStatus" />
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </form>
</template>

<style scoped>
.procedure-flow {
    width: 100%;
    height: 100%;
    background: #f8fafc;
}
</style>
