<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserAction;
use Illuminate\Http\Request;

class UserActionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:user-actions.view');
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->get('search', ''));
        $perPage = (int) $request->get('per_page', 50);
        if (! in_array($perPage, [25, 50, 100], true)) {
            $perPage = 50;
        }

        $query = UserAction::query()
            ->with(['item.model', 'item.category'])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($q) use ($like) {
                $q->where('username', 'ilike', $like)
                    ->orWhere('action_type', 'ilike', $like);
            });
        }

        $actions = $query->paginate($perPage)->withQueryString();

        return view('admin.user-actions.index', [
            'actions' => $actions,
            'search' => $search,
            'perPage' => $perPage,
        ]);
    }
}
