---
name: Craft CMS Skills
description: Complete skill suite for managing Craft CMS content including users, addresses, sections, entry types, fields, entries, drafts, field layouts, sites, and Commerce products, variants, and orders.
---

## Important: Use this plugin, Not YAML Files

**CRITICAL**: Always use these skill tools (MCP or CLI) to manage Craft CMS content. Never directly modify YAML configuration files in the `config/project/` directory. The API ensures proper validation, maintains data integrity, and handles all necessary relationships automatically. Direct YAML edits can corrupt your Craft installation.

**CRITICAL**: The `skills` plugin must be installed to Craft. You can verify installation by running `php craft plugin/list` and install it with `php craft plugin/install skills`

## How to call these tools

The plugin exposes each skill as an MCP tool and as a CLI command, backed by
the same invokable class. Pick the surface that fits the caller:

- **MCP (HTTP)**: POST JSON-RPC to `{PRIMARY_SITE_URL}/{mcpPath}` (the
  `mcpPath` value in `config/ai.php` defaults to `mcp`). See `docs/mcp.md` for a full
  description of the transport, including the `Mcp-Session-Id` header.
- **MCP (stdio)**: `php craft skills/mcp/serve` (useful for Claude Desktop
  and similar clients that spawn the server as a subprocess).
- **CLI**: `agent-craft <command>` — see the README for positional/flag
  conventions. The same tools are addressable through `CommandMap`.

All tools return JSON. Errors from the MCP transport are returned as
JSON-RPC errors; CLI errors are written to STDERR with an exit code.

## Content
- [create_entry](create_entry.md) - Create entries with section/entry type IDs and field data
- [get_entry](get_entry.md) - Retrieve entry by ID with all fields and metadata
- [update_entry](update_entry.md) - Update entry (prefers draft workflow)
- [delete_entry](delete_entry.md) - Delete entry (soft/permanent)
- [search_content](search_content.md) - Search/filter entries by section/status/query

## Drafts
- [create_draft](create_draft.md) - Create draft from scratch or existing entry
- [update_draft](update_draft.md) - Update draft content/metadata (PATCH semantics)
- [delete_draft](delete_draft.md) - Delete draft without changing canonical entry
- [apply_draft](apply_draft.md) - Publish draft to canonical entry

## Sections
- [create_section](create_section.md) - Create section with types/versioning/sites
- [get_sections](get_sections.md) - List all or filter by IDs
- [update_section](update_section.md) - Update properties/settings
- [delete_section](delete_section.md) - Permanently delete (removes all entries)

## Entry Types
- [create_entry_type](create_entry_type.md) - Create with handle/name/layout
- [get_entry_types](get_entry_types.md) - List with fields/usage/URLs
- [update_entry_type](update_entry_type.md) - Update properties/layout
- [delete_entry_type](delete_entry_type.md) - Delete if not in use

## Fields
- [create_field](create_field.md) - Create with type and settings
- [get_fields](get_fields.md) - List global or layout-specific
- [get_field_types](get_field_types.md) - List available types
- [update_field](update_field.md) - Update properties/settings
- [delete_field](delete_field.md) - Permanently delete (removes data)

## Field Layouts
- [create_field_layout](create_field_layout.md) - Create empty field layout for entry types
- [get_field_layout](get_field_layout.md) - Get field layout structure by entry type/layout/element ID
- [add_tab_to_field_layout](add_tab_to_field_layout.md) - Add tab to field layout with flexible positioning (prepend/append/before/after)
- [add_field_to_field_layout](add_field_to_field_layout.md) - Add custom field to tab with positioning, width, required, and display options
- [add_ui_element_to_field_layout](add_ui_element_to_field_layout.md) - Add UI elements (heading, tip, horizontal rule, markdown, template) to layouts
- [move_element_in_field_layout](move_element_in_field_layout.md) - Move fields/UI elements within or between tabs with precise positioning
- [remove_element_from_field_layout](remove_element_from_field_layout.md) - Remove fields or UI elements from field layout

