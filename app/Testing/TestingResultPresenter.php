<?php

namespace App\Testing;

class TestingResultPresenter
{
    /**
     * @param  array<string, mixed>  $result
     * @return list<array{step_id: string, question: string, answer: string, answer_key: ?string, note: string, failed: bool}>
     */
    public function answeredSteps(array $result): array
    {
        $snapshot = is_array($result['flow_snapshot'] ?? null) ? $result['flow_snapshot'] : [];
        $steps = is_array($snapshot['steps'] ?? null) ? $snapshot['steps'] : [];
        $answers = is_array($result['answers'] ?? null) ? $result['answers'] : [];
        $orderedIds = $this->walkAnsweredPath($snapshot, $answers);
        $rows = [];

        foreach ($orderedIds as $stepId) {
            $stepId = (string) $stepId;
            if (! isset($answers[$stepId]) && ! isset($answers[(int) $stepId])) {
                continue;
            }

            $answer = $answers[$stepId] ?? $answers[(int) $stepId];
            $rows[] = $this->formatStepRow($stepId, $steps[$stepId] ?? $steps[(int) $stepId] ?? null, $answer);
        }

        $orderedLookup = array_fill_keys(array_map('strval', $orderedIds), true);

        foreach ($answers as $stepId => $answer) {
            $stepKey = (string) $stepId;
            if (isset($orderedLookup[$stepKey])) {
                continue;
            }

            if (! is_array($answer)) {
                continue;
            }

            $rows[] = $this->formatStepRow($stepKey, $steps[$stepKey] ?? $steps[(int) $stepKey] ?? null, $answer);
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $result
     * @return list<array{step_id: string, question: string, answer: string, answer_key: ?string, note: string, failed: bool}>
     */
    public function failedSteps(array $result): array
    {
        return array_values(array_filter(
            $this->answeredSteps($result),
            fn (array $row) => $row['failed'],
        ));
    }

    /**
     * @param  array<string, mixed>|null  $step
     * @param  array<string, mixed>  $answer
     * @return array{step_id: string, question: string, answer: string, answer_key: ?string, note: string, failed: bool}
     */
    private function formatStepRow(string $stepId, ?array $step, array $answer): array
    {
        $answerKey = isset($answer['answer']) && $answer['answer'] !== ''
            ? (string) $answer['answer']
            : null;
        $note = trim((string) ($answer['note'] ?? ''));

        if (! is_array($step)) {
            return [
                'step_id' => $stepId,
                'question' => "Step {$stepId}",
                'answer' => $answerKey ?? '—',
                'answer_key' => $answerKey,
                'note' => $note,
                'failed' => $answerKey === 'no',
            ];
        }

        $question = (string) ($step['question'] ?? "Step {$stepId}");
        $answerText = $answerKey ?? '—';

        if (($step['type'] ?? '') === 'none') {
            $answerText = (string) ($step['status'] ?? 'Confirmed');
        } elseif ($answerKey !== null) {
            foreach (($step['options'] ?? []) as $option) {
                if (! is_array($option)) {
                    continue;
                }
                if ((string) ($option['key'] ?? '') === $answerKey) {
                    $answerText = (string) ($option['text'] ?? $answerKey);
                    break;
                }
            }
        }

        return [
            'step_id' => $stepId,
            'question' => $question,
            'answer' => $answerText,
            'answer_key' => $answerKey,
            'note' => $note,
            'failed' => $answerKey === 'no',
        ];
    }

    /**
     * @param  array<string, mixed>  $flow
     * @param  array<string, array<string, mixed>>  $answers
     * @return list<string>
     */
    private function walkAnsweredPath(array $flow, array $answers): array
    {
        $steps = is_array($flow['steps'] ?? null) ? $flow['steps'] : [];
        $start = (string) ($flow['start'] ?? '');
        $path = [];
        $current = $start;
        $guard = 0;
        $visited = [];

        while ($current !== '' && $guard < 100) {
            $guard++;
            if (isset($visited[$current])) {
                break;
            }
            $visited[$current] = true;

            if (! isset($answers[$current]) && ! isset($answers[(int) $current])) {
                break;
            }

            $path[] = $current;
            $step = $steps[$current] ?? $steps[(int) $current] ?? null;
            if (! is_array($step)) {
                break;
            }

            if (($step['type'] ?? '') === 'none') {
                break;
            }

            $answer = $answers[$current] ?? $answers[(int) $current];
            $answerKey = $answer['answer'] ?? null;
            $next = null;
            foreach (($step['options'] ?? []) as $option) {
                if (! is_array($option)) {
                    continue;
                }
                if ((string) ($option['key'] ?? '') === (string) $answerKey) {
                    $next = $option['next'] ?? null;
                    break;
                }
            }

            if ($next === null || $next === '') {
                break;
            }

            $current = (string) $next;
        }

        return $path;
    }
}
