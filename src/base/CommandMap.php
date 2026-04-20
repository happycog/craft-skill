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
use happycog\craftmcp\tools\GetAvailablePermissions;
use happycog\craftmcp\tools\GetUser;
use happycog\craftmcp\tools\GetUserFieldLayout;
use happycog\craftmcp\tools\GetUserGroup;
use happycog\craftmcp\tools\GetUserGroups;
use happycog\craftmcp\tools\GetUsers;
use happycog\craftmcp\tools\GetVolumes;
use happycog\craftmcp\tools\MoveElementInFieldLayout;
use happycog\craftmcp\tools\RemoveElementFromFieldLayout;
use happycog\craftmcp\tools\SearchContent;
use happycog\craftmcp\tools\UpdateAsset;
use happycog\craftmcp\tools\UpdateAddress;
use happycog\craftmcp\tools\UpdateDraft;
use happycog\craftmcp\tools\UpdateEntry;
use happycog\craftmcp\tools\UpdateEntryType;
use happycog\craftmcp\tools\UpdateField;
use happycog\craftmcp\tools\UpdateOrder;
use happycog\craftmcp\tools\UpdateProduct;
use happycog\craftmcp\tools\UpdateProductType;
use happycog\craftmcp\tools\UpdateSection;
use happycog\craftmcp\tools\UpdateUser;
use happycog\craftmcp\tools\UpdateUserGroup;
use happycog\craftmcp\tools\UpdateVariant;
use happycog\craftmcp\tools\CreateProduct;
use happycog\craftmcp\tools\CreateProductType;
use happycog\craftmcp\tools\CreateVariant;
use happycog\craftmcp\tools\DeleteProduct;
use happycog\craftmcp\tools\DeleteProductType;
use happycog\craftmcp\tools\DeleteVariant;
use happycog\craftmcp\tools\GetOrder;
use happycog\craftmcp\tools\GetOrderStatuses;
use happycog\craftmcp\tools\GetProduct;
use happycog\craftmcp\tools\GetProducts;
use happycog\craftmcp\tools\GetProductType;
use happycog\craftmcp\tools\GetProductTypes;
use happycog\craftmcp\tools\GetVariant;
use happycog\craftmcp\tools\GetStore;
use happycog\craftmcp\tools\GetStores;
use happycog\craftmcp\tools\SearchOrders;
use happycog\craftmcp\tools\UpdateStore;

/**
 * Centralized command-to-tool mapping.
 * 
 * This class defines all available CLI commands and their corresponding tool classes.
 * All tools are invokable (use __invoke method).
 */
class CommandMap
{
    /**
     * @var array<string, class-string>
     */
    public const MAP = [
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

        // Volumes
        'volumes/list' => GetVolumes::class,

        // Health
        'health' => GetHealth::class,

        // Commerce: Products
        'products/create' => CreateProduct::class,
        'products/get' => GetProduct::class,
        'products/search' => GetProducts::class,
        'products/update' => UpdateProduct::class,
        'products/delete' => DeleteProduct::class,
        'product-types/list' => GetProductTypes::class,
        'product-types/get' => GetProductType::class,
        'product-types/create' => CreateProductType::class,
        'product-types/update' => UpdateProductType::class,
        'product-types/delete' => DeleteProductType::class,

        // Commerce: Variants
        'variants/create' => CreateVariant::class,
        'variants/get' => GetVariant::class,
        'variants/update' => UpdateVariant::class,
        'variants/delete' => DeleteVariant::class,

        // Commerce: Orders
        'orders/get' => GetOrder::class,
        'orders/search' => SearchOrders::class,
        'orders/update' => UpdateOrder::class,
        'order-statuses/list' => GetOrderStatuses::class,

        // Commerce: Stores
        'stores/list' => GetStores::class,
        'stores/get' => GetStore::class,
        'stores/update' => UpdateStore::class,
    ];

    /**
     * Get the tool class for a command.
     *
     * @param string $command
     * @return class-string|null
     */
    public static function getToolClass(string $command): ?string
    {
        return self::MAP[$command] ?? null;
    }

    /**
     * Check if a command exists.
     *
     * @param string $command
     * @return bool
     */
    public static function hasCommand(string $command): bool
    {
        return isset(self::MAP[$command]);
    }

    /**
     * Get all commands.
     *
     * @return array<string, class-string>
     */
    public static function all(): array
    {
        return self::MAP;
    }
}
