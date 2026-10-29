<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $customers = Customer::search($request->input('q'))
            ->withSum('openInstallments as open_amount', 'amount')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('customers.index', compact('customers'));
    }

    public function create(): View
    {
        return view('customers.form', ['customer' => new Customer()]);
    }

    public function store(CustomerRequest $request): RedirectResponse
    {
        $customer = Customer::create($request->validated());
        AuditService::log('cliente_cadastrado', "Cliente {$customer->name} cadastrado", $customer);

        if ($request->input('redirect') === 'sale') {
            return redirect()->route('sales.create', ['customer_id' => $customer->id])->with('success', 'Cliente cadastrado.');
        }

        return redirect()->route('customers.show', $customer)->with('success', 'Cliente cadastrado.');
    }

    public function show(Customer $customer): View
    {
        $customer->ensureAccessCode();
        $today = Carbon::today()->toDateString();

        $sales = $customer->sales()->latest('sale_date')->get();
        $installments = $customer->installments()->with('sale')->open()->orderBy('due_date')->get();
        $payments = $customer->payments()->with(['installment', 'sale'])->latest('paid_at')->get();

        $totals = [
            'purchases' => (float) $sales->where('status.value', 'concluida')->sum('total'),
            'paid' => (float) $payments->sum('amount'),
            'open' => (float) $installments->sum('amount'),
            'overdue' => (float) $installments->filter(fn ($i) => $i->due_date->toDateString() < $today)->sum('amount'),
        ];

        return view('customers.show', compact('customer', 'sales', 'installments', 'payments', 'totals'));
    }

    public function edit(Customer $customer): View
    {
        return view('customers.form', compact('customer'));
    }

    public function update(CustomerRequest $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->validated());
        AuditService::log('cliente_editado', "Cliente {$customer->name} editado", $customer);

        return redirect()->route('customers.show', $customer)->with('success', 'Cliente atualizado.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        if ($customer->sales()->exists()) {
            return back()->with('error', 'Este cliente tem vendas registradas e não pode ser excluído.');
        }

        $undo = app(\App\Services\UndoService::class);
        $snapshot = \Illuminate\Support\Facades\DB::transaction(function () use ($customer, $undo) {
            $snapshot = $undo->capture("Cliente {$customer->name}", ['customers' => [$customer->id]], collect(['installments', 'payments', 'whatsapp_messages'])
                ->map(fn ($table) => ['table' => $table, 'column' => 'customer_id', 'value' => $customer->id])->all());
            AuditService::log('cliente_excluido', "Cliente {$customer->name} excluído");
            $customer->delete();

            return $snapshot;
        });
        $undo->flash($snapshot, 'Cliente excluído.');

        return redirect()->route('customers.index');
    }
}
