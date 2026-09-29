import type { Edge, Node } from '@vue-flow/core';
import type { FlowOption, FlowStep, TestingFlow } from '../types';

export type QuestionNodeData = {
    question: string;
    note: boolean;
    options: FlowOption[];
    isStart: boolean;
};

export type TerminalNodeData = {
    question: string;
    note: boolean;
    status: string | null;
    isStart: boolean;
};

export type ProcedureNodeData = QuestionNodeData | TerminalNodeData;

export type ProcedureGraph = {
    nodes: Node<ProcedureNodeData>[];
    edges: Edge[];
};

function optionHandleId(optionKey: string): string {
    return `opt-${optionKey}`;
}

/**
 * Convert stored TestingFlow into Vue Flow nodes/edges.
 * Option edges with next:null + status become edges to an existing or synthetic terminal node.
 */
export function testingFlowToGraph(flow: TestingFlow): ProcedureGraph {
    const steps = flow.steps ?? {};
    const nodes: Node<ProcedureNodeData>[] = [];
    const edges: Edge[] = [];
    const terminalByStatus = new Map<string, string>();

    for (const [id, step] of Object.entries(steps)) {
        if (step.type === 'none') {
            const status = step.status || '';
            if (status) {
                terminalByStatus.set(status, id);
            }
            nodes.push({
                id,
                type: 'terminal',
                position: { x: 0, y: 0 },
                data: {
                    question: step.question || 'Result',
                    note: Boolean(step.note),
                    status: step.status ?? null,
                    isStart: flow.start === id,
                },
            });
        } else {
            nodes.push({
                id,
                type: 'question',
                position: { x: 0, y: 0 },
                data: {
                    question: step.question || '',
                    note: Boolean(step.note),
                    options: (step.options ?? []).map((option) => ({
                        key: option.key,
                        text: option.text,
                        next: option.next,
                        status: option.status,
                    })),
                    isStart: flow.start === id,
                },
            });
        }
    }

    let syntheticIndex = 0;

    function ensureTerminalForStatus(status: string): string {
        const existing = terminalByStatus.get(status);
        if (existing) {
            return existing;
        }

        let id = `status_${status.toLowerCase().replace(/[^a-z0-9]+/g, '_')}`;
        while (steps[id] || nodes.some((node) => node.id === id)) {
            syntheticIndex += 1;
            id = `status_${syntheticIndex}`;
        }

        terminalByStatus.set(status, id);
        nodes.push({
            id,
            type: 'terminal',
            position: { x: 0, y: 0 },
            data: {
                question: status,
                note: false,
                status,
                isStart: false,
            },
        });

        return id;
    }

    for (const node of nodes) {
        if (node.type !== 'question') {
            continue;
        }

        const data = node.data as QuestionNodeData;
        for (const option of data.options) {
            let target: string | null = null;

            if (option.next) {
                target = option.next;
            } else if (option.status) {
                target = ensureTerminalForStatus(option.status);
            }

            if (!target || !nodes.some((candidate) => candidate.id === target)) {
                continue;
            }

            edges.push({
                id: `e-${node.id}-${option.key}->${target}`,
                source: node.id,
                sourceHandle: optionHandleId(option.key),
                target,
                label: option.text || option.key,
                data: { optionKey: option.key },
            });
        }
    }

    return { nodes, edges };
}

export function graphToTestingFlow(
    meta: Pick<TestingFlow, 'slug' | 'name' | 'version' | 'updated_at' | 'start'>,
    nodes: Node<ProcedureNodeData>[],
    edges: Edge[],
): TestingFlow {
    const steps: Record<string, FlowStep> = {};

    for (const node of nodes) {
        if (node.type === 'terminal') {
            const data = node.data as TerminalNodeData;
            steps[node.id] = {
                id: node.id,
                question: data.question || data.status || 'Result',
                type: 'none',
                note: Boolean(data.note),
                next: null,
                status: data.status,
                options: [],
            };
            continue;
        }

        const data = node.data as QuestionNodeData;
        const options: FlowOption[] = (data.options ?? []).map((option) => {
            const edge = edges.find(
                (item) => item.source === node.id && item.sourceHandle === optionHandleId(option.key),
            );

            if (!edge) {
                return {
                    key: option.key,
                    text: option.text,
                    next: null,
                    status: option.status,
                };
            }

            return {
                key: option.key,
                text: option.text,
                next: edge.target,
                status: null,
            };
        });

        steps[node.id] = {
            id: node.id,
            question: data.question || '',
            type: 'radio',
            note: Boolean(data.note),
            options,
        };
    }

    const start =
        meta.start && steps[meta.start]
            ? meta.start
            : (Object.keys(steps)[0] ?? '');

    return {
        slug: meta.slug,
        name: meta.name,
        version: meta.version,
        updated_at: meta.updated_at,
        start,
        steps,
    };
}

export { optionHandleId };
