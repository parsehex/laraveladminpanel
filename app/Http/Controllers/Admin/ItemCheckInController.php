<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ItemCheckIn;
use App\Models\Truck;
use App\Models\TruckAppliance;
use App\Support\ItemCheckInMatcher;
use App\Support\ItemCheckInWriter;
use App\Support\LabelReadException;
use App\Support\OpenRouterLabelReader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ItemCheckInController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:inventory.view');
    }

    public function create(): View
    {
        return view('admin.inventory.check-in');
    }

    public function store(Request $request, OpenRouterLabelReader $reader): RedirectResponse
    {
        $data = $request->validate([
            'photos' => ['required', 'array', 'min:1', 'max:5'],
            'photos.*' => ['required', 'image', 'max:8192'],
        ], [
            'photos.required' => 'Add at least one photo of the item.',
            'photos.max' => 'Use at most 5 photos.',
            'photos.*.image' => 'Each photo must be an image.',
            'photos.*.max' => 'Each photo must be 8MB or smaller.',
        ]);

        if (blank(config('services.openrouter.key'))) {
            return back()->with('error', __('Check-in needs an OpenRouter API key. Set OPENROUTER_API_KEY and try again.'));
        }

        $directory = 'check-in-photos/'.Str::uuid();
        $paths = [];

        try {
            foreach ($data['photos'] as $photo) {
                $paths[] = $photo->store($directory, 'public');
            }

            $extraction = $reader->read($paths);
        } catch (LabelReadException $exception) {
            Storage::disk('public')->delete($paths);

            return back()->with('error', $exception->getMessage());
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($paths);

            throw $exception;
        }

        $checkIn = ItemCheckIn::query()->create([
            'user_id' => $request->user()->id,
            'photos' => $paths,
            'extraction' => $extraction,
        ]);

        return redirect()->route('admin.inventory.check-in.show', $checkIn);
    }

    public function show(Request $request, ItemCheckIn $checkIn, ItemCheckInMatcher $matcher): View|RedirectResponse
    {
        $this->authorizeDraft($request, $checkIn);

        if ($checkIn->confirmed_at !== null && $checkIn->truck_appliance_id !== null) {
            return redirect()->route('admin.inventory.show', $checkIn->truck_appliance_id);
        }

        $extraction = $checkIn->extraction ?? [];

        return view('admin.inventory.check-in-review', [
            'checkIn' => $checkIn,
            'extraction' => $extraction,
            'match' => $matcher->match($extraction),
            'trucks' => Truck::query()->orderBy('name')->get(['id', 'name']),
            'canEdit' => $request->user()?->can('appliance.edit') ?? false,
            'canCreate' => $request->user()?->can('appliance.create') ?? false,
        ]);
    }

    public function photo(Request $request, ItemCheckIn $checkIn): Response
    {
        $this->authorizeDraft($request, $checkIn);

        $path = $request->string('photo')->toString();
        abort_unless(in_array($path, $checkIn->photos ?? [], true), 404);
        abort_unless(Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path);
    }

    public function confirm(Request $request, ItemCheckIn $checkIn, ItemCheckInWriter $writer): RedirectResponse
    {
        $this->authorizeDraft($request, $checkIn);

        $data = $request->validate([
            'intent' => ['required', Rule::in(['lookup', 'match', 'add'])],
            'appliance_id' => [
                'required_if:intent,match',
                'nullable',
                'integer',
                Rule::exists('truck_appliances', 'id')->whereNull('deleted_at'),
            ],
            'truck_id' => [
                'required_if:intent,add',
                'nullable',
                'integer',
                Rule::exists('trucks', 'id')->whereNull('deleted_at'),
            ],
            'brand' => ['nullable', 'string', 'max:255'],
            'model_number' => ['nullable', 'string', 'max:255'],
            'product_name' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'msrp' => ['nullable', 'numeric', 'min:0'],
        ], [
            'appliance_id.required_if' => 'Choose the unit this box matches.',
            'truck_id.required_if' => 'Choose the truck to add this item to.',
        ]);

        $user = $request->user();
        abort_unless($user !== null, 403);

        $fields = [
            'brand' => $data['brand'] ?? null,
            'model_number' => $data['model_number'] ?? null,
            'product_name' => $data['product_name'] ?? null,
            'serial_number' => $data['serial_number'] ?? null,
            'category' => $data['category'] ?? null,
            'msrp' => $data['msrp'] ?? null,
        ];

        if ($data['intent'] === 'lookup') {
            $writer->rememberFields($checkIn, $fields);

            return redirect()
                ->route('admin.inventory.check-in.show', $checkIn)
                ->withInput($request->only(['brand', 'model_number', 'product_name', 'serial_number', 'category', 'msrp']));
        }

        if ($data['intent'] === 'match') {
            abort_unless($user->can('appliance.edit'), 403);

            $appliance = TruckAppliance::query()->findOrFail($data['appliance_id']);
            $appliance = $writer->confirmExisting($checkIn, $appliance, $user, $fields);

            return redirect()
                ->route('admin.inventory.show', $appliance)
                ->with('success', __('Checked in :label.', [
                    'label' => $appliance->unit_label ?: '#'.$appliance->id,
                ]));
        }

        abort_unless($user->can('appliance.create'), 403);

        $truck = Truck::query()->findOrFail($data['truck_id']);
        $appliance = $writer->addUnit($checkIn, $truck, $user, $fields);

        return redirect()
            ->route('admin.inventory.show', $appliance)
            ->with('success', __('Added :label to :truck.', [
                'label' => $appliance->unit_label ?: '#'.$appliance->id,
                'truck' => $truck->name,
            ]));
    }

    private function authorizeDraft(Request $request, ItemCheckIn $checkIn): void
    {
        abort_unless($checkIn->user_id === $request->user()?->id, 404);
    }
}
