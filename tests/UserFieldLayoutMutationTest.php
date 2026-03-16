<?php

use craft\elements\User;
use craft\fieldlayoutelements\Heading;
use happycog\craftmcp\tools\AddTabToFieldLayout;
use happycog\craftmcp\tools\AddUiElementToFieldLayout;
use happycog\craftmcp\tools\GetUserFieldLayout;
use happycog\craftmcp\tools\MoveElementInFieldLayout;
use happycog\craftmcp\tools\RemoveElementFromFieldLayout;

beforeEach(function () {
    $this->getLayout = Craft::$container->get(GetUserFieldLayout::class);
    $this->addTab = Craft::$container->get(AddTabToFieldLayout::class);
    $this->addUiElement = Craft::$container->get(AddUiElementToFieldLayout::class);
    $this->moveElement = Craft::$container->get(MoveElementInFieldLayout::class);
    $this->removeElement = Craft::$container->get(RemoveElementFromFieldLayout::class);
});

it('retrieves user layout id that can be reused by field layout tools', function () {
    $response = $this->getLayout->__invoke();

    expect($response['fieldLayout']['id'])->toBe(GetUserFieldLayout::PLACEHOLDER_ID);
    expect($response['fieldLayout']['type'])->toBe(User::class);
});

it('can add a tab to the user field layout', function () {
    $layout = $this->getLayout->__invoke();
    $fieldLayoutId = $layout['fieldLayout']['id'];

    $result = $this->addTab->__invoke(
        fieldLayoutId: $fieldLayoutId,
        name: 'User Test Tab',
        position: ['type' => 'append'],
    );

    expect(collect($result['fieldLayout']['tabs'])->pluck('name'))->toContain('User Test Tab');
});

it('can add a generic ui element to the user field layout', function () {
    $layout = $this->getLayout->__invoke();
    $fieldLayoutId = $layout['fieldLayout']['id'];
    $tabName = collect($layout['fieldLayout']['tabs'])->pluck('name')->first() ?? 'Content';

    $result = $this->addUiElement->__invoke(
        fieldLayoutId: $fieldLayoutId,
        elementType: Heading::class,
        tabName: $tabName,
        position: ['type' => 'append'],
        config: ['heading' => 'User Layout Heading'],
    );

    $addedUid = $result['addedElement']['uid'];
    expect($addedUid)->toBeString();

    $updatedLayout = $this->getLayout->__invoke();
    $elements = collect($updatedLayout['fieldLayout']['tabs'])->flatMap(fn(array $tab) => $tab['elements']);
    expect($elements->pluck('uid'))->toContain($addedUid);
});

it('can move a user layout element to another tab after adding it', function () {
    $layout = $this->getLayout->__invoke();
    $fieldLayoutId = $layout['fieldLayout']['id'];
    $sourceTabName = collect($layout['fieldLayout']['tabs'])->pluck('name')->first() ?? 'Content';
    $targetTabName = 'Moved User Elements';

    $this->addTab->__invoke(
        fieldLayoutId: $fieldLayoutId,
        name: $targetTabName,
        position: ['type' => 'append'],
    );

    $added = $this->addUiElement->__invoke(
        fieldLayoutId: $fieldLayoutId,
        elementType: Heading::class,
        tabName: $sourceTabName,
        position: ['type' => 'append'],
        config: ['heading' => 'Move Me'],
    );

    $result = $this->moveElement->__invoke(
        fieldLayoutId: $fieldLayoutId,
        elementUid: $added['addedElement']['uid'],
        tabName: $targetTabName,
        position: ['type' => 'append'],
    );

    $targetTab = collect($result['fieldLayout']['tabs'])->firstWhere('name', $targetTabName);

    expect($targetTab)->not->toBeNull()
        ->and(collect($targetTab['elements'])->pluck('uid'))->toContain($added['addedElement']['uid']);
});

it('can remove a user layout element after adding it', function () {
    $layout = $this->getLayout->__invoke();
    $fieldLayoutId = $layout['fieldLayout']['id'];
    $tabName = collect($layout['fieldLayout']['tabs'])->pluck('name')->first() ?? 'Content';

    $added = $this->addUiElement->__invoke(
        fieldLayoutId: $fieldLayoutId,
        elementType: Heading::class,
        tabName: $tabName,
        position: ['type' => 'append'],
        config: ['heading' => 'Remove Me'],
    );

    $result = $this->removeElement->__invoke(
        fieldLayoutId: $fieldLayoutId,
        elementUid: $added['addedElement']['uid'],
    );

    $elements = collect($result['fieldLayout']['tabs'])->flatMap(fn(array $tab) => $tab['elements']);
    expect($elements->pluck('uid'))->not->toContain($added['addedElement']['uid']);
});
