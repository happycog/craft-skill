<?php

namespace happycog\craftmcp\tools;

use Craft;
use craft\base\FieldLayoutElement;
use craft\fieldlayoutelements\Heading;
use craft\fieldlayoutelements\HorizontalRule;
use craft\fieldlayoutelements\LineBreak;
use craft\fieldlayoutelements\Markdown;
use craft\fieldlayoutelements\Template;
use craft\fieldlayoutelements\Tip;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\services\Fields;
use happycog\craftmcp\exceptions\ModelSaveException;

class AddUiElementToFieldLayout
{
    public function __construct(
        protected Fields $fieldsService,
        protected GetFieldLayout $getFieldLayout,
    ) {
    }

    /**
     * Add a UI element (heading, tip, markdown, etc.) to a field layout at a specific position within a tab.
     *
     * The target tab must already exist - use add_tab_to_field_layout to create tabs first.
     * Position can be before/after a specific element UID, or prepend/append to the tab's elements.
     *
     * @param array<string, mixed> $position
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    public function add(
        /** The ID of the field layout to modify */
        int $fieldLayoutId,

        /**
         * Element type class name:
         * - craft\fieldlayoutelements\Heading
         * - craft\fieldlayoutelements\Tip
         * - craft\fieldlayoutelements\Markdown
         * - craft\fieldlayoutelements\Template
         * - craft\fieldlayoutelements\HorizontalRule
         * - craft\fieldlayoutelements\LineBreak
         */
        string $elementType,

        /** Name of tab to add element to (must exist) */
        string $tabName,

        /**
         * Positioning configuration:
         * - type: 'before', 'after', 'prepend', or 'append' (required)
         * - elementUid: UID of existing element for 'before' or 'after' positioning
         */
        array $position,

        /** Element width percentage (1-100) */
        ?int $width = null,

        /**
         * Element-specific configuration:
         * - Heading: ['heading' => 'Text'] (required)
         * - Tip: ['tip' => 'Text'] (required), ['style' => 'tip'|'warning'], ['dismissible' => true|false]
         * - Markdown: ['content' => 'Text'] (required), ['displayInPane' => true|false]
         * - Template: ['template' => 'path/to/template'] (required)
         * - HorizontalRule: No config needed
         * - LineBreak: No config needed
         */
        array $config = [],
    ): array {
        $fieldLayout = $this->fieldsService->getLayoutById($fieldLayoutId);
        throw_unless($fieldLayout instanceof FieldLayout, "Field layout with ID {$fieldLayoutId} not found");

        $validTypes = [
            Heading::class,
            Tip::class,
            Markdown::class,
            Template::class,
            HorizontalRule::class,
            LineBreak::class,
        ];
        throw_unless(in_array($elementType, $validTypes, true), "Invalid element type '{$elementType}'");

        $this->validateConfig($elementType, $config);

        $positionType = $position['type'] ?? null;
        throw_unless(
            in_array($positionType, ['before', 'after', 'prepend', 'append'], true),
            "Position type must be one of: 'before', 'after', 'prepend', 'append'"
        );

        if (in_array($positionType, ['before', 'after'], true)) {
            throw_unless(
                isset($position['elementUid']) && is_string($position['elementUid']),
                "elementUid is required for 'before' and 'after' positioning"
            );
        }

        $targetTab = null;
        foreach ($fieldLayout->getTabs() as $tab) {
            if ($tab->name === $tabName) {
                $targetTab = $tab;
                break;
            }
        }
        throw_unless($targetTab !== null, "Tab with name '{$tabName}' not found. Create the tab first using add_tab_to_field_layout");

        /** @var FieldLayoutElement $newElement */
        $newElement = new $elementType();
        $this->applyConfig($newElement, $elementType, $config);
        $width !== null && $newElement->width = $width;

        $existingElements = $targetTab->getElements();
        $newElements = [];
        $elementAdded = false;

        switch ($positionType) {
            case 'prepend':
                $newElements = array_merge([$newElement], $existingElements);
                $elementAdded = true;
                break;

            case 'append':
                $newElements = array_merge($existingElements, [$newElement]);
                $elementAdded = true;
                break;

            case 'before':
            case 'after':
                $targetUid = $position['elementUid'];
                foreach ($existingElements as $element) {
                    if ($element->uid === $targetUid && $positionType === 'before') {
                        $newElements[] = $newElement;
                        $elementAdded = true;
                    }
                    $newElements[] = $element;
                    if ($element->uid === $targetUid && $positionType === 'after') {
                        $newElements[] = $newElement;
                        $elementAdded = true;
                    }
                }
                throw_unless($elementAdded, "Element with UID '{$targetUid}' not found in tab '{$tabName}'");
                break;
        }

        $targetTab->setElements($newElements);

        $tabs = [];
        foreach ($fieldLayout->getTabs() as $tab) {
            $tabs[] = $tab->name === $tabName ? $targetTab : $tab;
        }
        $fieldLayout->setTabs($tabs);

        throw_unless($this->fieldsService->saveLayout($fieldLayout), ModelSaveException::class, $fieldLayout);

        return [
            '_notes' => ['UI element added successfully', 'Review the field layout in the control panel'],
            'fieldLayout' => $this->getFieldLayout->formatFieldLayout($fieldLayout),
            'addedElement' => [
                'uid' => $newElement->uid,
                'type' => $elementType,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $config
     */
    protected function validateConfig(string $elementType, array $config): void
    {
        switch ($elementType) {
            case Heading::class:
                throw_unless(isset($config['heading']) && is_string($config['heading']), "Heading requires 'heading' text in config");
                break;
            case Tip::class:
                throw_unless(isset($config['tip']) && is_string($config['tip']), "Tip requires 'tip' text in config");
                if (isset($config['style'])) {
                    throw_unless(in_array($config['style'], ['tip', 'warning'], true), "Tip style must be 'tip' or 'warning'");
                }
                break;
            case Markdown::class:
                throw_unless(isset($config['content']) && is_string($config['content']), "Markdown requires 'content' in config");
                break;
            case Template::class:
                throw_unless(isset($config['template']) && is_string($config['template']), "Template requires 'template' path in config");
                break;
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    protected function applyConfig(FieldLayoutElement $element, string $elementType, array $config): void
    {
        switch ($elementType) {
            case Heading::class:
                assert($element instanceof Heading);
                assert(is_string($config['heading']));
                $element->heading = $config['heading'];
                break;
            case Tip::class:
                assert($element instanceof Tip);
                assert(is_string($config['tip']));
                $element->tip = $config['tip'];
                if (isset($config['style'])) {
                    assert(is_string($config['style']));
                    assert(in_array($config['style'], ['tip', 'warning'], true));
                    /** @var 'tip'|'warning' $style */
                    $style = $config['style'];
                    $element->style = $style;
                }
                if (isset($config['dismissible'])) {
                    assert(is_bool($config['dismissible']));
                    $element->dismissible = $config['dismissible'];
                }
                break;
            case Markdown::class:
                assert($element instanceof Markdown);
                assert(is_string($config['content']));
                $element->content = $config['content'];
                if (isset($config['displayInPane'])) {
                    assert(is_bool($config['displayInPane']));
                    $element->displayInPane = $config['displayInPane'];
                }
                break;
            case Template::class:
                assert($element instanceof Template);
                assert(is_string($config['template']));
                $element->template = $config['template'];
                break;
        }
    }
}
