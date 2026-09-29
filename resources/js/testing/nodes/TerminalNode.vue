<script setup lang="ts">
import { Handle, Position, useVueFlow } from '@vue-flow/core';
import type { TerminalNodeData } from '../flow/adapters';

const props = defineProps<{
    id: string;
    data: TerminalNodeData;
    statuses: string[];
}>();

const { updateNodeData } = useVueFlow({ id: 'procedure-editor' });

function patch(partial: Partial<TerminalNodeData>): void {
    updateNodeData(props.id, {
        ...props.data,
        ...partial,
    });
}

function updateQuestion(event: Event): void {
    const target = event.target as HTMLInputElement;
    patch({ question: target.value });
}

function updateStatus(event: Event): void {
    const target = event.target as HTMLSelectElement;
    patch({ status: target.value || null });
}
</script>

<template>
    <div
        class="w-[280px] rounded-lg border bg-emerald-50 shadow-sm"
        :class="data.isStart ? 'border-blue-500 ring-2 ring-blue-200' : 'border-emerald-300'"
    >
        <Handle
            id="target"
            type="target"
            :position="Position.Top"
            class="!h-2.5 !w-2.5 !bg-emerald-600"
        />

        <div class="flex items-center justify-between gap-2 border-b border-emerald-100 bg-emerald-100/70 px-3 py-2">
            <div class="flex items-center gap-2">
                <span class="font-mono text-xs font-semibold text-emerald-800">{{ id }}</span>
                <span
                    v-if="data.isStart"
                    class="rounded bg-blue-600 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white"
                >
                    Start
                </span>
            </div>
            <span class="text-[10px] font-semibold uppercase tracking-wide text-emerald-700">Terminal</span>
        </div>

        <div class="space-y-2 p-3" @mousedown.stop>
            <input
                type="text"
                :value="data.question"
                class="w-full rounded-md border border-emerald-200 bg-white px-2 py-1.5 text-sm"
                placeholder="Label"
                @input="updateQuestion"
            />

            <div>
                <label class="mb-1 block text-xs font-medium text-emerald-900">Status</label>
                <select
                    :value="data.status || ''"
                    class="w-full rounded-md border border-emerald-200 bg-white px-2 py-1.5 text-sm"
                    @change="updateStatus"
                >
                    <option value="">Select status</option>
                    <option v-for="status in statuses" :key="status" :value="status">
                        {{ status }}
                    </option>
                </select>
            </div>
        </div>
    </div>
</template>
