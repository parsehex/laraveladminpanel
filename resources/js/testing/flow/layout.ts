import dagre from '@dagrejs/dagre';
import type { Edge, Node } from '@vue-flow/core';
import type { ProcedureNodeData } from './adapters';

const NODE_WIDTH = 280;
const QUESTION_BASE_HEIGHT = 120;
const OPTION_ROW_HEIGHT = 36;
const TERMINAL_HEIGHT = 110;

function nodeHeight(node: Node<ProcedureNodeData>): number {
    if (node.type === 'terminal') {
        return TERMINAL_HEIGHT;
    }

    const options = (node.data as { options?: unknown[] })?.options?.length ?? 1;
    return QUESTION_BASE_HEIGHT + options * OPTION_ROW_HEIGHT;
}

export function layoutProcedureGraph(
    nodes: Node<ProcedureNodeData>[],
    edges: Edge[],
    direction: 'TB' | 'LR' = 'TB',
): Node<ProcedureNodeData>[] {
    const graph = new dagre.graphlib.Graph();
    graph.setDefaultEdgeLabel(() => ({}));
    graph.setGraph({
        rankdir: direction,
        nodesep: 60,
        ranksep: 80,
        marginx: 24,
        marginy: 24,
    });

    for (const node of nodes) {
        graph.setNode(node.id, {
            width: NODE_WIDTH,
            height: nodeHeight(node),
        });
    }

    for (const edge of edges) {
        graph.setEdge(edge.source, edge.target);
    }

    dagre.layout(graph);

    return nodes.map((node) => {
        const laidOut = graph.node(node.id);
        if (!laidOut) {
            return node;
        }

        return {
            ...node,
            position: {
                x: laidOut.x - NODE_WIDTH / 2,
                y: laidOut.y - nodeHeight(node) / 2,
            },
        };
    });
}
