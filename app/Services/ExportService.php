<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\FinancialTransaction;
use App\Models\Installment;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use RuntimeException;
use ZipArchive;

class ExportService
{
    public const DATASETS = [
        'clientes' => 'Clientes',
        'produtos' => 'Produtos',
        'vendas' => 'Vendas',
        'itens_vendidos' => 'Itens das vendas',
        'parcelas' => 'Parcelas',
        'pagamentos' => 'Pagamentos recebidos',
        'movimentacoes' => 'Fluxo de caixa',
    ];

    public function csv(string $dataset): string
    {
        [$header, $rows] = $this->{'data'.str_replace('_', '', ucwords($dataset, '_'))}();

        $out = fopen('php://temp', 'r+');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, $header, ';', '"', '');

        foreach ($rows as $row) {
            fputcsv($out, array_map(fn ($v) => $this->cell($v), $row), ';', '"', '');
        }

        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return $csv;
    }

    public function zip(): string
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('A extensão zip do PHP não está ativa no servidor. Baixe as planilhas uma por uma.');
        }

        $path = tempnam(sys_get_temp_dir(), 'meucash').'.zip';
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach (array_keys(self::DATASETS) as $dataset) {
            $zip->addFromString("{$dataset}.csv", $this->csv($dataset));
        }

        $zip->close();
        AuditService::log('backup_gerado', 'Exportação completa dos dados da empresa');

        return $path;
    }

    private function cell(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_float($value) || (is_string($value) && preg_match('/^-?\d+\.\d{2}$/', $value))) {
            return number_format((float) $value, 2, ',', '');
        }

        $value = (string) $value;

        return preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }

    private function dataClientes(): array
    {
        return [
            ['id', 'nome', 'cpf_cnpj', 'telefone', 'whatsapp', 'email', 'cidade', 'endereco', 'observacoes', 'cadastrado_em'],
            Customer::orderBy('id')->get()->map(fn ($c) => [
                $c->id, $c->name, $c->document, $c->phone, $c->whatsapp, $c->email, $c->city, $c->address, $c->notes, $c->created_at?->format('d/m/Y H:i'),
            ]),
        ];
    }

    private function dataProdutos(): array
    {
        return [
            ['id', 'nome', 'categoria', 'marca', 'modelo', 'codigo_barras', 'status', 'quantidade', 'data_compra', 'preco_compra', 'despesas', 'custo_unitario', 'preco_venda', 'observacoes'],
            Product::with('category')->orderBy('id')->get()->map(fn ($p) => [
                $p->id, $p->name, $p->category?->name, $p->brand, $p->model, $p->barcode, $p->status->label(), $p->quantity,
                $p->purchase_date?->format('d/m/Y'), (float) $p->purchase_price, (float) $p->expenses_total, $p->unitCost(), (float) $p->sale_price, $p->notes,
            ]),
        ];
    }

    private function dataVendas(): array
    {
        return [
            ['id', 'codigo', 'data', 'cliente', 'vendedor', 'status', 'forma_pagamento', 'parcelas', 'subtotal', 'desconto', 'total', 'custo', 'lucro', 'comissao', 'entrada', 'observacoes'],
            Sale::with(['customer', 'user'])->orderBy('id')->get()->map(fn ($s) => [
                $s->id, $s->code, $s->sale_date->format('d/m/Y'), $s->customerName(), $s->user?->name, $s->status->label(), $s->payment_method->label(),
                $s->installments_count, (float) $s->subtotal, (float) $s->discount, (float) $s->total, (float) $s->cost_total, (float) $s->profit,
                (float) $s->commission, (float) $s->down_payment, $s->notes,
            ]),
        ];
    }

    private function dataItensVendidos(): array
    {
        return [
            ['venda', 'data', 'produto_id', 'descricao', 'quantidade', 'preco_unitario', 'custo_unitario', 'total'],
            SaleItem::with('sale')->orderBy('sale_id')->get()->map(fn ($i) => [
                $i->sale?->code, $i->sale?->sale_date->format('d/m/Y'), $i->product_id, $i->description, $i->quantity,
                (float) $i->unit_price, (float) $i->unit_cost, (float) $i->total,
            ]),
        ];
    }

    private function dataParcelas(): array
    {
        return [
            ['venda', 'cliente', 'parcela', 'vencimento', 'valor', 'status', 'pago_em', 'forma_pagamento'],
            Installment::with(['sale', 'customer'])->orderBy('due_date')->get()->map(fn ($i) => [
                $i->sale?->code, $i->customer?->name, $i->label(), $i->due_date->format('d/m/Y'), (float) $i->amount,
                $i->status->label(), $i->paid_at?->format('d/m/Y'), $i->payment_method?->label(),
            ]),
        ];
    }

    private function dataPagamentos(): array
    {
        return [
            ['data', 'venda', 'cliente', 'referente', 'forma', 'valor'],
            Payment::with(['sale', 'customer', 'installment'])->orderBy('paid_at')->get()->map(fn ($p) => [
                $p->paid_at->format('d/m/Y'), $p->sale?->code, $p->customer?->name,
                $p->installment ? 'Parcela '.$p->installment->label() : 'Entrada / à vista', $p->payment_method->label(), (float) $p->amount,
            ]),
        ];
    }

    private function dataMovimentacoes(): array
    {
        return [
            ['data', 'tipo', 'descricao', 'origem', 'categoria', 'forma', 'valor'],
            FinancialTransaction::orderBy('date')->orderBy('id')->get()->map(fn ($t) => [
                $t->date->format('d/m/Y'), $t->type->label(), $t->description, $t->originLabel(), $t->categoryLabel(),
                $t->payment_method?->label(), (float) $t->amount,
            ]),
        ];
    }
}