## Sites
- [get_sites](get_sites.md) - List all sites with IDs/handles/URLs

## Templates
- [list_templates](list_templates.md) - List all site template filenames relative to the templates directory
- [get_template](get_template.md) - Retrieve the contents of a specific site template by filename
- [search_templates](search_templates.md) - Search site template contents for a plain-text string

## Assets
- [create_asset](create_asset.md) - Upload file from local/remote URL to volume
- [update_asset](update_asset.md) - Update metadata or replace file
- [delete_asset](delete_asset.md) - Delete asset and file
- [get_volumes](get_volumes.md) - List asset volumes with IDs/URLs

## Addresses
- [get_addresses](get_addresses.md) - List/search addresses by owner, field, and location
- [get_address](get_address.md) - Retrieve address details with owner and field context
- [create_address](create_address.md) - Create generic owner-backed addresses for users or custom address fields
- [update_address](update_address.md) - Update address attributes and custom fields
- [delete_address](delete_address.md) - Delete address (soft/permanent)
- [get_address_field_layout](get_address_field_layout.md) - Retrieve the single global address field layout

## Users
- [get_users](get_users.md) - List/search users by query, identity fields, status, and optionally group
- [get_user](get_user.md) - Retrieve a user by ID, email, or username
- [create_user](create_user.md) - Create a user with native attributes and custom fields
- [get_available_permissions](get_available_permissions.md) - List all known permissions plus custom stored permission names
- [update_user](update_user.md) - Update a user by ID, email, or username
- [delete_user](delete_user.md) - Delete a user by ID, email, or username
- [get_user_field_layout](get_user_field_layout.md) - Retrieve the single global user field layout

## User Groups
- [get_user_groups](get_user_groups.md) - List user groups and their permissions
- [get_user_group](get_user_group.md) - Retrieve a user group by ID or handle
- [create_user_group](create_user_group.md) - Create a user group and set permissions
- [update_user_group](update_user_group.md) - Update a user group and its permissions
- [delete_user_group](delete_user_group.md) - Delete a user group by ID or handle

## System
- [get_health](get_health.md) - Health check endpoint to verify plugin installation and API availability

## Commerce: Products
- [create_product](create_product.md) - Create product with type, title, SKU, price, and custom fields
- [get_product](get_product.md) - Retrieve product with variants, pricing, and custom fields
- [get_products](get_products.md) - Search/filter products by type/status/query
- [update_product](update_product.md) - Update product attributes and custom fields
- [delete_product](delete_product.md) - Delete product (soft/permanent)
- [get_product_types](get_product_types.md) - List available Commerce product types
- [get_product_type](get_product_type.md) - Retrieve product type with field layouts and site settings
- [create_product_type](create_product_type.md) - Create product type with title, variant, layout, and site settings
- [update_product_type](update_product_type.md) - Update product type configuration and site settings
- [delete_product_type](delete_product_type.md) - Delete product type with impact analysis and force protection

## Commerce: Variants
- [create_variant](create_variant.md) - Add variant to existing product with SKU, price, and attributes
- [get_variant](get_variant.md) - Retrieve variant with pricing, inventory, and dimensions
- [update_variant](update_variant.md) - Update variant pricing, SKU, stock, and fields
- [delete_variant](delete_variant.md) - Delete variant (soft/permanent)

## Commerce: Orders
- [get_order](get_order.md) - Retrieve order with line items, totals, and addresses
- [search_orders](search_orders.md) - Search/filter orders by email/status/date/payment
- [update_order](update_order.md) - Update order status or message
- [get_order_statuses](get_order_statuses.md) - List all order statuses with IDs/handles/colors

## Commerce: Stores
- [get_stores](get_stores.md) - List all stores with checkout/payment/tax configuration
- [get_store](get_store.md) - Retrieve store with full configuration details
- [update_store](update_store.md) - Update store checkout, payment, and pricing settings
