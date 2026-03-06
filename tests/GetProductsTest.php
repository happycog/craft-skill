<?php

use happycog\craftmcp\tools\GetProducts;

beforeEach(function () {
    if (!class_exists(\craft\commerce\elements\Product::class)) {
        $this->markTestSkipped('Craft Commerce is not installed.');
    }

    $this->tool = Craft::$container->get(GetProducts::class);
});

it('returns products with expected structure', function () {
    $response = $this->tool->__invoke();

    expect($response)->toBeArray();
    expect($response)->toHaveKeys(['_notes', 'results']);
    expect($response['_notes'])->toBe('The following products were found.');
    expect($response['results'])->toBeInstanceOf(\Illuminate\Support\Collection::class);
});

it('respects limit parameter', function () {
    $limit = 2;
    $response = $this->tool->__invoke(limit: $limit);

    expect($response['results']->count())->toBeLessThanOrEqual($limit);
});

it('generates correct notes for search query', function () {
    $response = $this->tool->__invoke(query: 'widget');

    expect($response['_notes'])->toContain('search query "widget"');
});

it('generates correct notes for type filter', function () {
    $commerce = \craft\commerce\Plugin::getInstance();
    $productTypes = $commerce->getProductTypes()->getAllProductTypes();

    if (empty($productTypes)) {
        $this->markTestSkipped('No product types configured in Commerce.');
    }

    $typeId = $productTypes[0]->id;
    $typeName = $productTypes[0]->name;

    $response = $this->tool->__invoke(typeIds: [$typeId]);

    expect($response['_notes'])->toContain('product type(s):');
    expect($response['_notes'])->toContain($typeName);
});

it('generates correct notes with no filters', function () {
    $response = $this->tool->__invoke();

    expect($response['_notes'])->toBe('The following products were found.');
});

it('throws exception for invalid product type ID', function () {
    expect(fn () => $this->tool->__invoke(typeIds: [99999]))
        ->toThrow(\RuntimeException::class, 'Product type with ID 99999 not found');
});

it('returns correct keys for each product in results', function () {
    // Create a product to ensure there's at least one result
    $commerce = \craft\commerce\Plugin::getInstance();
    $productTypes = $commerce->getProductTypes()->getAllProductTypes();

    if (empty($productTypes)) {
        $this->markTestSkipped('No product types configured in Commerce.');
    }

    $product = new \craft\commerce\elements\Product();
    $product->typeId = $productTypes[0]->id;
    $product->title = 'Products List Test';
    $product->enabled = true;

    $variant = new \craft\commerce\elements\Variant();
    $variant->sku = 'TEST-LIST-001';
    $variant->price = 9.99;
    $variant->isDefault = true;
    $product->setVariants([$variant]);

    Craft::$app->getElements()->saveElement($product);

    $response = $this->tool->__invoke();

    if ($response['results']->isNotEmpty()) {
        $first = $response['results']->first();
        expect($first)->toHaveKeys([
            'productId',
            'title',
            'slug',
            'status',
            'typeId',
            'defaultSku',
            'defaultPrice',
            'url',
        ]);
        expect($first['productId'])->toBeInt();
        expect($first['title'])->toBeString();
    }
});

it('generates correct notes for combined query and type filter', function () {
    $commerce = \craft\commerce\Plugin::getInstance();
    $productTypes = $commerce->getProductTypes()->getAllProductTypes();

    if (empty($productTypes)) {
        $this->markTestSkipped('No product types configured in Commerce.');
    }

    $typeId = $productTypes[0]->id;

    $response = $this->tool->__invoke(query: 'test', typeIds: [$typeId]);

    expect($response['_notes'])->toContain('search query "test"');
    expect($response['_notes'])->toContain('product type(s):');
});
