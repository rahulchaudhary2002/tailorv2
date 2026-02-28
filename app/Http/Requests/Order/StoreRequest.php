<?php

namespace App\Http\Requests\Order;

use App\Models\Order;
use App\Models\ProductCategory;
use App\Models\GarmentTypeTailoringPackage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'ordered_at' => ['required', 'date'],
            'delivery_due_at' => ['required', 'date', 'after_or_equal:ordered_at'],
            'status' => ['required', 'string', Rule::in(Order::creatableStatuses())],
            'worker_id' => ['nullable', 'integer', 'exists:users,id'],
            'worker_deadline_at' => ['nullable', 'date', 'after_or_equal:ordered_at'],
            'payment_status' => ['required', 'string', Rule::in(Order::availablePaymentStatuses())],
            'payment_method' => ['nullable', 'string', 'max:100'],
            'advance_payment_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'vat_enabled' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_category' => ['required', 'string', Rule::in(['custom', 'fabric', 'readymade'])],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.custom.garment_type_id' => ['nullable', 'integer', 'exists:garment_types,id'],
            'items.*.custom.fabric_source' => ['nullable', 'string', Rule::in(['own', 'stock'])],
            'items.*.custom.fabric_product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.custom.fabric_quantity' => ['nullable', 'numeric', 'min:0.01'],
            'items.*.custom.design_note' => ['nullable', 'string', 'max:1000'],
            'items.*.custom.design_images' => ['nullable', 'array'],
            'items.*.custom.design_images.*' => ['nullable', 'image', 'max:5120'],
            'items.*.custom.measurements' => ['nullable', 'array'],
            'items.*.custom.measurements.*.type' => ['nullable', 'string', 'max:100'],
            'items.*.custom.measurements.*.measurement' => ['nullable', 'string', 'max:100'],
            'items.*.custom.measurements.*.unit' => ['nullable', 'string', 'max:20'],
            'items.*.custom.tailoring_package_id' => ['nullable', 'integer', 'exists:garment_type_tailoring_packages,id'],
            'items.*.custom.tailoring_package' => ['nullable', 'string', 'max:100'],
            'items.*.custom.tailoring_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $items = collect($this->input('items', []))->values();
            $status = (string) $this->input('status', '');

            $allowedProductCategoryIds = ProductCategory::query()
                ->whereIn('slug', ['ready-made', 'accessories', 'fabrics'])
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values();

            $fabricCategoryId = (int) (ProductCategory::query()
                ->where('slug', 'fabrics')
                ->value('id') ?? 0);

            $productIds = $items
                ->flatMap(function ($item) {
                    return [
                        (int) ($item['product_id'] ?? 0),
                        (int) data_get($item, 'custom.fabric_product_id', 0),
                    ];
                })
                ->filter(fn ($id) => $id > 0)
                ->unique()
                ->values();

            $products = \App\Models\Product::query()
                ->whereIn('id', $productIds)
                ->get(['id', 'product_category_id'])
                ->keyBy('id');

            foreach ($items as $index => $item) {
                $itemCategory = (string) ($item['item_category'] ?? '');
                $productId = (int) ($item['product_id'] ?? 0);

                if ($itemCategory === 'custom') {
                    $garmentTypeId = (int) data_get($item, 'custom.garment_type_id', 0);
                    $fabricSource = (string) data_get($item, 'custom.fabric_source', '');
                    $measurements = collect((array) data_get($item, 'custom.measurements', []))->values();
                    $tailoringPackageId = (int) data_get($item, 'custom.tailoring_package_id', 0);
                    $tailoringPackage = trim((string) data_get($item, 'custom.tailoring_package', ''));
                    $tailoringAmount = (float) data_get($item, 'custom.tailoring_amount', 0);

                    if ($garmentTypeId < 1) {
                        $validator->errors()->add("items.{$index}.custom.garment_type_id", 'Garment type is required for custom item.');
                    }

                    if (!in_array($fabricSource, ['own', 'stock'], true)) {
                        $validator->errors()->add("items.{$index}.custom.fabric_source", 'Fabric source is required for custom item.');
                    }

                    if ($measurements->isEmpty()) {
                        $validator->errors()->add("items.{$index}.custom.measurements", 'At least one measurement row is required for custom item.');
                    }

                    if ($tailoringPackage === '') {
                        $validator->errors()->add("items.{$index}.custom.tailoring_package", 'Tailoring package is required for custom item.');
                    }

                    if ($tailoringAmount <= 0) {
                        $validator->errors()->add("items.{$index}.custom.tailoring_amount", 'Tailoring amount must be greater than zero for custom item.');
                    }

                    if ($tailoringPackageId > 0) {
                        $validPackage = GarmentTypeTailoringPackage::query()
                            ->whereKey($tailoringPackageId)
                            ->where('garment_type_id', $garmentTypeId)
                            ->exists();

                        if (!$validPackage) {
                            $validator->errors()->add(
                                "items.{$index}.custom.tailoring_package_id",
                                'Selected tailoring package does not belong to selected garment type.'
                            );
                        }
                    }

                    foreach ($measurements as $measurementIndex => $measurement) {
                        $type = trim((string) ($measurement['type'] ?? ''));
                        $value = trim((string) ($measurement['measurement'] ?? ''));
                        $unit = trim((string) ($measurement['unit'] ?? ''));

                        if ($type === '' || $value === '' || $unit === '') {
                            $validator->errors()->add(
                                "items.{$index}.custom.measurements.{$measurementIndex}.measurement",
                                'Measurement type, value and unit are required.'
                            );
                        }
                    }

                    if ($fabricSource === 'stock') {
                        $fabricProductId = (int) data_get($item, 'custom.fabric_product_id', 0);
                        $fabricQty = (float) data_get($item, 'custom.fabric_quantity', 0);
                        $fabricProduct = $products->get($fabricProductId);

                        if ($fabricProductId < 1 || !$fabricProduct) {
                            $validator->errors()->add("items.{$index}.custom.fabric_product_id", 'Select a fabric product from stock.');
                        } elseif ($fabricCategoryId > 0 && (int) $fabricProduct->product_category_id !== $fabricCategoryId) {
                            $validator->errors()->add("items.{$index}.custom.fabric_product_id", 'Selected stock product must be in Fabrics category.');
                        }

                        if ($fabricQty <= 0) {
                            $validator->errors()->add("items.{$index}.custom.fabric_quantity", 'Fabric quantity must be greater than zero when using stock.');
                        }
                    }

                    continue;
                }

                if ($productId < 1) {
                    $validator->errors()->add("items.{$index}.product_id", 'Product is required.');
                    continue;
                }

                $product = $products->get($productId);
                if (!$product || !$allowedProductCategoryIds->contains((int) $product->product_category_id)) {
                    $validator->errors()->add("items.{$index}.product_id", 'Selected product is not allowed for order items.');
                    continue;
                }

                if ($itemCategory === 'fabric' && $fabricCategoryId > 0 && (int) $product->product_category_id !== $fabricCategoryId) {
                    $validator->errors()->add("items.{$index}.product_id", 'Fabric category item requires a fabric product.');
                }

                if ($itemCategory === 'readymade' && $fabricCategoryId > 0 && (int) $product->product_category_id === $fabricCategoryId) {
                    $validator->errors()->add("items.{$index}.product_id", 'Readymade category item cannot use a fabric product.');
                }

            }

            $advanceAmount = (float) ($this->input('advance_payment_amount') ?? 0);
            $discountAmount = (float) ($this->input('discount_amount') ?? 0);
            $vatEnabled = $this->boolean('vat_enabled');
            $grandTotal = $items->sum(function ($item) {
                $qty = (float) ($item['quantity'] ?? 0);
                $unitPrice = (float) ($item['unit_price'] ?? 0);

                return $qty * $unitPrice;
            });
            $netTotalWithoutVat = max(0.0, $grandTotal - $discountAmount);
            $vatAmount = $vatEnabled ? ($netTotalWithoutVat * 0.13) : 0.0;
            $netTotal = $netTotalWithoutVat + $vatAmount;

            if ($discountAmount > $grandTotal) {
                $validator->errors()->add('discount_amount', 'Discount cannot be greater than order total.');
            }

            if ($advanceAmount > $netTotal) {
                $validator->errors()->add('advance_payment_amount', 'Advance payment amount cannot be greater than payable amount after discount.');
            }

            if (in_array($status, [
                Order::STATUS_ASSIGNED,
                Order::STATUS_IN_PROGRESS,
                Order::STATUS_NEAR_COMPLETION,
                Order::STATUS_COMPLETED,
            ], true)) {
                if ((int) $this->input('worker_id', 0) < 1) {
                    $validator->errors()->add('worker_id', 'Worker is required for assigned/in-progress/completed order status.');
                }

                if (empty($this->input('worker_deadline_at'))) {
                    $validator->errors()->add('worker_deadline_at', 'Worker deadline is required for assigned/in-progress/completed order status.');
                }
            }

            if (in_array($status, [Order::STATUS_DELIVERED, Order::STATUS_CANCELLED], true)) {
                $validator->errors()->add('status', 'Use the status update workflow to mark orders delivered or cancelled.');
            }
        });
    }
}
