<?php

namespace App\Legacy\Importers;

use App\Legacy\LegacyImportContext;
use App\Legacy\LegacyText;
use Illuminate\Support\Facades\DB;

class SuggestionsImporter implements LegacyTableImporter
{
    public function legacyTable(): string
    {
        return 'suggestions';
    }

    public function import(LegacyImportContext $context): void
    {
        if (! $context->dump->hasTable('suggestions')) {
            return;
        }

        $read = 0;
        $inserted = 0;
        $updated = 0;

        foreach ($context->dump->rows('suggestions') as $row) {
            $read++;
            $legacyId = (int) $row['id'];
            $completedBy = $this->nullableString($row['completed_by'] ?? null);

            $attributes = [
                'username' => $row['username'],
                'user_id' => $context->users->resolve($row['username'] ?? null, false),
                'suggestion' => LegacyText::plain($row['suggestion']) ?? '',
                'urgency' => $row['urgency'],
                'status' => $row['status'] === 'complete' ? 'completed' : $row['status'],
                'responses' => $this->normalizeJson($row['responses'] ?? '[]'),
                'completed_by' => $completedBy ? $context->users->resolve($completedBy, false) : null,
                'completed_at' => $row['status'] === 'complete' ? ($row['updated_at'] ?? null) : null,
                'created_at' => $row['created_at'] ?? now(),
                'updated_at' => $row['updated_at'] ?? now(),
            ];

            if ($context->dryRun) {
                continue;
            }

            $existingId = $context->idMap->get('suggestions', $legacyId);
            if ($existingId) {
                DB::table('suggestions')->where('id', $existingId)->update($attributes);
                $updated++;
            } else {
                $context->queueInsert('suggestions', 'suggestions', $legacyId, $attributes);
                $inserted++;
            }
        }

        $context->report->recordTable('suggestions', $read, $inserted, $updated);
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function normalizeJson(mixed $value): string
    {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE) ?: '[]';
        }

        $decoded = json_decode((string) $value, true);

        return json_encode(is_array($decoded) ? $decoded : [], JSON_UNESCAPED_UNICODE) ?: '[]';
    }
}
