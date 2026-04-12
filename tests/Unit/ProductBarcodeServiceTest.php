<?php

use App\Services\ProductBarcodeService;

it('generates a stable ean13 barcode for a product id', function () {
    $service = app(ProductBarcodeService::class);

    $barcode = $service->generateForProductId(123);

    expect($barcode)
        ->toBe('2000000001234')
        ->and($service->isValid($barcode))->toBeTrue();
});

it('renders barcode svg output for a valid barcode', function () {
    $service = app(ProductBarcodeService::class);

    $svg = $service->renderSvg('2000000001234');

    expect($svg)
        ->toContain('<svg')
        ->toContain('Barcode 2000000001234')
        ->toContain('<rect');
});
