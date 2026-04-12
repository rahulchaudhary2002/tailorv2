<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Product Barcodes PDF</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Poppins", Arial, sans-serif;
            color: #111827;
            background: #f3f4f6;
        }

        .toolbar {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 16px 24px;
            background: rgba(17, 24, 39, 0.96);
            color: #f9fafb;
        }

        .toolbar__title {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 600;
        }

        .toolbar__meta {
            margin: 4px 0 0;
            font-size: 0.88rem;
            color: #d1d5db;
        }

        .toolbar__actions {
            display: flex;
            gap: 10px;
        }

        .toolbar__button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 14px;
            border: 0;
            border-radius: 8px;
            background: #f9fafb;
            color: #111827;
            font: inherit;
            text-decoration: none;
            cursor: pointer;
        }

        .sheet {
            max-width: 1120px;
            margin: 0 auto;
            padding: 24px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .label {
            padding: 14px;
            border: 1px solid #d1d5db;
            border-radius: 12px;
            background: #ffffff;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .label__name {
            margin: 0 0 6px;
            font-size: 0.98rem;
            font-weight: 600;
        }

        .label__meta {
            margin: 0 0 10px;
            font-size: 0.82rem;
            color: #4b5563;
        }

        .label__barcode {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 112px;
            padding: 8px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            background: #fff;
        }

        .label__barcode svg {
            display: block;
            width: 100%;
            height: auto;
        }

        .label__fallback {
            font-family: monospace;
            font-size: 0.96rem;
            letter-spacing: 0.08em;
        }

        .empty {
            padding: 48px 24px;
            text-align: center;
            color: #6b7280;
        }

        @page {
            size: A4 portrait;
            margin: 12mm;
        }

        @media (max-width: 900px) {
            .grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .toolbar {
                flex-direction: column;
                align-items: flex-start;
            }

            .grid {
                grid-template-columns: 1fr;
            }
        }

        @media print {
            body {
                background: #ffffff;
            }

            .toolbar {
                display: none;
            }

            .sheet {
                max-width: none;
                padding: 0;
            }

            .grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 12px;
            }

            .label {
                border-radius: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <div>
            <h1 class="toolbar__title">Product Barcodes</h1>
            <p class="toolbar__meta">
                {{ $products->count() }} items
                @if ($q !== '')
                    | Search: "{{ $q }}"
                @endif
                @if ($categoryId > 0)
                    | Category filter applied
                @endif
            </p>
        </div>
        <div class="toolbar__actions">
            <button type="button" class="toolbar__button" onclick="window.print()">Save as PDF</button>
            <a href="{{ route('product.index', request()->only(['q', 'category_id'])) }}" class="toolbar__button">Back to Products</a>
        </div>
    </div>

    <main class="sheet">
        @if ($products->isEmpty())
            <div class="empty">No products found for the selected filters.</div>
        @else
            <div class="grid">
                @foreach ($products as $product)
                    <section class="label">
                        <h2 class="label__name">{{ $product->name }}</h2>
                        <p class="label__meta">
                            {{ $product->category?->name ?? 'Uncategorized' }}
                            @if ($product->code)
                                | Code: {{ $product->code }}
                            @endif
                        </p>
                        <div class="label__barcode">
                            @if ($product->barcode_svg !== '')
                                {!! $product->barcode_svg !!}
                            @else
                                <span class="label__fallback">{{ $product->barcode ?: $product->code ?: '-' }}</span>
                            @endif
                        </div>
                    </section>
                @endforeach
            </div>
        @endif
    </main>

    <script>
        window.addEventListener('load', () => {
            if (window.matchMedia('print').matches) {
                return;
            }

            window.print();
        });
    </script>
</body>
</html>
