<?php

namespace App\Services;

use App\Enums\CategoryType;
use App\Enums\ProductStatus;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ProductImportService
{
    public const COLUMNS = ['nome', 'categoria', 'marca', 'modelo', 'codigo_barras', 'quantidade', 'preco_compra', 'preco_venda', 'data_compra', 'observacoes'];

    private const MAX_ROWS = 2000;

    public function __construct(private readonly FinanceService $finance)
    {
    }

    public function template(): string
    {
        $rows = [
            self::COLUMNS,
            ['Capinha iPhone 13', 'Acessórios', 'Genérica', 'Silicone', '7891234567890', '10', '8,00', '25,00', date('d/m/Y'), ''],
            ['Fone Bluetooth X1', 'Eletrônicos', 'Xtech', 'X1', '', '3', '45,00', '89,90', date('d/m/Y'), 'Lote de outubro'],
        ];

        return "\xEF\xBB\xBF".collect($rows)->map(fn ($row) => implode(';', $row))->implode("\r\n")."\r\n";
    }

    /**
     * @return array{created: int, errors: array<int, string>}
     */
    public function import(string $contents, bool $registerPurchase): array
    {
        $rows = $this->parse($contents);

        if (count($rows) < 2) {
            throw new RuntimeException('A planilha está vazia. Use o modelo e preencha a partir da segunda linha.');
        }

        $header = array_map(fn ($h) => $this->normalize($h), array_shift($rows));

        foreach (['nome', 'preco_venda'] as $required) {
            if (! in_array($required, $header, true)) {
                throw new RuntimeException("A coluna \"{$required}\" não foi encontrada. Use o modelo para importar.");
            }
        }

        if (count($rows) > self::MAX_ROWS) {
            throw new RuntimeException('Importe no máximo '.self::MAX_ROWS.' produtos por vez.');
        }

        $existingBarcodes = Product::whereNotNull('barcode')->pluck('barcode')->flip();
        $seenBarcodes = [];
        $valid = [];
        $errors = [];

        foreach ($rows as $index => $values) {
            $line = $index + 2;

            if (count(array_filter($values, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            $row = [];
            foreach ($header as $i => $column) {
                $row[$column] = trim((string) ($values[$i] ?? ''));
            }

            $name = $row['nome'] ?? '';
            $sale = to_decimal($row['preco_venda'] ?? null);
            $purchase = to_decimal($row['preco_compra'] ?? null) ?? 0;
            $qty = ($row['quantidade'] ?? '') === '' ? 1 : (ctype_digit($row['quantidade']) ? (int) $row['quantidade'] : null);
            $barcode = ($row['codigo_barras'] ?? '') !== '' ? $row['codigo_barras'] : null;
            $date = $this->date($row['data_compra'] ?? '');

            $problem = match (true) {
                $name === '' => 'nome vazio',
                mb_strlen($name) > 255 => 'nome muito longo',
                $sale === null || $sale < 0 => 'preço de venda inválido',
                $purchase < 0 => 'preço de compra inválido',
                $qty === null || $qty > 100000 => 'quantidade inválida',
                $date === false => 'data da compra inválida (use dd/mm/aaaa)',
                $barcode && mb_strlen($barcode) > 50 => 'código de barras muito longo',
                $barcode && isset($existingBarcodes[$barcode]) => "código de barras {$barcode} já cadastrado",
                $barcode && isset($seenBarcodes[$barcode]) => "código de barras {$barcode} repetido na planilha",
                default => null,
            };

            if ($problem) {
                $errors[$line] = $problem;
                continue;
            }

            if ($barcode) {
                $seenBarcodes[$barcode] = true;
            }

            $valid[] = [
                'name' => $name,
                'category' => mb_substr($row['categoria'] ?? '', 0, 100),
                'brand' => mb_substr($row['marca'] ?? '', 0, 100) ?: null,
                'model' => mb_substr($row['modelo'] ?? '', 0, 100) ?: null,
                'barcode' => $barcode,
                'quantity' => $qty,
                'purchase_price' => $purchase,
                'sale_price' => $sale,
                'purchase_date' => $date ?: now()->toDateString(),
                'notes' => ($row['observacoes'] ?? '') ?: null,
            ];
        }

        if ($errors) {
            return ['created' => 0, 'errors' => $errors];
        }

        DB::transaction(function () use ($valid, $registerPurchase) {
            $categories = Category::ofType(CategoryType::Produto)->get()->keyBy(fn ($c) => Str::lower($c->name));

            foreach ($valid as $data) {
                $categoryId = null;

                if ($data['category'] !== '') {
                    $key = Str::lower($data['category']);
                    $categories[$key] ??= Category::create(['name' => $data['category'], 'type' => CategoryType::Produto->value]);
                    $categoryId = $categories[$key]->id;
                }

                $product = Product::create([
                    'category_id' => $categoryId,
                    'name' => $data['name'],
                    'brand' => $data['brand'],
                    'model' => $data['model'],
                    'barcode' => $data['barcode'],
                    'purchase_date' => $data['purchase_date'],
                    'purchase_price' => $data['purchase_price'],
                    'sale_price' => $data['sale_price'],
                    'initial_quantity' => max(1, $data['quantity']),
                    'quantity' => $data['quantity'],
                    'status' => $data['quantity'] > 0 ? ProductStatus::Disponivel : ProductStatus::Vendido,
                    'notes' => $data['notes'],
                ]);

                if ($registerPurchase) {
                    $this->finance->record(TransactionType::Saida, 'compra_produto', "Compra: {$product->fullName()}", $data['purchase_price'] * $data['quantity'], $data['purchase_date'], null, 'compras', $product);
                }
            }
        });

        AuditService::log('produto_cadastrado', count($valid).' produtos importados por planilha');

        return ['created' => count($valid), 'errors' => []];
    }

    private function parse(string $contents): array
    {
        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents);

        if (! mb_check_encoding($contents, 'UTF-8')) {
            $contents = mb_convert_encoding($contents, 'UTF-8', 'Windows-1252');
        }

        $firstLine = strtok($contents, "\n");
        $delimiter = substr_count((string) $firstLine, ';') >= substr_count((string) $firstLine, ',') ? ';' : ',';

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $contents);
        rewind($handle);

        $rows = [];
        while (($row = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
            $rows[] = $row;
        }
        fclose($handle);

        return $rows;
    }

    private function normalize(?string $value): string
    {
        return (string) Str::of((string) $value)->ascii()->lower()->trim()->replace([' ', '-'], '_');
    }

    private function date(string $value): string|false|null
    {
        if ($value === '') {
            return null;
        }

        foreach (['d/m/Y', 'Y-m-d', 'd/m/y'] as $format) {
            try {
                $date = Carbon::createFromFormat('!'.$format, $value);
                if ($date && $date->format($format) === $value) {
                    return $date->toDateString();
                }
            } catch (\Throwable) {
            }
        }

        return false;
    }
}
