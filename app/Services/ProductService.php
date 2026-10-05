<?php

namespace App\Services;

use App\Enums\ProductStatus;
use App\Enums\TransactionType;
use App\Models\Product;
use App\Models\ProductExpense;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductService
{
    public function __construct(
        private readonly FinanceService $finance,
        private readonly NotificationService $notifications,
    ) {
    }

    public function create(array $data, array $photos = []): Product
    {
        return DB::transaction(function () use ($data, $photos) {
            $registerPurchase = (bool) ($data['register_purchase'] ?? false);
            unset($data['register_purchase'], $data['photos'], $data['remove_photos']);

            $data['initial_quantity'] = $data['quantity'];
            $data['photos'] = $this->storePhotos($photos);

            $product = Product::create($data);

            if ($registerPurchase) {
                $this->finance->record(
                    TransactionType::Saida,
                    'compra_produto',
                    "Compra: {$product->fullName()}",
                    (float) $product->purchase_price * $product->initial_quantity,
                    $product->purchase_date ?? now(),
                    null,
                    'compras',
                    $product,
                );
            }

            AuditService::log('produto_cadastrado', "Produto {$product->fullName()} cadastrado", $product);

            if ($product->status === ProductStatus::Manutencao) {
                $this->notifyMaintenance($product);
            }

            return $product;
        });
    }

    public function update(Product $product, array $data, array $photos = [], array $remove = []): Product
    {
        $oldStatus = $product->status;
        unset($data['register_purchase'], $data['photos'], $data['remove_photos']);

        $current = array_values(array_diff($product->photos ?? [], $remove));
        foreach (array_intersect($product->photos ?? [], $remove) as $path) {
            Storage::disk('public')->delete($path);
        }

        $data['photos'] = array_merge($current, $this->storePhotos($photos));

        if ((int) $data['quantity'] > $product->initial_quantity) {
            $data['initial_quantity'] = (int) $data['quantity'];
        }

        if ((int) $data['quantity'] === 0 && in_array($data['status'], ProductStatus::inStock(), true)) {
            $data['status'] = ProductStatus::Vendido->value;
        }

        $product->update($data);

        AuditService::log('produto_editado', "Produto {$product->fullName()} editado", $product);

        if ($oldStatus !== ProductStatus::Manutencao && $product->status === ProductStatus::Manutencao) {
            $this->notifyMaintenance($product);
        }

        return $product;
    }

    public function delete(Product $product): void
    {
        foreach ($product->photos ?? [] as $path) {
            Storage::disk('public')->delete($path);
        }

        AuditService::log('produto_excluido', "Produto {$product->fullName()} excluído");
        $product->delete();
    }

    public function addExpense(Product $product, array $data, bool $registerCash = true): ProductExpense
    {
        return DB::transaction(function () use ($product, $data, $registerCash) {
            $expense = $product->expenses()->create($data);
            $this->recalculate($product);

            if ($registerCash) {
                $this->finance->record(
                    TransactionType::Saida,
                    'despesa_produto',
                    "{$expense->description} · {$product->fullName()}",
                    (float) $expense->amount,
                    $expense->date,
                    null,
                    $expense->category,
                    $expense,
                );
            }

            AuditService::log('despesa_registrada', "Despesa {$expense->description} de ".money($expense->amount)." no produto {$product->fullName()}", $product);

            return $expense;
        });
    }

    public function removeExpense(ProductExpense $expense): void
    {
        DB::transaction(function () use ($expense) {
            $product = $expense->product;
            $this->finance->removeFor($expense);
            $expense->delete();
            $this->recalculate($product);
            AuditService::log('despesa_removida', "Despesa {$expense->description} removida do produto {$product->fullName()}", $product);
        });
    }

    public function recalculate(Product $product): void
    {
        $product->update(['expenses_total' => (float) $product->expenses()->sum('amount')]);
    }

    private function storePhotos(array $photos): array
    {
        return array_values(array_map(
            fn (UploadedFile $file) => $file->store('products', 'public'),
            array_filter($photos, fn ($file) => $file instanceof UploadedFile && $file->isValid()),
        ));
    }

    private function notifyMaintenance(Product $product): void
    {
        $this->notifications->notify('produto_manutencao', "Produto em manutenção · {$product->fullName()}", null, route('products.show', $product));
    }
}
