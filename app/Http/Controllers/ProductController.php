<?php

namespace App\Http\Controllers;

use App\Http\Requests\Product\StoreRequest;
use App\Http\Requests\Product\UpdateRequest;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use App\Rules\ProductMediaFileRule;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Display a listing of products.
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $productsQuery = Product::query()
            ->with(['category:id,name,slug', 'unit:id,name,symbol'])
            ->withSum('inventoryStocks as inventory_total_quantity', 'on_hand_qty')
            ->withCount('variants')
            ->withCount('mediaFiles');

        if ($q !== '') {
            $productsQuery->where(function ($query) use ($q): void {
                $query->where('name', 'like', '%' . $q . '%')
                    ->orWhere('sku', 'like', '%' . $q . '%')
                    ->orWhere('description', 'like', '%' . $q . '%');
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

        $units = Unit::query()->orderBy('name')->get(['id', 'name', 'symbol']);

        return view('modules.product.create', compact('categories', 'units'));
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(StoreRequest $request)
    {
        $data = $request->validated();
        $variants = $data['variants'] ?? [];
        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        $mediaFiles = $request->file('media_files', []);
        unset($data['media_files'], $data['variants']);

        DB::transaction(function () use ($data, $variants, $mediaFiles): void {
            $product = Product::create($data);
            $this->syncProductVariants($product, $variants);
            $this->storeProductMedia($product, $mediaFiles);
        });

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
        $existingVariants = $product->variants()
            ->orderBy('id')
            ->get(['id', 'sku', 'size', 'color', 'material']);

        $existingMedia = $product->mediaFiles()
            ->orderBy('sort_order')
            ->get();

        $categories = ProductCategory::query()
            ->creatableForProducts()
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        $units = Unit::query()->orderBy('name')->get(['id', 'name', 'symbol']);

        return view('modules.product.edit', compact('product', 'categories', 'existingMedia', 'existingVariants', 'units'));
    }

    /**
     * Update the specified product in storage.
     */
    public function update(UpdateRequest $request, Product $product)
    {
        $data = $request->validated();
        $variants = $data['variants'] ?? [];
        $originalUnitId = $product->unit_id;
        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        $mediaFiles = $request->file('media_files', []);
        $removeMediaIds = $data['remove_media_ids'] ?? [];
        unset($data['media_files'], $data['remove_media_ids'], $data['variants']);

        DB::transaction(function () use ($product, $data, $variants, $mediaFiles, $removeMediaIds, $originalUnitId): void {
            $product->update($data);

            if ($product->unit_id && $product->unit_id !== $originalUnitId) {
                $this->syncRelatedUnitReferences($product);
            }

            $this->syncProductVariants($product, $variants);

            if (!empty($removeMediaIds)) {
                $this->deleteProductMediaByIds($product, $removeMediaIds);
            }

            $this->storeProductMedia($product, $mediaFiles);
        });

        return redirect()
            ->route('product.index')
            ->with('success', 'Product updated successfully.');
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy(Product $product)
    {
        $media = $product->mediaFiles()->get();

        foreach ($media as $item) {
            Storage::disk('public')->delete($item->file_path);
        }

        $product->delete();

        return redirect()
            ->route('product.index')
            ->with('success', 'Product deleted successfully.');
    }

    /**
     * Persist uploaded media files for a product.
     *
     * @param  array<int, UploadedFile>  $mediaFiles
     */
    private function storeProductMedia(Product $product, array $mediaFiles): void
    {
        if (empty($mediaFiles)) {
            return;
        }

        $currentMaxSort = (int) ($product->mediaFiles()->max('sort_order') ?? 0);

        foreach ($mediaFiles as $index => $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            $mimeType = (string) $file->getMimeType();
            $mediaType = str_starts_with($mimeType, 'video/') ? 'video' : 'image';
            $durationSeconds = $mediaType === 'video'
                ? ProductMediaFileRule::detectVideoDurationSeconds($file)
                : null;

            $path = $file->store('products/media', 'public');

            $product->mediaFiles()->create([
                'file_path' => $path,
                'media_type' => $mediaType,
                'mime_type' => $mimeType,
                'size_bytes' => (int) $file->getSize(),
                'duration_seconds' => $durationSeconds,
                'sort_order' => $currentMaxSort + $index + 1,
            ]);
        }
    }

    /**
     * Delete selected media records and their files.
     *
     * @param  array<int, int|string>  $ids
     */
    private function deleteProductMediaByIds(Product $product, array $ids): void
    {
        $mediaItems = $product->mediaFiles()
            ->whereIn('id', $ids)
            ->get();

        foreach ($mediaItems as $item) {
            Storage::disk('public')->delete($item->file_path);
            $item->delete();
        }
    }

    /**
     * Replace product variants from submitted rows.
     *
     * @param  array<int, array<string, mixed>>  $variants
     */
    private function syncProductVariants(Product $product, array $variants): void
    {
        $product->variants()->delete();

        foreach ($variants as $variant) {
            $product->variants()->create([
                'sku' => trim((string) ($variant['sku'] ?? '')),
                'size' => filled($variant['size'] ?? null) ? trim((string) $variant['size']) : null,
                'color' => filled($variant['color'] ?? null) ? trim((string) $variant['color']) : null,
                'material' => filled($variant['material'] ?? null) ? trim((string) $variant['material']) : null,
            ]);
        }
    }

    /**
     * Keep downstream records aligned with the product measurement unit.
     */
    private function syncRelatedUnitReferences(Product $product): void
    {
        $unitId = (int) $product->unit_id;

        $product->inventoryStocks()->update(['unit_id' => $unitId]);
        $product->rawMaterialPurchases()->update(['unit_id' => $unitId]);
    }
}
