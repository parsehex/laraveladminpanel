<?php

namespace App\Testing;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class RepairDiagnosisRepository
{
    public function storagePath(): string
    {
        return storage_path('app/repair-diagnoses');
    }

    /**
     * @return list<array{id: string, diagnosis: string, user_id: int, user_name: string, created_at: string}>
     */
    public function listForAppliance(int $applianceId): array
    {
        $path = $this->fileForAppliance($applianceId);
        if (! File::exists($path)) {
            return [];
        }

        $data = json_decode(File::get($path), true);
        if (! is_array($data)) {
            return [];
        }

        return collect($data)
            ->filter(fn ($row) => is_array($row))
            ->sortByDesc(fn ($row) => $row['created_at'] ?? '')
            ->values()
            ->all();
    }

    /**
     * @return array{id: string, diagnosis: string, user_id: int, user_name: string, created_at: string}
     */
    public function append(int $applianceId, string $diagnosis, int $userId, string $userName): array
    {
        File::ensureDirectoryExists($this->storagePath());

        $entries = $this->listForAppliance($applianceId);
        $entry = [
            'id' => Str::uuid()->toString(),
            'diagnosis' => trim($diagnosis),
            'user_id' => $userId,
            'user_name' => $userName,
            'created_at' => now()->utc()->toIso8601String(),
        ];

        $entries[] = $entry;
        File::put($this->fileForAppliance($applianceId), json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

        return $entry;
    }

    private function fileForAppliance(int $applianceId): string
    {
        return $this->storagePath().'/'.$applianceId.'.json';
    }
}
