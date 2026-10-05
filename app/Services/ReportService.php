<?php

namespace App\Services;

use App\Enums\InstallmentStatus;
use App\Enums\TransactionType;
use App\Models\Customer;
use App\Models\FinancialTransaction;
use App\Models\Installment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Service;
use App\Support\Period;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public const TYPES = [
        'vendas' => 'Vendas',
        'lucros' => 'Lucros',
        'despesas' => 'Despesas',
        'estoque' => 'Estoque',
        'clientes' => 'Clientes',
        'receber' => 'Parcelas',
        'fluxo' => 'Fluxo de caixa',
        'produtos_vendidos' => 'Produtos vendidos',
        'servicos' => 'Serviços vendidos',
    ];

    public function __construct(private readonly FinanceService $finance)
    {
    }

    public function build(string $type, Period $period): array
    {
        $type = array_key_exists($type, self::TYPES) ? $type : 'vendas';
        $report = $this->{'report'.str_replace('_', '', ucwords($type, '_'))}($period);

        return $report + ['type' => $type, 'title' => self::TYPES[$type], 'period' => $period->label()];
    }

    private function reportVendas(Period $period): array
    {
        $sales = Sale::with('customer')->completed()->whereBetween('sale_date', [$period->startDate(), $period->endDate()])->orderBy('sale_date')->get();

        return [
            'columns' => ['Data', 'Código', 'Cliente', 'Pagamento', 'Total', 'Custo', 'Lucro'],
            'align' => [5 => 'end', 6 => 'end', 4 => 'end'],
            'rows' => $sales->map(fn ($s) => [
                $s->sale_date->format('d/m/Y'), $s->code, $s->customerName(), $s->payment_method->label(),
                money($s->total), money($s->cost_total), money($s->profit),
            ])->all(),
            'totals' => ['Vendas' => $sales->count(), 'Faturamento' => money($sales->sum('total')), 'Custo' => money($sales->sum('cost_total')), 'Lucro' => money($sales->sum('profit'))],
        ];
    }

    private function reportLucros(Period $period): array
    {
        $rows = [];
        $total = 0;

        Sale::with('customer')->completed()->whereBetween('sale_date', [$period->startDate(), $period->endDate()])->orderBy('sale_date')->get()
            ->each(function ($s) use (&$rows, &$total) {
                $rows[] = [$s->sale_date->format('d/m/Y'), 'Venda', $s->code.' · '.$s->customerName(), money($s->total), money($s->cost_total), money($s->profit)];
                $total += (float) $s->profit;
            });

        Service::with('customer')->billable()->whereBetween('date', [$period->startDate(), $period->endDate()])->orderBy('date')->get()
            ->each(function ($s) use (&$rows, &$total) {
                $rows[] = [$s->date->format('d/m/Y'), 'Serviço', $s->name.' · '.($s->customer?->name ?? '—'), money($s->amount), money($s->expenses_total), money($s->profit())];
                $total += $s->profit();
            });

        usort($rows, fn ($a, $b) => strcmp(implode('', array_reverse(explode('/', $a[0]))), implode('', array_reverse(explode('/', $b[0])))));

        return [
            'columns' => ['Data', 'Tipo', 'Descrição', 'Valor', 'Custo', 'Lucro'],
            'align' => [3 => 'end', 4 => 'end', 5 => 'end'],
            'rows' => $rows,
            'totals' => ['Lançamentos' => count($rows), 'Lucro total' => money($total)],
        ];
    }

    private function reportDespesas(Period $period): array
    {
        $items = FinancialTransaction::between($period->start, $period->end)->where('type', TransactionType::Saida->value)->orderBy('date')->get();

        return [
            'columns' => ['Data', 'Descrição', 'Origem', 'Categoria', 'Valor'],
            'align' => [4 => 'end'],
            'rows' => $items->map(fn ($t) => [$t->date->format('d/m/Y'), $t->description, $t->originLabel(), $t->categoryLabel(), money($t->amount)])->all(),
            'totals' => ['Lançamentos' => $items->count(), 'Total' => money($items->sum('amount'))],
        ];
    }

    private function reportEstoque(Period $period): array
    {
        $products = Product::with('category')->inStock()->orderBy('name')->get();
        $stock = $this->finance->stockTotals();

        return [
            'columns' => ['Produto', 'Categoria', 'Status', 'Qtd.', 'Custo un.', 'Preço un.', 'Investido', 'Venda estimada'],
            'align' => [3 => 'end', 4 => 'end', 5 => 'end', 6 => 'end', 7 => 'end'],
            'rows' => $products->map(fn ($p) => [
                $p->fullName(), $p->category?->name ?? '—', $p->status->label(), $p->quantity,
                money($p->unitCost()), money($p->sale_price), money($p->stockInvested()), money($p->stockSaleValue()),
            ])->all(),
            'totals' => ['Unidades' => $stock['units'], 'Investido' => money($stock['invested']), 'Venda estimada' => money($stock['sale_value']), 'Lucro potencial' => money($stock['potential_profit'])],
        ];
    }

    private function reportClientes(Period $period): array
    {
        $customers = Customer::query()
            ->withSum(['sales as purchases' => fn ($q) => $q->completed()->whereBetween('sale_date', [$period->startDate(), $period->endDate()])], 'total')
            ->withSum(['payments as paid' => fn ($q) => $q->whereBetween('paid_at', [$period->startDate(), $period->endDate()])], 'amount')
            ->withSum(['installments as open' => fn ($q) => $q->whereIn('status', InstallmentStatus::open())], 'amount')
            ->orderBy('name')
            ->get()
            ->filter(fn ($c) => $c->purchases || $c->paid || $c->open);

        return [
            'columns' => ['Cliente', 'Telefone', 'Compras no período', 'Pago no período', 'Em aberto'],
            'align' => [2 => 'end', 3 => 'end', 4 => 'end'],
            'rows' => $customers->map(fn ($c) => [$c->name, $c->whatsapp ?: $c->phone ?: '—', money($c->purchases), money($c->paid), money($c->open)])->values()->all(),
            'totals' => ['Clientes' => $customers->count(), 'Compras' => money($customers->sum('purchases')), 'Recebido' => money($customers->sum('paid')), 'Em aberto' => money($customers->sum('open'))],
        ];
    }

    private function reportReceber(Period $period): array
    {
        $items = Installment::with(['customer', 'sale', 'service'])
            ->whereBetween('due_date', [$period->startDate(), $period->endDate()])
            ->where('status', '!=', InstallmentStatus::Cancelado->value)
            ->orderBy('due_date')->get();

        $open = $items->filter(fn ($i) => $i->isOpen());

        return [
            'columns' => ['Vencimento', 'Cliente', 'Origem', 'Parcela', 'Status', 'Valor'],
            'align' => [5 => 'end'],
            'rows' => $items->map(fn ($i) => [$i->due_date->format('d/m/Y'), $i->customer?->name ?? '—', $i->originLabel(), $i->label(), $i->status->label(), money($i->amount)])->all(),
            'totals' => ['Parcelas' => $items->count(), 'Em aberto' => money($open->sum('amount')), 'Recebido' => money($items->sum('amount') - $open->sum('amount'))],
        ];
    }

    private function reportFluxo(Period $period): array
    {
        $items = FinancialTransaction::between($period->start, $period->end)->orderBy('date')->orderBy('id')->get();
        $totals = $this->finance->totalsBetween($period->start, $period->end);

        return [
            'columns' => ['Data', 'Tipo', 'Descrição', 'Origem', 'Forma', 'Valor'],
            'align' => [5 => 'end'],
            'rows' => $items->map(fn ($t) => [
                $t->date->format('d/m/Y'), $t->type->label(), $t->description, $t->originLabel(),
                $t->payment_method?->label() ?? '—', ($t->type === TransactionType::Saida ? '- ' : '').money($t->amount),
            ])->all(),
            'totals' => ['Entradas' => money($totals['in']), 'Saídas' => money($totals['out']), 'Saldo do período' => money($totals['net'])],
        ];
    }

    private function reportProdutosVendidos(Period $period): array
    {
        $items = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', 'concluida')
            ->whereBetween('sales.sale_date', [$period->startDate(), $period->endDate()])
            ->select('sale_items.description', DB::raw('SUM(sale_items.quantity) as qty'), DB::raw('SUM(sale_items.total) as revenue'), DB::raw('SUM(sale_items.total - sale_items.unit_cost * sale_items.quantity) as profit'))
            ->groupBy('sale_items.description')
            ->orderByDesc('qty')
            ->get();

        return [
            'columns' => ['Produto / item', 'Quantidade', 'Faturamento', 'Lucro'],
            'align' => [1 => 'end', 2 => 'end', 3 => 'end'],
            'rows' => $items->map(fn ($i) => [$i->description, (int) $i->qty, money($i->revenue), money($i->profit)])->all(),
            'totals' => ['Itens vendidos' => (int) $items->sum('qty'), 'Faturamento' => money($items->sum('revenue')), 'Lucro' => money($items->sum('profit'))],
        ];
    }

    private function reportServicos(Period $period): array
    {
        $services = Service::with(['customer', 'category'])->whereBetween('date', [$period->startDate(), $period->endDate()])->orderBy('date')->get();
        $billable = $services->filter(fn ($s) => in_array($s->status->value, ['em_andamento', 'concluido'], true));

        return [
            'columns' => ['Data', 'Serviço', 'Cliente', 'Status', 'Valor', 'Despesas', 'Lucro'],
            'align' => [4 => 'end', 5 => 'end', 6 => 'end'],
            'rows' => $services->map(fn ($s) => [$s->date->format('d/m/Y'), $s->name, $s->customer?->name ?? '—', $s->status->label(), money($s->amount), money($s->expenses_total), money($s->profit())])->all(),
            'totals' => ['Serviços' => $services->count(), 'Faturado' => money($billable->sum('amount')), 'Lucro' => money($billable->sum(fn ($s) => $s->profit()))],
        ];
    }
}
