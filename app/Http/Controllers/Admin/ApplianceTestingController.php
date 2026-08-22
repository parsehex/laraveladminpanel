<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TruckAppliance;
use App\Testing\TestingFlowCategoryMapper;
use App\Testing\TestingFlowRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ApplianceTestingController extends Controller
{
    public function __construct(
        private readonly TestingFlowRepository $flows,
    ) {
        $this->middleware('permission:inventory.view')->only('show');
        $this->middleware('permission:appliance.edit')->only('store');
    }

    public function show(TruckAppliance $appliance)
    {
        abort_unless($appliance->status === 'Testing', 403, 'Unit must be in Testing status.');

        $appliance->loadMissing('category', 'model', 'truck');
        $slug = TestingFlowCategoryMapper::slugFromCategoryName($appliance->category?->name);
        $flow = $slug ? $this->flows->get($slug) : null;

        return view('admin.inventory.testing', [
            'appliance' => $appliance,
            'flow' => $flow,
            'flowSlug' => $slug,
            'statuses' => InventoryController::STATUSES,
        ]);
    }

    public function store(Request $request, TruckAppliance $appliance)
    {
        abort_unless($request->user()?->can('appliance.edit'), 403);
        abort_unless($appliance->status === 'Testing', 403, 'Unit must be in Testing status.');

        $data = $request->validate([
            'resulting_status' => ['required', 'string', Rule::in(InventoryController::STATUSES)],
            'answers_json' => ['required', 'string'],
            'flow_slug' => ['required', 'string', 'max:64'],
            'flow_version' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
        ]);

        $answers = json_decode($data['answers_json'], true);
        if (! is_array($answers)) {
            return back()->with('error', __('Testing answers were invalid.'));
        }

        $flow = $this->flows->get($data['flow_slug']);
        if ($flow === null) {
            return back()->with('error', __('Testing flow not found.'));
        }

        $snapshot = [
            'slug' => $flow['slug'],
            'name' => $flow['name'],
            'version' => (int) ($flow['version'] ?? 1),
            'start' => $flow['start'],
            'steps' => $flow['steps'],
        ];

        DB::transaction(function () use ($appliance, $data, $answers, $snapshot, $request) {
            $this->flows->storeResult([
                'appliance_id' => $appliance->id,
                'flow_slug' => $snapshot['slug'],
                'flow_version' => (int) $data['flow_version'],
                'resulting_status' => $data['resulting_status'],
                'answers' => $answers,
                'notes' => $data['notes'] ?? null,
                'user_id' => $request->user()->id,
                'user_name' => $request->user()->name,
                'completed_at' => now()->utc()->toIso8601String(),
                'flow_snapshot' => $snapshot,
            ]);

            $appliance->update([
                'status' => $data['resulting_status'],
                'updated_by' => $request->user()->id,
            ]);

            $appliance->statusHistories()->create([
                'status' => $data['resulting_status'],
                'notes' => $data['notes'] ?? 'Completed Testing flow '.$snapshot['slug'].' v'.$data['flow_version'].'.',
                'parts_ordered' => false,
                'user_id' => $request->user()->id,
            ]);
        });

        return redirect()
            ->route('admin.inventory.show', $appliance)
            ->with('success', __('Testing completed. Status set to :status.', [
                'status' => $data['resulting_status'],
            ]));
    }
}
