<?php

declare(strict_types=1);

namespace happycog\craftmcp\base;

use happycog\craftmcp\tools\AddFieldToFieldLayout;
use happycog\craftmcp\tools\AddTabToFieldLayout;
use happycog\craftmcp\tools\AddUiElementToFieldLayout;
use happycog\craftmcp\tools\ApplyDraft;
use happycog\craftmcp\tools\CreateAddress;
use happycog\craftmcp\tools\CreateAsset;
use happycog\craftmcp\tools\CreateDraft;
use happycog\craftmcp\tools\CreateEntry;
use happycog\craftmcp\tools\CreateEntryType;
use happycog\craftmcp\tools\CreateField;
use happycog\craftmcp\tools\CreateFieldLayout;
use happycog\craftmcp\tools\CreateSection;
use happycog\craftmcp\tools\CreateUser;
use happycog\craftmcp\tools\CreateUserGroup;
use happycog\craftmcp\tools\DeleteAsset;
use happycog\craftmcp\tools\DeleteAddress;
use happycog\craftmcp\tools\DeleteDraft;
use happycog\craftmcp\tools\DeleteEntry;
use happycog\craftmcp\tools\DeleteEntryType;
use happycog\craftmcp\tools\DeleteField;
use happycog\craftmcp\tools\DeleteSection;
use happycog\craftmcp\tools\DeleteUser;
use happycog\craftmcp\tools\DeleteUserGroup;
use happycog\craftmcp\tools\GetEntry;
use happycog\craftmcp\tools\GetEntryTypes;
use happycog\craftmcp\tools\GetAddress;
use happycog\craftmcp\tools\GetAddressFieldLayout;
use happycog\craftmcp\tools\GetAddresses;
use happycog\craftmcp\tools\GetFieldLayout;
use happycog\craftmcp\tools\GetFields;
use happycog\craftmcp\tools\GetFieldTypes;
use happycog\craftmcp\tools\GetHealth;
use happycog\craftmcp\tools\GetSection;
use happycog\craftmcp\tools\GetSections;
use happycog\craftmcp\tools\GetSites;
use happycog\craftmcp\tools\GetTemplate;
use happycog\craftmcp\tools\GetAvailablePermissions;
use happycog\craftmcp\tools\GetUser;
use happycog\craftmcp\tools\GetUserFieldLayout;
use happycog\craftmcp\tools\GetUserGroup;
use happycog\craftmcp\tools\GetUserGroups;
use happycog\craftmcp\tools\GetUsers;
use happycog\craftmcp\tools\GetVolumes;
use happycog\craftmcp\tools\ListTemplates;
use happycog\craftmcp\tools\MoveElementInFieldLayout;
use happycog\craftmcp\tools\RemoveElementFromFieldLayout;
use happycog\craftmcp\tools\SearchContent;
use happycog\craftmcp\tools\SearchTemplates;
use happycog\craftmcp\tools\UpdateAsset;
use happycog\craftmcp\tools\UpdateAddress;
use happycog\craftmcp\tools\UpdateDraft;
use happycog\craftmcp\tools\UpdateEntry;
use happycog\craftmcp\tools\UpdateEntryType;
use happycog\craftmcp\tools\UpdateField;
use happycog\craftmcp\tools\UpdateSection;
use happycog\craftmcp\tools\UpdateUser;
use happycog\craftmcp\tools\UpdateUserGroup;

/**
 * Centralized command-to-tool mapping.
 *
 * This class defines all available CLI commands and their corresponding tool classes.
 * All tools are invokable (use __invoke method).
 *
 * Commerce tools are registered only when Craft Commerce is installed.
 */
class CommandMap
{
    /** @var array<string, class-string>|null */
    private static ?array $resolved = null;

