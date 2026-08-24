<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DemanPart;
use App\Models\TruckAppliance;
use App\Models\UserAction;
use App\Testing\DemanPromptRepository;
use App\Testing\RepairReevaluationPresenter;
use App\Testing\RepairResultRepository;
use App\Testing\TestingFlowRepository;
use App\Testing\TestingResultPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ApplianceDemanufactureController extends Controller
{
    public function __construct(
        private readonly TestingFlowRepository $testingResults,
        private readonly RepairResultRepository $repairResults,
        private readonly DemanPromptRepository $prompts,
        private readonly TestingResultPresenter $testingPresenter,
        private readonly RepairReevaluationPresenter $repairPresenter,
    ) {
        $this->middleware('permission:inventory.view')->only(['show']);
        $this->middleware('permission:appliance.edit')->only(['store']);
    }

    public function show(TruckAppliance $appliance)
    {
        abort_unless($appliance->status === 'Demanufacture', 403, 'Unit must be in Demanufacture status.');

        $appliance->loadMissing('category', 'model', 'truck');
        $latestTest = $this->testingResults->latestResultForAppliance($appliance->id);
        $latestRepair = $this->latestRepairResult($appliance->id);
        $categoryName = $appliance->category?->name;

        return view('admin.inventory.deman', [
            'appliance' => $appliance,
            'prompts' => $this->prompts->promptsForCategory($categoryName),
            'existingParts' => $appliance->demanParts()->with('user')->latest()->get(),
            'latestTest' => $latestTest,
            'failedSteps' => $latestTest !== null ? $this->testingPresenter->failedSteps($latestTest) : [],
            'latestRepair' => $latestRepair,
            'repairSteps' => $latestRepair !== null ? $this->repairPresenter->reevaluationSteps($latestRepair) : [],
            'testingResultCount' => count($this->testingResults->listResultsForAppliance($appliance->id)),
            'repairResultCount' => count($this->repairResults->listForAppliance($appliance->id)),
        ]);
    }

    public function store(Request $request, TruckAppliance $appliance)
    {
        abort_unless($request->user()?->can('appliance.edit'), 403);
        abort_unless($appliance->status === 'Demanufacture', 403, 'Unit must be in Demanufacture status.');

        $promptKeys = array_keys($this->prompts->promptsForCategory($appliance->category?->name));
        $rules = [
            'custom_parts' => ['nullable', 'array'],
            'custom_parts.*.description' => ['required_with:custom_parts.*.part_number', 'string', 'max:255'],
            'custom_parts.*.part_number' => ['nullable', 'string', 'max:100', 'regex:/^[A-Z0-9-]+$/'],
            'custom_parts.*.price' => ['nullable', 'numeric', 'min:0'],
            'custom_parts.*.condition' => ['nullable', Rule::in(DemanPart::CONDITIONS)],
        ];

        foreach ($promptKeys as $key) {
            $rules["prompts.{$key}.part_number"] = ['nullable', 'string', 'max:100', 'regex:/^[A-Z0-9-]+$/'];
            $rules["prompts.{$key}.price"] = ['nullable', 'numeric', 'min:0'];
            $rules["prompts.{$key}.condition"] = ['nullable', Rule::in(DemanPart::CONDITIONS)];
        }

        $data = $request->validate($rules);
        $prompts = $this->prompts->promptsForCategory($appliance->category?->name);
        $partsToStore = [];

        foreach ($prompts as $key => $description) {
            $row = $data['prompts'][$key] ?? [];
            $partNumber = strtoupper(trim((string) ($row['part_number'] ?? '')));
            if ($partNumber === '') {
                continue;
            }

            $partsToStore[] = [
                'part_number' => $partNumber,
                'description' => $description,
                'price' => (float) ($row['price'] ?? 0),
                'condition' => $row['condition'] ?? 'Good',
            ];
        }

        foreach ($data['custom_parts'] ?? [] as $row) {
            $partNumber = strtoupper(trim((string) ($row['part_number'] ?? '')));
            $description = trim((string) ($row['description'] ?? ''));
            if ($partNumber === '' || $description === '') {
                continue;
            }

            $partsToStore[] = [
                'part_number' => $partNumber,
                'description' => $description,
                'price' => (float) ($row['price'] ?? 0),
                'condition' => $row['condition'] ?? 'Good',
            ];
        }

        if ($partsToStore === []) {
            return back()->with('error', __('Enter at least one salvaged part with a part number.'));
        }

        DB::transaction(function () use ($appliance, $partsToStore, $request) {
            foreach ($partsToStore as $part) {
                DemanPart::create([
                    'truck_appliance_id' => $appliance->id,
                    'part_number' => $part['part_number'],
                    'description' => $part['description'],
                    'price' => $part['price'],
                    'condition' => $part['condition'],
                    'user_id' => $request->user()->id,
                ]);
            }

            $appliance->update(['updated_by' => $request->user()->id]);

            $appliance->statusHistories()->create([
                'status' => 'Demanufacture',
                'notes' => sprintf('Logged %d salvaged part(s).', count($partsToStore)),
                'parts_ordered' => false,
                'user_id' => $request->user()->id,
            ]);
        });

        UserAction::log('deman_unit', $appliance->id);

        foreach ($partsToStore as $part) {
            UserAction::log('pull_part', $appliance->id, [
                'part_number' => $part['part_number'],
                'price' => $part['price'],
                'condition' => $part['condition'],
            ]);
        }

        return redirect()
            ->route('admin.inventory.deman.show', $appliance)
            ->with('success', __('Demanufacture parts saved.'));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function latestRepairResult(int $applianceId): ?array
    {
        $summary = $this->repairResults->listForAppliance($applianceId)[0] ?? null;
        if ($summary === null) {
            return null;
        }

        return $this->repairResults->get((string) $summary['result_id']);
    }
}
