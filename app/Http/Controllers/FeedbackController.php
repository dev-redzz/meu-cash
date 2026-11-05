<?php

namespace App\Http\Controllers;

use App\Mail\FeedbackMail;
use App\Models\Feedback;
use App\Services\ErrorMonitor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class FeedbackController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(Feedback::TYPES))],
            'message' => ['required', 'string', 'min:5', 'max:3000'],
            'page' => ['nullable', 'string', 'max:500'],
        ], [], ['message' => 'mensagem']);

        $feedback = Feedback::create([
            'company_id' => tenant_id(),
            'user_id' => $request->user()->id,
            'type' => $data['type'],
            'message' => $data['message'],
            'page' => Str::limit((string) ($data['page'] ?? url()->previous()), 490, ''),
        ]);

        if ($to = app(ErrorMonitor::class)->recipients()) {
            dispatch(function () use ($to, $feedback) {
                try {
                    Mail::to($to)->send(new FeedbackMail($feedback->load(['company', 'user'])));
                } catch (Throwable) {
                }
            })->afterResponse();
        }

        return back()->with('success', 'Obrigado! Sua mensagem chegou para a equipe do Meu Cash.');
    }
}
