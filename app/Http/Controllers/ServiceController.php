<?php

namespace App\Http\Controllers;

use App\Enums\CategoryType;
use App\Enums\PaymentMethod;
use App\Enums\ServiceStatus;
use App\Http\Requests\ExpenseRequest;
use App\Http\Requests\ServiceRequest;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Service;
use App\Models\ServiceExpense;
use App\Services\ServiceOrderService;
use App\Support\Period;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function __construct(private readonly ServiceOrderService $services)
    {
    }

    public function index(Request $request): View
    {
        $period = Period::fromRequest($request, 'ano');

        $query = Service::with(['customer', 'category'])
            ->whereBetween('date', [$period->startDate(), $period->endDate()])
            ->when($request->input('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->input('category_id'), fn ($q, $v) => $q->where('category_id', $v))
            ->when($request->input('q'), fn ($q, $term) => $q->where(fn ($w) => $w->where('name', 'like', "%{$term}%")->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$term}%"))));

        $billable = (clone $query)->billable()->get(['amount', 'expenses_total']);

        return view('services.index', [
            'services' => $query->latest('date')->latest('id')->paginate(20)->withQueryString(),
            'period' => $period,
            'categories' => Category::ofType(CategoryType::Servico)->get(),
            'totals' => [
                'amount' => (float) $billable->sum('amount'),
                'profit' => (float) $billable->sum(fn ($s) => $s->profit()),
                'count' => $billable->count(),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        return view('services.form', [
            'service' => new Service(['date' => now(), 'status' => ServiceStatus::EmAndamento, 'customer_id' => $request->integer('customer_id') ?: null]),
            'customers' => Customer::orderBy('name')->get(['id', 'name']),
            'categories' => Category::ofType(CategoryType::Servico)->get(),
            'methods' => PaymentMethod::options(),
        ]);
    }

    public function store(ServiceRequest $request): RedirectResponse
    {
        $service = $this->services->create($request->validated());

        return redirect()->route('services.show', $service)->with('success', 'Serviço registrado.');
    }

    public function show(Service $service): View
    {
        $service->load(['customer', 'category', 'expenses', 'installments', 'payments.installment']);

        return view('services.show', ['service' => $service, 'receivingMethods' => PaymentMethod::receiving()]);
    }

    public function edit(Service $service): View
    {
        return view('services.form', [
            'service' => $service,
            'customers' => Customer::orderBy('name')->get(['id', 'name']),
            'categories' => Category::ofType(CategoryType::Servico)->get(),
            'methods' => PaymentMethod::options(),
        ]);
    }

    public function update(ServiceRequest $request, Service $service): RedirectResponse
    {
        $this->services->update($service, $request->safe()->only(['name', 'customer_id', 'category_id', 'date', 'status', 'notes']));

        return redirect()->route('services.show', $service)->with('success', 'Serviço atualizado.');
    }

    public function cancel(Service $service): RedirectResponse
    {
        $this->services->cancel($service);

        return redirect()->route('services.show', $service)->with('success', 'Serviço cancelado e parcelas em aberto canceladas.');
    }

    public function storeExpense(ExpenseRequest $request, Service $service): RedirectResponse
    {
        $this->services->addExpense($service, $request->safe()->only(['description', 'amount', 'date', 'notes']));

        return redirect()->route('services.show', $service)->with('success', 'Despesa registrada.');
    }

    public function destroyExpense(Service $service, ServiceExpense $expense): RedirectResponse
    {
        abort_unless($expense->service_id === $service->id, 404);
        $this->services->removeExpense($expense);

        return redirect()->route('services.show', $service)->with('success', 'Despesa removida.');
    }
}
