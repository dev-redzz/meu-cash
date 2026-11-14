<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Installment;
use App\Models\OnlinePayment;
use App\Services\Payments\PixService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PixController extends Controller
{
    public function __construct(private readonly PixService $pix)
    {
    }

    public function create(Request $request, int $installment): RedirectResponse
    {
        $installment = $this->find($request, $installment);

        if (! $this->pix->enabled()) {
            return redirect()->route('portal.home')->with('error', 'Esta loja ainda não recebe Pix pela área do cliente.');
        }

        if (! $installment->isOpen()) {
            return redirect()->route('portal.home')->with('success', 'Esta parcela já está paga.');
        }

        try {
            $this->pix->forInstallment($installment);
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('portal.home')->with('error', 'Não foi possível gerar o Pix agora. Tente de novo em alguns minutos ou fale com a loja.');
        }

        return redirect()->route('portal.pix.show', $installment->id);
    }

    public function show(Request $request, int $installment): View|RedirectResponse
    {
        $installment = $this->find($request, $installment);
        $payment = $this->latest($installment);

        if (! $payment) {
            return redirect()->route('portal.home');
        }

        return view('portal.pix', ['installment' => $installment, 'payment' => $payment]);
    }

    public function status(Request $request, int $installment): JsonResponse
    {
        $installment = $this->find($request, $installment);
        $payment = $this->latest($installment);

        if ($payment && $payment->status === 'pendente') {
            try {
                $payment = $this->pix->refresh($payment);
            } catch (\Throwable $e) {
                if ($e->getCode() !== 404) {
                    report($e);
                }
            }
        }

        return response()->json([
            'status' => $payment?->status ?? 'nenhum',
            'paid' => $payment?->isPaid() || ! $installment->fresh()->isOpen(),
        ]);
    }

    private function find(Request $request, int $id): Installment
    {
        /** @var Customer $customer */
        $customer = $request->attributes->get('portalCustomer');

        return Installment::with(['sale', 'customer'])->where('customer_id', $customer->id)->findOrFail($id);
    }

    private function latest(Installment $installment): ?OnlinePayment
    {
        return OnlinePayment::where('installment_id', $installment->id)->whereNotNull('provider_id')->latest('id')->first();
    }
}
