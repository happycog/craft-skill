<?php

namespace happycog\craftmcp\actions;

use craft\base\ElementInterface;

class ResolveElementOwner
{
    /**
     * @return array{0: ElementInterface, 1: class-string<ElementInterface>}
     */
    public function __invoke(int $ownerId, string $ownerType): array
    {
        throw_unless(class_exists($ownerType), \InvalidArgumentException::class, "Owner type '{$ownerType}' does not exist");
        throw_unless(is_subclass_of($ownerType, ElementInterface::class), \InvalidArgumentException::class, "Owner type '{$ownerType}' is not a valid Craft element type");

        /** @var class-string<ElementInterface> $resolvedOwnerType */
        $resolvedOwnerType = $ownerType;

        $owner = \Craft::$app->getElements()->getElementById($ownerId, $resolvedOwnerType, null, [
            'siteId' => '*',
        ]);

        throw_unless($owner instanceof ElementInterface, \InvalidArgumentException::class, "Owner {$resolvedOwnerType} with ID {$ownerId} not found");

        return [$owner, $resolvedOwnerType];
    }
}