    /**
     * Core tools that ship with Craft CMS itself.
     *
     * @var array<string, class-string>
     */
    public const CORE = [
        // Assets
        'assets/create' => CreateAsset::class,
        'assets/delete' => DeleteAsset::class,
        'assets/update' => UpdateAsset::class,

        // Addresses
        'addresses/list' => GetAddresses::class,
        'addresses/get' => GetAddress::class,
        'addresses/create' => CreateAddress::class,
        'addresses/update' => UpdateAddress::class,
        'addresses/delete' => DeleteAddress::class,
        'addresses/field-layout' => GetAddressFieldLayout::class,

        // Users
        'users/list' => GetUsers::class,
        'users/get' => GetUser::class,
        'users/create' => CreateUser::class,
        'users/permissions' => GetAvailablePermissions::class,
        'users/update' => UpdateUser::class,
        'users/delete' => DeleteUser::class,
        'users/field-layout' => GetUserFieldLayout::class,

        // User Groups
        'user-groups/list' => GetUserGroups::class,
        'user-groups/get' => GetUserGroup::class,
        'user-groups/create' => CreateUserGroup::class,
        'user-groups/update' => UpdateUserGroup::class,
        'user-groups/delete' => DeleteUserGroup::class,

        // Drafts
        'drafts/apply' => ApplyDraft::class,
        'drafts/create' => CreateDraft::class,
        'drafts/delete' => DeleteDraft::class,
        'drafts/update' => UpdateDraft::class,

        // Entries
        'entries/create' => CreateEntry::class,
        'entries/delete' => DeleteEntry::class,
        'entries/get' => GetEntry::class,
        'entries/search' => SearchContent::class,
        'entries/update' => UpdateEntry::class,

        // Entry Types
        'entry-types/create' => CreateEntryType::class,
        'entry-types/delete' => DeleteEntryType::class,
        'entry-types/list' => GetEntryTypes::class,
        'entry-types/update' => UpdateEntryType::class,

        // Fields
        'fields/create' => CreateField::class,
        'fields/delete' => DeleteField::class,
        'fields/list' => GetFields::class,
        'fields/types' => GetFieldTypes::class,
        'fields/update' => UpdateField::class,

        // Field Layouts
        'field-layouts/create' => CreateFieldLayout::class,
        'field-layouts/get' => GetFieldLayout::class,
        'field-layouts/add-field' => AddFieldToFieldLayout::class,
        'field-layouts/add-tab' => AddTabToFieldLayout::class,
        'field-layouts/add-ui-element' => AddUiElementToFieldLayout::class,
        'field-layouts/move-element' => MoveElementInFieldLayout::class,
        'field-layouts/remove-element' => RemoveElementFromFieldLayout::class,

        // Sections
        'sections/create' => CreateSection::class,
        'sections/delete' => DeleteSection::class,
        'sections/get' => GetSection::class,
        'sections/list' => GetSections::class,
        'sections/update' => UpdateSection::class,

        // Sites
        'sites/list' => GetSites::class,

        // Templates
        'templates/list' => ListTemplates::class,
        'templates/get' => GetTemplate::class,
        'templates/search' => SearchTemplates::class,

        // Volumes
        'volumes/list' => GetVolumes::class,

        // Health
        'health' => GetHealth::class,
    ];

    /**
     * Commerce tools — only loaded when `craft\commerce\Plugin` is available.
     *
     * Uses string literals instead of `::class` constants so PHP never
     * tries to autoload the Commerce tool classes at compile time.
     *
     * @var array<string, string>
     */
    private const COMMERCE = [
        // Products
        'products/create' => 'happycog\craftmcp\tools\CreateProduct',
        'products/get' => 'happycog\craftmcp\tools\GetProduct',
        'products/search' => 'happycog\craftmcp\tools\GetProducts',
        'products/update' => 'happycog\craftmcp\tools\UpdateProduct',
        'products/delete' => 'happycog\craftmcp\tools\DeleteProduct',
        'product-types/list' => 'happycog\craftmcp\tools\GetProductTypes',
        'product-types/get' => 'happycog\craftmcp\tools\GetProductType',
        'product-types/create' => 'happycog\craftmcp\tools\CreateProductType',
        'product-types/update' => 'happycog\craftmcp\tools\UpdateProductType',
        'product-types/delete' => 'happycog\craftmcp\tools\DeleteProductType',

        // Variants
        'variants/create' => 'happycog\craftmcp\tools\CreateVariant',
        'variants/get' => 'happycog\craftmcp\tools\GetVariant',
        'variants/update' => 'happycog\craftmcp\tools\UpdateVariant',
        'variants/delete' => 'happycog\craftmcp\tools\DeleteVariant',

        // Orders
        'orders/get' => 'happycog\craftmcp\tools\GetOrder',
        'orders/search' => 'happycog\craftmcp\tools\SearchOrders',
        'orders/update' => 'happycog\craftmcp\tools\UpdateOrder',
        'order-statuses/list' => 'happycog\craftmcp\tools\GetOrderStatuses',

        // Stores
        'stores/list' => 'happycog\craftmcp\tools\GetStores',
        'stores/get' => 'happycog\craftmcp\tools\GetStore',
        'stores/update' => 'happycog\craftmcp\tools\UpdateStore',
    ];

    /**
     * Return the full tool map, including optional Commerce tools when available.
     *
     * @return array<string, class-string>
     */
    public static function all(): array
    {
        if (self::$resolved !== null) {
            return self::$resolved;
        }

        /** @var array<string, class-string> $map */
        $map = self::CORE;

        if (class_exists(\craft\commerce\Plugin::class)) {
            $map = array_merge($map, self::COMMERCE);
        }

        self::$resolved = $map;

        return $map;
    }

    /**
     * Get the tool class for a command.
     *
     * @return class-string|null
     */
    public static function getToolClass(string $command): ?string
    {
        return self::all()[$command] ?? null;
    }

    /**
     * Check if a command exists.
     */
    public static function hasCommand(string $command): bool
    {
        return isset(self::all()[$command]);
    }
}
