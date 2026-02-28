<?php

namespace App\Http\Controllers;

use App\Http\Requests\Product\StoreRequest;
use App\Http\Requests\Product\UpdateRequest;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of products.
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $productsQuery = Product::query()
            ->with(['category:id,name,slug'])
            ->withSum('inventoryStocks as inventory_total_quantity', 'on_hand_qty');

        if ($q !== '') {
            $productsQuery->where(function ($query) use ($q): void {
                $query->where('name', 'like', '%' . $q . '%')
                    ->orWhere('code', 'like', '%' . $q . '%')
                    ->orWhere('amount', 'like', '%' . $q . '%');
            });
        }

        $reporting = [
            'total' => (clone $productsQuery)->count(),
            'added_this_week' => (clone $productsQuery)->where('created_at', '>=', now()->startOfWeek())->count(),
            'added_this_month' => (clone $productsQuery)->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
            'added_last_30_days' => (clone $productsQuery)->where('created_at', '>=', now()->subDays(30))->count(),
        ];

        $products = $productsQuery
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('modules.product.index', compact('products', 'reporting'));
    }

    /**
     * Show the form for creating a new product.
     */
    public function create()
    {
        $categories = ProductCategory::query()
            ->creatableForProducts()
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return view('modules.product.create', compact('categories'));
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(StoreRequest $request)
    {
        $data = $request->validated();
        $data['code'] = trim((string) ($data['code'] ?? ''));
        Product::create($data);

        return redirect()
            ->route('product.index')
            ->with('success', 'Product created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        return redirect()->route('product.edit', $product);
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit(Product $product)
    {
        $categories = ProductCategory::query()
            ->creatableForProducts()
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return view('modules.product.edit', compact('product', 'categories'));
    }

    /**
     * Update the specified product in storage.
     */
    public function update(UpdateRequest $request, Product $product)
    {
        $data = $request->validated();
        $data['code'] = trim((string) ($data['code'] ?? ''));
        $product->update($data);

        return redirect()
            ->route('product.index')
            ->with('success', 'Product updated successfully.');
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()
            ->route('product.index')
            ->with('success', 'Product deleted successfully.');
    }

}
