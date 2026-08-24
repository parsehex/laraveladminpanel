<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Testing\DemanPromptRepository;
use Illuminate\Http\Request;
use InvalidArgumentException;

class DemanFlowController extends Controller
{
    public function __construct(
        private readonly DemanPromptRepository $flows,
    ) {
        $this->middleware('permission:deman-flows.manage');
    }

    public function index()
    {
        return view('admin.deman-flows.index', [
            'flows' => $this->flows->list(),
        ]);
    }

    public function edit(string $flow)
    {
        $data = $this->flows->get($flow);
        abort_if($data === null, 404);

        return view('admin.deman-flows.edit', [
            'flow' => $data,
        ]);
    }

    public function update(Request $request, string $flow)
    {
        $existing = $this->flows->get($flow);
        abort_if($existing === null, 404);

        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'prompts' => ['required', 'array', 'min:1'],
            'prompts.*.key' => ['nullable', 'string', 'max:64'],
            'prompts.*.description' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $saved = $this->flows->save($existing['slug'], $payload);
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.deman-flows.edit', $saved['slug'])
            ->with('success', __('Demanufacture prompts saved.'));
    }
}
