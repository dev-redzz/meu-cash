<?php

namespace App\Http\Controllers;

use App\Models\Installment;
use App\Services\WhatsAppService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class WhatsAppController extends Controller
{
    public function installment(Request $request, Installment $installment, WhatsAppService $whatsapp): RedirectResponse
    {
        try {
            $link = $whatsapp->send($installment->load('customer'), $request->input('tipo'));
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->away($link);
    }
}
