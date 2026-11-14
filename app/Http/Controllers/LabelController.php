<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Setting;
use App\Support\Barcode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LabelController extends Controller
{
    public const LAYOUTS = [
        'termica-50x30' => ['label' => 'Térmica 50 × 30 mm (1 coluna)', 'w' => 50, 'h' => 30, 'cols' => 1],
        'termica-40x25' => ['label' => 'Térmica 40 × 25 mm (1 coluna)', 'w' => 40, 'h' => 25, 'cols' => 1],
        'termica-33x22' => ['label' => 'Térmica 33 × 22 mm (3 colunas, bobina de 105 mm)', 'w' => 33, 'h' => 22, 'cols' => 3],
        'a4-21' => ['label' => 'Folha A4 · 21 etiquetas (63,5 × 38,1 mm)', 'w' => 63.5, 'h' => 38.1, 'cols' => 3, 'sheet' => true],
        'a4-65' => ['label' => 'Folha A4 · 65 etiquetas (38,1 × 21,2 mm)', 'w' => 38.1, 'h' => 21.2, 'cols' => 5, 'sheet' => true],
    ];

    /** Escolher produtos e quantidades. */
    public function index(Request $request): View
    {
        $selected = collect(explode(',', (string) $request->query('ids')))->filter(fn ($id) => ctype_digit($id))->map(fn ($id) => (int) $id)->all();

        return view('labels.index', [
            'products' => Product::orderBy('name')->limit(1000)->get(['id', 'name', 'model', 'barcode', 'sale_price', 'quantity', 'status']),
            'selected' => $selected,
            'layouts' => self::LAYOUTS,
            'layout' => Setting::get('label_layout', 'termica-50x30'),
        ]);
    }

    /** Página de impressão. */
    public function print(Request $request): View
    {
        $data = $request->validate([
            'qty' => ['required', 'array'],
            'qty.*' => ['nullable', 'integer', 'min:0', 'max:500'],
            'layout' => ['nullable', 'in:'.implode(',', array_keys(self::LAYOUTS))],
            'price' => ['nullable', 'boolean'],
            'store' => ['nullable', 'boolean'],
        ]);

        $qty = collect($data['qty'])->map(fn ($q) => (int) $q)->filter(fn ($q) => $q > 0);
        abort_if($qty->isEmpty(), 422, 'Escolha pelo menos um produto.');
        abort_if($qty->sum() > 2000, 422, 'No máximo 2.000 etiquetas por vez.');

        $layout = $data['layout'] ?? Setting::get('label_layout', 'termica-50x30');
        Setting::put('label_layout', $layout);

        $products = Product::whereIn('id', $qty->keys())->get()->keyBy('id');
        $missing = $products->filter(fn ($p) => ! $p->barcode);
        foreach ($missing as $product) {
            $this->assignCode($product);
        }

        $labels = [];
        foreach ($qty as $id => $count) {
            if ($product = $products->get($id)) {
                $svg = Barcode::svg($product->barcode, 40);
                for ($i = 0; $i < $count; $i++) {
                    $labels[] = ['product' => $product, 'svg' => $svg];
                }
            }
        }

        return view('labels.print', [
            'labels' => $labels,
            'layout' => self::LAYOUTS[$layout] + ['key' => $layout],
            'showPrice' => (bool) ($data['price'] ?? true),
            'showStore' => (bool) ($data['store'] ?? true),
            'store' => tenant()->company()->name,
            'generated' => $missing->count(),
        ]);
    }

    /** Produtos sem código de barras ganham um código interno (EAN-13 começando com 2). */
    private function assignCode(Product $product): void
    {
        $code = Barcode::internalEan(tenant_id(), $product->id);
        while (Product::where('barcode', $code)->where('id', '!=', $product->id)->exists()) {
            $code = Barcode::internalEan(tenant_id(), $product->id + random_int(100000, 999999));
        }
        $product->update(['barcode' => $code]);
    }
}
