<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TruckAppliance;
use App\Models\UserAction;
use App\Testing\RepairDiagnosisRepository;
use App\Testing\RepairReevaluationPresenter;
use App\Testing\RepairResultRepository;
use App\Testing\TestingFlowRepository;
use App\Testing\TestingResultPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ApplianceRepairController extends Controller
{
    public function __construct(
        private readonly TestingFlowRepository $testingResults,
        private readonly RepairResultRepository $repairResults,
        private readonly RepairDiagnosisRepository $diagnoses,
        private readonly TestingResultPresenter $testingPresenter,
        private readonly RepairReevaluationPresenter $repairPresenter,
    ) {
        $this->middleware('permission:inventory.view')->only(['show', 'showResult', 'indexResults']);
        $this->middleware('permission:appliance.edit')->only(['storeDiagnosis', 'storeReevaluation']);
    }

    public function show(TruckAppliance $appliance)
    {
        abort_unless($appliance->status === 'Repair', 403, 'Unit must be in Repair status.');

        $appliance->loadMissing('category', 'model', 'truck', 'parts.part', 'parts.user');
        $latestTest = $this->testingResults->latestResultForAppliance($appliance->id);
        $failedSteps = $latestTest !== null
            ? $this->repairPresenter->failedStepsForForm($latestTest)
            : [];

        return view('admin.inventory.repair', [
            'appliance' => $appliance,
            'diagnoses' => $this->diagnoses->listForAppliance($appliance->id),
            'latestTest' => $latestTest,
            'failedSteps' => $failedSteps,
            'repairResultCount' => count($this->repairResults->listForAppliance($appliance->id)),
        ]);
    }

    public function storeDiagnosis(Request $request, TruckAppliance $appliance)
    {
        abort_unless($request->user()?->can('appliance.edit'), 403);
        abort_unless($appliance->status === 'Repair', 403, 'Unit must be in Repair status.');

        $data = $request->validate([
            'diagnosis' => ['required', 'string', 'max:5000'],
        ]);

        $this->diagnoses->append(
            $appliance->id,
            $data['diagnosis'],
            (int) $request->user()->id,
            $request->user()->name,
        );

        return redirect()
            ->route('admin.inventory.repair.show', $appliance)
            ->with('success', __('Diagnosis note added.'));
    }

    public function storeReevaluation(Request $request, TruckAppliance $appliance)
    {
        abort_unless($request->user()?->can('appliance.edit'), 403);
        abort_unless($appliance->status === 'Repair', 403, 'Unit must be in Repair status.');

        $latestTest = $this->testingResults->latestResultForAppliance($appliance->id);
        if ($latestTest === null) {
            return back()->with('error', __('No testing result found for this unit.'));
        }

        $failedSteps = $this->repairPresenter->failedStepsForForm($latestTest);
        if ($failedSteps === []) {
            return back()->with('error', __('No failed testing steps to re-evaluate.'));
        }

        $data = $request->validate([
            're_test' => ['required', 'array'],
            're_test.*' => ['required', Rule::in(['yes', 'no'])],
            're_note' => ['nullable', 'array'],
            're_note.*' => ['nullable', 'string', 'max:1000'],
        ]);

        $answers = [];
        $snapshot = [];
        $allPassed = true;

        foreach ($failedSteps as $step) {
            $stepId = $step['step_id'];
            if (! array_key_exists($stepId, $data['re_test'])) {
                return back()->with('error', __('Missing re-test answer for step :step.', ['step' => $stepId]));
            }

            $answer = $data['re_test'][$stepId];
            $note = trim((string) ($data['re_note'][$stepId] ?? ''));

            $answers[$stepId] = ['answer' => $answer, 'note' => $note];
            $snapshot[$stepId] = [
                'question' => $step['question'],
                'original_note' => $step['original_note'],
            ];

            if ($answer === 'no') {
                $allPassed = false;
            }
        }

        $resultingStatus = $allPassed ? 'Ready' : 'Demanufacture';

        DB::transaction(function () use ($appliance, $latestTest, $answers, $snapshot, $resultingStatus, $request) {
            $resultId = $this->repairResults->store([
                'appliance_id' => $appliance->id,
                'type' => 'reevaluation',
                'source_testing_result_id' => $latestTest['result_id'] ?? null,
                'source_flow_slug' => $latestTest['flow_slug'] ?? null,
                'source_flow_version' => $latestTest['flow_version'] ?? null,
                'resulting_status' => $resultingStatus,
                'answers' => $answers,
                'failed_steps_snapshot' => $snapshot,
                'user_id' => $request->user()->id,
                'user_name' => $request->user()->name,
                'completed_at' => now()->utc()->toIso8601String(),
            ]);

            $appliance->update([
                'status' => $resultingStatus,
                'updated_by' => $request->user()->id,
            ]);

            $notes = 'Completed repair re-evaluation.';
            if ($resultingStatus === 'Ready') {
                $notes = 'Repair re-evaluation passed. Unit ready.';
            } elseif ($resultingStatus === 'Demanufacture') {
                $notes = 'Repair re-evaluation failed. Sent to demanufacture.';
            }
            $notes .= ' [repair-result:'.$resultId.']';

            $appliance->statusHistories()->create([
                'status' => $resultingStatus,
                'notes' => $notes,
                'parts_ordered' => false,
                'user_id' => $request->user()->id,
            ]);
        });

        $actionType = UserAction::actionTypeForStatus($resultingStatus);
        if ($actionType) {
            UserAction::log($actionType, $appliance->id);
        }

        return redirect()
            ->route('admin.inventory.show', $appliance)
            ->with('success', __('Re-evaluation complete. Status set to :status.', [
                'status' => $resultingStatus,
            ]));
    }

    public function indexResults(TruckAppliance $appliance)
    {
        $appliance->loadMissing('category', 'model', 'truck');

        return view('admin.inventory.repair-results-index', [
            'appliance' => $appliance,
            'results' => $this->repairResults->listForAppliance($appliance->id),
        ]);
    }

    public function showResult(TruckAppliance $appliance, string $result)
    {
        abort_unless($this->repairResults->belongsToAppliance($result, $appliance->id), 404);

        $data = $this->repairResults->get($result);
        abort_if($data === null, 404);

        $appliance->loadMissing('category', 'model', 'truck');

        return view('admin.inventory.repair-result', [
            'appliance' => $appliance,
            'result' => $data,
            'steps' => $this->repairPresenter->reevaluationSteps($data),
            'repairResultCount' => count($this->repairResults->listForAppliance($appliance->id)),
        ]);
    }
}
