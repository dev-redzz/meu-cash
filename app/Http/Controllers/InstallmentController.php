<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentRequest;
use App\Models\Installment;
use App\Services\InstallmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class InstallmentController extends Controller
{
    public function __construct(private readonly InstallmentService $installments)
    {
    }

    public function pay(PaymentRequest $request, Installment $installment): RedirectResponse
    {
        try {
            $this->installments->pay($installment, $request->input('paid_at'), $request->input('payment_method'), $request->input('notes'));
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Pagamento da parcela '.$installment->label().' registrado.');
    }

    public function updateDueDate(Request $request, Installment $installment): RedirectResponse
    {
        $request->validate(['due_date' => ['required', 'date']]);

        try {
            $this->installments->changeDueDate($installment, $request->input('due_date'));
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Vencimento alterado.');
    }
}
