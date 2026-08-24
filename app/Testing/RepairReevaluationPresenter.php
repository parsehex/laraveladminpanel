<?php

namespace App\Testing;

class RepairReevaluationPresenter
{
    public function __construct(
        private readonly TestingResultPresenter $testingPresenter,
    ) {}

    /**
     * Failed steps from the latest testing result, enriched for the repair form.
     *
     * @param  array<string, mixed>  $testingResult
     * @return list<array{step_id: string, question: string, original_answer: string, original_note: string}>
     */
    public function failedStepsForForm(array $testingResult): array
    {
        $rows = $this->testingPresenter->failedSteps($testingResult);

        return array_map(fn (array $row) => [
            'step_id' => $row['step_id'],
            'question' => $row['question'],
            'original_answer' => $row['answer_key'] ?? $row['answer'],
            'original_note' => $row['note'],
        ], $rows);
    }

    /**
     * @param  array<string, mixed>  $repairResult
     * @return list<array{step_id: string, question: string, answer: string, answer_key: ?string, note: string, original_note: string, failed: bool}>
     */
    public function reevaluationSteps(array $repairResult): array
    {
        $snapshot = is_array($repairResult['failed_steps_snapshot'] ?? null)
            ? $repairResult['failed_steps_snapshot']
            : [];
        $answers = is_array($repairResult['answers'] ?? null) ? $repairResult['answers'] : [];
        $rows = [];

        foreach ($answers as $stepId => $answer) {
            if (! is_array($answer)) {
                continue;
            }

            $stepKey = (string) $stepId;
            $meta = is_array($snapshot[$stepKey] ?? null) ? $snapshot[$stepKey] : [];
            $answerKey = isset($answer['answer']) && $answer['answer'] !== ''
                ? (string) $answer['answer']
                : null;

            $rows[] = [
                'step_id' => $stepKey,
                'question' => (string) ($meta['question'] ?? "Step {$stepKey}"),
                'answer' => $answerKey === 'yes' ? 'Passed (Fixed)' : ($answerKey === 'no' ? 'Failed' : ($answerKey ?? '—')),
                'answer_key' => $answerKey,
                'note' => trim((string) ($answer['note'] ?? '')),
                'original_note' => (string) ($meta['original_note'] ?? ''),
                'failed' => $answerKey === 'no',
            ];
        }

        return $rows;
    }
}
