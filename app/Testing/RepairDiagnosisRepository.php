<?php

namespace App\Testing;

use App\Models\RepairDiagnosis;
use Illuminate\Support\Str;

class RepairDiagnosisRepository
{
    /**
     * @return list<array{id: string, diagnosis: string, user_id: int, user_name: string, created_at: string}>
     */
    public function listForAppliance(int $applianceId): array
    {
        return RepairDiagnosis::query()
            ->where('truck_appliance_id', $applianceId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (RepairDiagnosis $row) => $row->toPayload())
            ->all();
    }

    /**
     * @return array{id: string, diagnosis: string, user_id: int, user_name: string, created_at: string}
     */
    public function append(int $applianceId, string $diagnosis, int $userId, string $userName): array
    {
        $row = RepairDiagnosis::query()->create([
            'uuid' => Str::uuid()->toString(),
            'truck_appliance_id' => $applianceId,
            'diagnosis' => trim($diagnosis),
            'user_id' => $userId,
            'user_name' => $userName,
            'created_at' => now(),
        ]);

        return $row->toPayload();
    }
}
