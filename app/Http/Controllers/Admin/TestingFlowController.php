<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Testing\TestingFlowRepository;
use Illuminate\Http\Request;
use InvalidArgumentException;

class TestingFlowController extends Controller
{
    public function __construct(
        private readonly TestingFlowRepository $flows,
    ) {
        $this->middleware('permission:testing-flows.manage');
    }

    public function index()
    {
        return view('admin.testing-flows.index', [
            'flows' => $this->flows->list(),
        ]);
    }

    public function edit(string $flow)
    {
        $data = $this->flows->get($flow);
        abort_if($data === null, 404);

        return view('admin.testing-flows.edit', [
            'flow' => $data,
            'statuses' => $this->terminalStatuses(),
        ]);
    }

    public function update(Request $request, string $flow)
    {
        $existing = $this->flows->get($flow);
        abort_if($existing === null, 404);

        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'start' => ['required', 'string', 'max:64'],
            'flow_json' => ['required', 'string'],
        ]);

        $decoded = json_decode($payload['flow_json'], true);
        if (! is_array($decoded)) {
            return back()->withInput()->with('error', __('Flow JSON is invalid.'));
        }

        $decoded['slug'] = $existing['slug'];
        $decoded['name'] = $payload['name'];
        $decoded['start'] = $payload['start'];
        $decoded['version'] = $existing['version'] ?? 1;

        try {
            $saved = $this->flows->save($decoded, bumpVersion: true);
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.testing-flows.edit', $saved['slug'])
            ->with('success', __('Testing flow saved as version :version.', [
                'version' => $saved['version'],
            ]));
    }

    /**
     * @return list<string>
     */
    private function terminalStatuses(): array
    {
        return collect(InventoryController::STATUSES)
            ->reject(fn (string $status) => in_array($status, ['Sold', 'Triage', 'Testing'], true))
            ->values()
            ->all();
    }
}
