<?php

namespace App\Http\Controllers;

use App\Enums\CategoryType;
use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::withCount(['products', 'services'])->orderBy('type')->orderBy('name')->get()->groupBy(fn ($c) => $c->type->value);

        return view('categories.index', ['categories' => $categories, 'types' => CategoryType::cases(), 'editing' => null]);
    }

    public function edit(Category $category): View
    {
        $categories = Category::withCount(['products', 'services'])->orderBy('type')->orderBy('name')->get()->groupBy(fn ($c) => $c->type->value);

        return view('categories.index', ['categories' => $categories, 'types' => CategoryType::cases(), 'editing' => $category]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        Category::create($request->validated());

        return redirect()->route('categories.index')->with('success', 'Categoria criada.');
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()->route('categories.index')->with('success', 'Categoria atualizada.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        return redirect()->route('categories.index')->with('success', 'Categoria excluída. Os itens dessa categoria ficaram sem categoria.');
    }
}
