<script setup lang="ts">
import { computed } from 'vue';
import { Handle, Position, useVueFlow } from '@vue-flow/core';
import { optionHandleId, type QuestionNodeData } from '../flow/adapters';

type Option = QuestionNodeData['options'][number];

const props = defineProps<{
    id: string;
    data: QuestionNodeData;
}>();

const { updateNodeData, getEdges, setEdges } = useVueFlow({ id: 'procedure-editor' });

const options = computed(() => props.data.options ?? []);

function patch(partial: Partial<QuestionNodeData>): void {
    updateNodeData(props.id, {
        ...props.data,
        ...partial,
    });
}

function updateQuestion(event: Event): void {
    const target = event.target as HTMLInputElement;
    patch({ question: target.value });
}

function updateNote(event: Event): void {
    const target = event.target as HTMLInputElement;
    patch({ note: target.checked });
}

function updateOptionKey(index: number, event: Event): void {
    const target = event.target as HTMLInputElement;
    const previous = options.value[index];
    if (!previous) {
        return;
    }

    const oldHandle = optionHandleId(previous.key);
    const nextKey = target.value;
    const next: Option[] = options.value.map((option, i) =>
        i === index ? { ...option, key: nextKey } : option,
    );
    patch({ options: next });

    const newHandle = optionHandleId(nextKey);
    setEdges(
        getEdges.value.map((edge) => {
            if (edge.source === props.id && edge.sourceHandle === oldHandle) {
                return { ...edge, sourceHandle: newHandle, id: `e-${props.id}-${nextKey}->${edge.target}` };
            }
            return edge;
        }),
    );
}

function updateOptionText(index: number, event: Event): void {
    const target = event.target as HTMLInputElement;
    const next = options.value.map((option, i) =>
        i === index ? { ...option, text: target.value } : option,
    );
    patch({ options: next });
}

function addOption(): void {
    const nextIndex = options.value.length + 1;
    const next: Option[] = [
        ...options.value,
        { key: `opt${nextIndex}`, text: 'New option', next: null, status: null },
    ];
    patch({ options: next });
}

function removeOption(index: number): void {
    const removed = options.value[index];
    const next = options.value.filter((_, i) => i !== index);
    patch({ options: next });

    if (!removed) {
        return;
    }

    const handle = optionHandleId(removed.key);
    setEdges(getEdges.value.filter((edge) => !(edge.source === props.id && edge.sourceHandle === handle)));
}
</script>

<template>
    <div
        class="w-[280px] rounded-lg border bg-white shadow-sm"
        :class="data.isStart ? 'border-blue-500 ring-2 ring-blue-200' : 'border-gray-300'"
    >
        <Handle
            id="target"
            type="target"
            :position="Position.Top"
            class="!h-2.5 !w-2.5 !bg-slate-500"
        />

        <div class="flex items-center justify-between gap-2 border-b border-gray-100 bg-slate-50 px-3 py-2">
            <div class="flex items-center gap-2">
                <span class="font-mono text-xs font-semibold text-slate-600">{{ id }}</span>
                <span
                    v-if="data.isStart"
                    class="rounded bg-blue-600 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white"
                >
                    Start
                </span>
            </div>
            <span class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Question</span>
        </div>

        <div class="space-y-2 p-3" @mousedown.stop>
            <input
                type="text"
                :value="data.question"
                class="w-full rounded-md border border-gray-300 px-2 py-1.5 text-sm"
                placeholder="Question"
                @input="updateQuestion"
            />

            <label class="inline-flex items-center gap-2 text-xs text-gray-600">
                <input type="checkbox" :checked="data.note" @change="updateNote" />
                Allow note
            </label>

            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-gray-700">Options</span>
                    <button
                        type="button"
                        class="text-xs font-medium text-blue-600 hover:text-blue-800"
                        @click.stop="addOption"
                    >
                        Add
                    </button>
                </div>

                <div
                    v-for="(option, index) in options"
                    :key="`${id}-opt-${index}`"
                    class="relative rounded-md border border-slate-200 bg-slate-50 p-2 pr-8"
                >
                    <div class="mb-1 grid grid-cols-3 gap-1">
                        <input
                            type="text"
                            :value="option.key"
                            class="col-span-1 rounded border border-gray-300 px-1.5 py-1 font-mono text-xs"
                            placeholder="key"
                            @input="updateOptionKey(index, $event)"
                        />
                        <input
                            type="text"
                            :value="option.text"
                            class="col-span-2 rounded border border-gray-300 px-1.5 py-1 text-xs"
                            placeholder="Label"
                            @input="updateOptionText(index, $event)"
                        />
                    </div>
                    <button
                        type="button"
                        class="absolute right-1 top-1 text-xs text-red-500 hover:text-red-700"
                        title="Remove option"
                        @click.stop="removeOption(index)"
                    >
                        ✕
                    </button>
                    <Handle
                        :id="optionHandleId(option.key)"
                        type="source"
                        :position="Position.Right"
                        class="!bg-blue-500"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
