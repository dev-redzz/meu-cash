<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = AppNotification::query()
            ->when($request->input('filtro') === 'nao_lidas', fn ($q) => $q->unread())
            ->when($request->input('tipo'), fn ($q, $v) => $q->where('type', $v))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('notifications.index', ['notifications' => $notifications, 'types' => AppNotification::TYPES]);
    }

    public function open(AppNotification $notification): RedirectResponse
    {
        $notification->update(['read_at' => $notification->read_at ?? now()]);

        return $notification->url ? redirect()->to($notification->url) : back();
    }

    public function readAll(): RedirectResponse
    {
        AppNotification::unread()->update(['read_at' => now()]);

        return back()->with('success', 'Todas as notificações foram marcadas como lidas.');
    }

    public function refresh(NotificationService $service): RedirectResponse
    {
        $service->sync();

        return back()->with('success', 'Vencimentos verificados.');
    }
}
