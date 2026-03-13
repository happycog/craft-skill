<?php

use craft\elements\Address;
use craft\fieldlayoutelements\addresses\LabelField;
use happycog\craftmcp\tools\AddTabToFieldLayout;
use happycog\craftmcp\tools\AddUiElementToFieldLayout;
use happycog\craftmcp\tools\GetAddressFieldLayout;
use happycog\craftmcp\tools\RemoveElementFromFieldLayout;

beforeEach(function () {
    $this->getLayout = Craft::$container->get(GetAddressFieldLayout::class);
    $this->addTab = Craft::$container->get(AddTabToFieldLayout::class);
    $this->addUiElement = Craft::$container->get(AddUiElementToFieldLayout::class);
    $this->removeElement = Craft::$container->get(RemoveElementFromFieldLayout::class);
});

it('retrieves address layout id that can be reused by field layout tools', function () {
    $response = $this->getLayout->__invoke();

    expect($response['fieldLayout']['id'])->toBe(GetAddressFieldLayout::PLACEHOLDER_ID);
    expect($response['fieldLayout']['type'])->toBe(Address::class);
});

it('can add a tab to the address field layout', function () {
    $layout = $this->getLayout->__invoke();
    $fieldLayoutId = $layout['fieldLayout']['id'];

    $result = $this->addTab->__invoke(
        fieldLayoutId: $fieldLayoutId,
        name: 'Address Test Tab',
        position: ['type' => 'append'],
    );

    expect(collect($result['fieldLayout']['tabs'])->pluck('name'))->toContain('Address Test Tab');
});

it('can add an address-native ui element to the address field layout', function () {
    $layout = $this->getLayout->__invoke();
    $fieldLayoutId = $layout['fieldLayout']['id'];

    $tabNames = collect($layout['fieldLayout']['tabs'])->pluck('name');
    $tabName = $tabNames->first() ?? 'Content';

    $result = $this->addUiElement->__invoke(
        fieldLayoutId: $fieldLayoutId,
        elementType: LabelField::class,
        tabName: $tabName,
        position: ['type' => 'append'],
    );

    $addedUid = $result['addedElement']['uid'];
    expect($addedUid)->toBeString();

    $updatedLayout = $this->getLayout->__invoke();
    $elements = collect($updatedLayout['fieldLayout']['tabs'])->flatMap(fn(array $tab) => $tab['elements']);
    expect($elements->pluck('uid'))->toContain($addedUid);
});

it('can remove an address layout element after adding it', function () {
    $layout = $this->getLayout->__invoke();
    $fieldLayoutId = $layout['fieldLayout']['id'];
    $tabName = collect($layout['fieldLayout']['tabs'])->pluck('name')->first() ?? 'Content';

    $added = $this->addUiElement->__invoke(
        fieldLayoutId: $fieldLayoutId,
        elementType: LabelField::class,
        tabName: $tabName,
        position: ['type' => 'append'],
    );

    $result = $this->removeElement->__invoke(
        fieldLayoutId: $fieldLayoutId,
        elementUid: $added['addedElement']['uid'],
    );

    $elements = collect($result['fieldLayout']['tabs'])->flatMap(fn(array $tab) => $tab['elements']);
    expect($elements->pluck('uid'))->not->toContain($added['addedElement']['uid']);
});
