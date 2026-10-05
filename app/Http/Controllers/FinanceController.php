<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\TransactionType;
use App\Http\Requests\TransactionRequest;
use App\Models\FinancialTransaction;
use App\Services\AuditService;
use App\Services\FinanceService;
use App\Support\Period;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinanceController extends Controller
{
    public function __construct(private readonly FinanceService $finance)
    {
    }

    public function index(Request $request): View
    {
        $period = Period::fromRequest($request, 'mes');

        $query = FinancialTransaction::between($period->start, $period->end)
            ->when($request->input('tipo'), fn ($q, $v) => $q->where('type', $v))
            ->when($request->input('origem'), fn ($q, $v) => $q->where('origin', $v))
            ->when($request->input('q'), fn ($q, $v) => $q->where('description', 'like', "%{$v}%"));

        $byOrigin = FinancialTransaction::between($period->start, $period->end)
            ->selectRaw('type, origin, SUM(amount) as total')
            ->groupBy('type', 'origin')
            ->get()
            ->groupBy(fn ($row) => $row->type->value);

        return view('finance.index', [
            'period' => $period,
            'summary' => $this->finance->summary($period),
            'byOrigin' => $byOrigin,
            'transactions' => $query->with('user')->latest('date')->latest('id')->paginate(30)->withQueryString(),
            'categories' => config('meucash.transaction_categories'),
            'origins' => config('meucash.origins'),
            'receivingMethods' => PaymentMethod::receiving(),
        ]);
    }

    public function store(TransactionRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $transaction = $this->finance->record(
            TransactionType::from($data['type']),
            'manual',
            $data['description'],
            (float) $data['amount'],
            $data['date'],
            $data['payment_method'] ?? null,
            $data['category'],
        );

        AuditService::log('lancamento_criado', $transaction->type->label().": {$transaction->description} (".money($transaction->amount).')', $transaction);

        return back()->with('success', 'Lançamento registrado.');
    }

    public function destroy(FinancialTransaction $transaction): RedirectResponse
    {
        abort_unless($transaction->isManual(), 403, 'Somente lançamentos manuais podem ser excluídos por aqui.');

        AuditService::log('lancamento_excluido', $transaction->type->label().": {$transaction->description} (".money($transaction->amount).')');
        $transaction->delete();

        return back()->with('success', 'Lançamento excluído.');
    }
}
