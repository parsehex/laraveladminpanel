<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kit;
use App\Models\KitAssignment;
use App\Models\KitInventory;
use App\Models\KitMessage;
use App\Models\KitPart;
use App\Models\Part;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class KitController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:kits.view')->only(['index', 'sop']);
        $this->middleware('permission:kits.manage')->except(['index', 'sop', 'start', 'built', 'message']);
        $this->middleware('permission:kits.build')->only(['start', 'built', 'message']);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $canManage = $user?->can('kits.manage');
        $platform = $user->platform ?: null;
        $kits = Kit::query()->with('parts')->orderBy('code')->get();
        $editKit = $request->integer('edit_kit') ? $kits->firstWhere('id', $request->integer('edit_kit')) : null;
        $selectedAssignment = $request->integer('assign')
            ? KitAssignment::with(['kit', 'messages.sender'])->find($request->integer('assign'))
            : null;

        $assignmentsQuery = KitAssignment::query()
            ->with(['kit', 'assignee', 'assigner'])
            ->whereNotIn('status', [KitAssignment::STATUS_BUILT, KitAssignment::STATUS_COMPLETED]);

        if (! $canManage) {
            $assignmentsQuery->where('assigned_to', $user->id);
        }

        if ($platform) {
            $assignmentsQuery->where('platform', $platform);
        }

        $builtQuery = KitAssignment::query()
            ->with(['kit', 'assignee', 'assigner'])
            ->where('status', KitAssignment::STATUS_BUILT);

        $completedQuery = KitAssignment::query()
            ->with(['kit', 'assignee', 'assigner'])
            ->where('status', KitAssignment::STATUS_COMPLETED);

        if ($platform && ! $user->hasRole('admin')) {
            $builtQuery->where('platform', $platform);
            $completedQuery->where('platform', $platform);
        }

        $kitCodes = Kit::query()->pluck('code');

        return view('admin.kits.index', [
            'kits' => $kits,
            'kitSummaries' => $kits->mapWithKeys(fn (Kit $kit) => [$kit->id => $this->kitSummary($kit)]),
            'makers' => User::query()
                ->whereHas('roles', fn (Builder $query) => $query->whereIn('name', ['kit_maker', 'kit_assigner']))
                ->orderBy('name')
                ->get(),
            'assignments' => $assignmentsQuery->latest()->get(),
            'builtAssignments' => $canManage ? $builtQuery->latest()->get() : collect(),
            'completedAssignments' => $canManage ? $completedQuery->latest()->get() : collect(),
            'finishedKits' => KitInventory::query()
                ->whereIn('part_name', $kitCodes)
                ->orderBy('part_name')
                ->get()
                ->keyBy('part_name'),
            'rawResources' => KitInventory::query()
                ->whereNotIn('part_name', $kitCodes)
                ->orderBy('part_name')
                ->get(),
            'editKit' => $editKit,
            'selectedAssignment' => $selectedAssignment,
            'canManage' => $canManage,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:255', 'unique:kits,code'],
            'name' => ['required', 'string', 'max:255'],
            'sop' => ['nullable', 'string'],
            'amazon_min_level' => ['nullable', 'integer', 'min:0'],
            'shopify_min_level' => ['nullable', 'integer', 'min:0'],
            'part_name' => ['array'],
            'part_name.*' => ['nullable', 'string', 'max:255'],
            'quantity_per_kit' => ['array'],
            'quantity_per_kit.*' => ['nullable', 'integer', 'min:1'],
        ]);

        DB::transaction(function () use ($data) {
            $kit = Kit::create([
                'code' => strtoupper(trim($data['code'])),
                'name' => trim($data['name']),
                'sop' => $data['sop'] ?? null,
            ]);

            KitInventory::firstOrCreate(
                ['part_name' => $kit->code],
                [
                    'amazon_stock' => 0,
                    'shopify_stock' => 0,
                    'amazon_min_level' => $data['amazon_min_level'] ?? 0,
                    'shopify_min_level' => $data['shopify_min_level'] ?? 0,
                ]
            );

            $this->syncNewParts($kit, $data['part_name'] ?? [], $data['quantity_per_kit'] ?? []);
        });

        return back()->with('success', __('Kit added.'));
    }

    public function destroy(Kit $kit)
    {
        DB::transaction(function () use ($kit) {
            KitInventory::query()->where('part_name', $kit->code)->delete();
            $kit->delete();
        });

        return redirect()->route('admin.kits.index')->with('success', __('Kit deleted.'));
    }

    public function addParts(Request $request, Kit $kit)
    {
        $data = $request->validate([
            'part_name' => ['array'],
            'part_name.*' => ['nullable', 'string', 'max:255'],
            'quantity_per_kit' => ['array'],
            'quantity_per_kit.*' => ['nullable', 'integer', 'min:1'],
        ]);

        $this->syncNewParts($kit, $data['part_name'] ?? [], $data['quantity_per_kit'] ?? []);

        return back()->with('success', __('Parts added to kit.'));
    }

    public function destroyPart(Kit $kit, KitPart $part)
    {
        abort_unless($part->kit_id === $kit->id, 404);

        $part->delete();

        return back()->with('success', __('Part removed from kit.'));
    }

    public function assign(Request $request)
    {
        $data = $request->validate([
            'kit_id' => ['required', 'exists:kits,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'platform' => ['required', Rule::in(['amazon', 'shopify'])],
            'assigned_to' => ['required', 'exists:users,id'],
            'due_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        KitAssignment::create([
            ...$data,
            'assigned_by' => $request->user()->id,
            'status' => KitAssignment::STATUS_PENDING,
        ]);

        return back()->with('success', __('Assignment created.'));
    }

    public function start(Request $request, KitAssignment $assignment)
    {
        abort_unless($assignment->assigned_to === $request->user()->id || $request->user()->can('kits.manage'), 403);
        abort_unless($assignment->status === KitAssignment::STATUS_PENDING, 422);

        DB::transaction(function () use ($assignment) {
            $assignment->load('kit.parts');

            foreach ($assignment->kit->parts as $part) {
                Part::query()
                    ->where('part_number', $part->part_name)
                    ->decrement('total_stock', $assignment->quantity * $part->quantity_per_kit);

                KitInventory::query()
                    ->where('part_name', $part->part_name)
                    ->decrement('current_stock', $assignment->quantity * $part->quantity_per_kit);
            }

            $assignment->update([
                'status' => KitAssignment::STATUS_IN_PROGRESS,
                'raw_stock_deducted' => true,
            ]);
        });

        return back()->with('success', __('Marked as started. Raw parts deducted.'));
    }

    public function built(Request $request, KitAssignment $assignment)
    {
        abort_unless($assignment->assigned_to === $request->user()->id || $request->user()->can('kits.manage'), 403);
        abort_unless($assignment->status === KitAssignment::STATUS_IN_PROGRESS, 422);

        $assignment->update(['status' => KitAssignment::STATUS_BUILT]);

        return back()->with('success', __('Marked as built. Waiting for confirmation.'));
    }

    public function confirm(KitAssignment $assignment)
    {
        abort_unless($assignment->status === KitAssignment::STATUS_BUILT, 422);

        DB::transaction(function () use ($assignment) {
            $assignment->load('kit');
            $column = $assignment->platform === 'amazon' ? 'amazon_stock' : 'shopify_stock';

            KitInventory::query()
                ->where('part_name', $assignment->kit->code)
                ->increment($column, $assignment->quantity);

            $assignment->update(['status' => KitAssignment::STATUS_COMPLETED]);
        });

        return back()->with('success', __('Confirmed. Finished kits added to platform stock.'));
    }

    public function destroyAssignment(KitAssignment $assignment)
    {
        DB::transaction(function () use ($assignment) {
            if ($assignment->raw_stock_deducted && in_array($assignment->status, [KitAssignment::STATUS_IN_PROGRESS, KitAssignment::STATUS_BUILT], true)) {
                $assignment->load('kit.parts');

                foreach ($assignment->kit->parts as $part) {
                    Part::query()
                        ->where('part_number', $part->part_name)
                        ->increment('total_stock', $assignment->quantity * $part->quantity_per_kit);

                    KitInventory::query()
                        ->where('part_name', $part->part_name)
                        ->increment('current_stock', $assignment->quantity * $part->quantity_per_kit);
                }
            }

            $assignment->delete();
        });

        return back()->with('success', __('Assignment deleted.'));
    }

    public function adjustStock(Request $request)
    {
        $data = $request->validate([
            'part_name' => ['required', 'exists:kit_inventory,part_name'],
            'adjustment' => ['required', 'integer'],
            'platform' => ['nullable', Rule::in(['amazon', 'shopify'])],
        ]);

        $column = $data['platform'] ? $data['platform'].'_stock' : 'current_stock';
        KitInventory::query()->where('part_name', $data['part_name'])->increment($column, $data['adjustment']);

        if (! $data['platform']) {
            Part::query()
                ->where('part_number', $data['part_name'])
                ->increment('total_stock', $data['adjustment']);
        }

        return back()->with('success', __('Stock adjusted.'));
    }

    public function adjustMinLevel(Request $request)
    {
        $data = $request->validate([
            'part_name' => ['required', 'exists:kit_inventory,part_name'],
            'new_min_level' => ['required', 'integer', 'min:0'],
            'platform' => ['required', Rule::in(['amazon', 'shopify'])],
        ]);

        KitInventory::query()
            ->where('part_name', $data['part_name'])
            ->update([$data['platform'].'_min_level' => $data['new_min_level']]);

        return back()->with('success', __('Minimum level adjusted.'));
    }

    public function storeResource(Request $request)
    {
        $data = $request->validate([
            'part_name' => ['required', 'string', 'max:255', 'unique:kit_inventory,part_name'],
            'initial_stock' => ['nullable', 'integer'],
            'min_level' => ['nullable', 'integer', 'min:0'],
        ]);

        KitInventory::create([
            'part_name' => trim($data['part_name']),
            'current_stock' => $data['initial_stock'] ?? 0,
            'min_level' => $data['min_level'] ?? 0,
        ]);

        Part::firstOrCreate(
            ['part_number' => trim($data['part_name'])],
            [
                'total_stock' => $data['initial_stock'] ?? 0,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]
        );

        return back()->with('success', __('Resource added.'));
    }

    public function destroyResource(KitInventory $resource)
    {
        abort_if(Kit::query()->where('code', $resource->part_name)->exists(), 422);

        $resource->delete();

        return back()->with('success', __('Resource deleted.'));
    }

    public function message(Request $request, KitAssignment $assignment)
    {
        abort_unless($assignment->assigned_to === $request->user()->id || $assignment->assigned_by === $request->user()->id || $request->user()->can('kits.manage'), 403);

        $data = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        KitMessage::create([
            'assignment_id' => $assignment->id,
            'sender_id' => $request->user()->id,
            'message' => $data['message'],
        ]);

        return redirect()->route('admin.kits.index', ['assign' => $assignment->id])->with('success', __('Message sent.'));
    }

    public function sop(Kit $kit)
    {
        $kit->load('parts');

        return response()->json([
            'name' => $kit->name,
            'sop' => $kit->sop,
            'parts' => $kit->parts->map(fn (KitPart $part) => [
                'part_name' => $part->part_name,
                'quantity_per_kit' => $part->quantity_per_kit,
            ]),
        ]);
    }

    private function syncNewParts(Kit $kit, array $partNames, array $quantities): void
    {
        $parts = $this->normalizeParts($partNames, $quantities);
        $this->assertPartQuantitiesWithinStock($parts);

        foreach ($parts as $partName => $quantity) {
            $part = Part::query()->where('part_number', $partName)->first();

            if (! $part) {
                throw ValidationException::withMessages([
                    'part_name' => "The selected part '{$partName}' does not exist in parts.",
                ]);
            }

            KitInventory::firstOrCreate(
                ['part_name' => $part->part_number],
                [
                    'current_stock' => $part->total_stock,
                    'min_level' => 0,
                ]
            );

            KitPart::updateOrCreate(
                ['kit_id' => $kit->id, 'part_name' => $partName],
                ['quantity_per_kit' => $quantity]
            );
        }
    }

    /**
     * @param  array<int, mixed>  $partNames
     * @param  array<int, mixed>  $quantities
     * @return array<string, int>
     */
    private function normalizeParts(array $partNames, array $quantities): array
    {
        $parts = [];

        foreach ($partNames as $index => $partName) {
            $partName = trim((string) $partName);
            $quantity = (int) ($quantities[$index] ?? 0);

            if ($partName === '' || $quantity < 1) {
                continue;
            }

            $parts[$partName] = $quantity;
        }

        return $parts;
    }

    /**
     * @param  array<string, int>  $parts
     */
    private function assertPartQuantitiesWithinStock(array $parts): void
    {
        foreach ($parts as $partName => $quantity) {
            $part = Part::query()->where('part_number', $partName)->first();

            if (! $part) {
                throw ValidationException::withMessages([
                    'part_name' => "The selected part '{$partName}' does not exist in parts.",
                ]);
            }

            if ($quantity > $part->total_stock) {
                throw ValidationException::withMessages([
                    'quantity_per_kit' => "Quantity for '{$partName}' cannot be greater than available stock ({$part->total_stock}).",
                ]);
            }
        }
    }

    private function assertKitAssignmentStock(Kit $kit, int $kitQuantity): void
    {
        $kit->loadMissing('parts');

        foreach ($kit->parts as $part) {
            $catalogPart = Part::query()->where('part_number', $part->part_name)->first();
            $available = $catalogPart?->total_stock ?? 0;
            $required = $kitQuantity * $part->quantity_per_kit;

            if ($required > $available) {
                $maxKits = $part->quantity_per_kit > 0 ? intdiv($available, $part->quantity_per_kit) : 0;

                throw ValidationException::withMessages([
                    'quantity' => "Not enough stock for '{$part->part_name}'. Required {$required}, available {$available}. You can assign up to {$maxKits} kit(s).",
                ]);
            }
        }
    }

    /**
     * @return array{cost: float, buildable: int|null, parts: array<int, array<string, mixed>>}
     */
    private function kitSummary(Kit $kit): array
    {
        $kit->loadMissing('parts');
        $partNumbers = $kit->parts->pluck('part_name')->filter()->values();
        $catalogParts = Part::query()
            ->whereIn('part_number', $partNumbers)
            ->get()
            ->keyBy('part_number');

        $cost = 0.0;
        $buildable = null;
        $parts = [];

        foreach ($kit->parts as $kitPart) {
            $catalogPart = $catalogParts->get($kitPart->part_name);
            $unitCost = $catalogPart ? $this->partCost($catalogPart) : 0.0;
            $lineCost = $unitCost * $kitPart->quantity_per_kit;
            $available = $catalogPart?->total_stock ?? 0;
            $partBuildable = $kitPart->quantity_per_kit > 0 ? intdiv($available, $kitPart->quantity_per_kit) : 0;

            $cost += $lineCost;
            $buildable = $buildable === null ? $partBuildable : min($buildable, $partBuildable);
            $parts[] = [
                'part_name' => $kitPart->part_name,
                'quantity_per_kit' => $kitPart->quantity_per_kit,
                'available' => $available,
                'unit_cost' => $unitCost,
                'line_cost' => $lineCost,
                'buildable' => $partBuildable,
            ];
        }

        return [
            'cost' => $cost,
            'buildable' => $buildable ?? 0,
            'parts' => $parts,
        ];
    }

    private function partCost(Part $part): float
    {
        $yourPrice = (float) $part->your_price;

        return $yourPrice > 0 ? $yourPrice : (float) $part->retail_price;
    }
}
