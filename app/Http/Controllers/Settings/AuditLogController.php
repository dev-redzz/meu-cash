<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\Period;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $period = Period::fromRequest($request, 'mes');

        $logs = AuditLog::with('user')
            ->whereBetween('created_at', [$period->start, $period->end])
            ->when($request->input('user_id'), fn ($q, $v) => $q->where('user_id', $v))
            ->when($request->input('action'), fn ($q, $v) => $q->where('action', $v))
            ->latest()
            ->paginate(40)
            ->withQueryString();

        return view('settings.audit', [
            'logs' => $logs,
            'period' => $period,
            'users' => User::orderBy('name')->get(['id', 'name']),
            'actions' => AuditLog::ACTIONS,
        ]);
    }
}
