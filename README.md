# Craft Skill

A standalone CLI tool that provides programmatic access to Craft CMS content management capabilities. Designed specifically for AI agents and automation workflows.

## Overview

Craft Skill is distributed as a self-contained PHAR executable (PHP Archive) that bootstraps your Craft CMS installation and exposes all CMS functionality through a clean command-line interface. 

**One file. No dependencies. Just point it at your Craft installation.**

### Claude Skills Integration

This tool is structured as a **Claude Skill** - a specialized capability that can be connected to Claude (Anthropic's AI assistant) to enable it to interact with your Craft CMS installation directly via CLI commands.

By adding this skill to Claude, you transform your Craft CMS site into an AI-accessible content management system, enabling natural language content operations and automated CMS administration tasks.

## Features

- **Entry Management**: Create, read, update, and delete entries with full field support
- **Content Search**: Search across entries with flexible query capabilities
- **Section Management**: Create and configure sections with different types (single, channel, structure)
- **Entry Type Management**: Define and manage entry types with custom field layouts
- **Field Management**: Create and configure custom fields with support for multiple field types
- **Field Layout Management**: Build and modify field layouts programmatically
- **Draft Support**: Create, update, and apply drafts for content workflows
- **Asset Management**: Upload, update, and manage assets and volumes
- **Site Information**: Access multi-site configuration details

## Installation

### Quick Start

Download the PHAR and start using it immediately:

```bash
# Download the latest release
curl -LSs https://github.com/happycog/craft-skill/releases/latest/download/agent-craft.phar -o agent-craft
chmod +x agent-craft

# Use it with any Craft installation
./agent-craft --path=/path/to/craft sections/list
```

### Global Installation (Recommended)

Make the tool available from anywhere on your system:

```bash
# Move to system path
sudo mv agent-craft /usr/local/bin/agent-craft

# Now use from any directory
agent-craft --path=/path/to/craft sections/list
```

### Verify Installation

Test that the tool can bootstrap your Craft installation:

```bash
agent-craft --path=/path/to/craft sections/list
```

You should see a JSON response with your Craft sections.

### Build from Source (Optional)

If you want to build the PHAR yourself:

```bash
# Clone the repository
git clone https://github.com/happycog/craft-skill.git
cd craft-skill

# Build the PHAR
php -d phar.readonly=0 bin/build-phar.php

# Use the built PHAR
./agent-craft.phar --path=/path/to/craft sections/list
```

## Usage

### Basic Command Structure

```bash
agent-craft <tool/action> [positional-args] [--flags]
```

### Simple Examples

```bash
# List all sections
agent-craft sections/list

# Get a specific entry by ID
agent-craft entries/get 123

# Create an entry with simple fields
agent-craft entries/create \
  --sectionId=1 \
  --entryTypeId=2 \
  --title="My New Entry" \
  --slug="my-new-entry"

# Search entries
agent-craft entries/search --query="blog post"

# Delete an entry
agent-craft entries/delete 123
```

### Complex Examples (with structured data)

For operations requiring structured data, use query string-style bracket notation:

```bash
# Create entry with field data using bracket notation
agent-craft entries/create \
  --sectionId=1 \
  --entryTypeId=2 \
  --title="Product Page" \
  --fields[bodyContent]="<p>Description here</p>" \
  --fields[relatedEntries]=45,67,89

# Arrays using comma-separated values
agent-craft entries/update 123 \
  --title="Updated Title" \
  --fields[categories]=5,6,7 \
  --fields[customField]="value"

# Arrays using repeated flags with auto-indexing
agent-craft entries/create \
  --title="Gallery" \
  --fields[images][]=123 \
  --fields[images][]=456 \
  --fields[images][]=789

# Nested objects with bracket notation
agent-craft sections/create \
  --name="Blog" \
  --handle="blog" \
  --type="channel" \
  --siteSettings[0][siteId]=1 \
  --siteSettings[0][uriFormat]="blog/{slug}" \
  --siteSettings[0][template]="blog/_entry"

# For very complex nested structures, JSON strings are still supported
agent-craft entries/create \
  --title="Complex Entry" \
  --fields[matrix]='[{"type":"imageBlock","image":[123],"caption":"Photo"},{"type":"textBlock","text":"Content"}]'

# Mix simple flags with nested bracket notation
agent-craft entries/create \
  --sectionId=1 \
  --title="Article" \
  --slug="my-article" \
  --fields[author]="John Doe" \
  --fields[tags]=news,featured,homepage \
  --fields[excerpt]="Short description"
```

### Working with Different Craft Installations

The tool can work with any Craft installation using the `--path` flag:

```bash
# Point to a specific Craft installation
agent-craft --path=/var/www/craft sections/list

# Work with multiple installations
agent-craft --path=/var/www/site1 sections/list
agent-craft --path=/var/www/site2 sections/list

# If run from within a Craft project, --path is optional
cd /var/www/craft
agent-craft sections/list  # Auto-detects current directory
```

### Verbose Output & Debugging

Use `-v`, `-vv`, or `-vvv` flags for progressively more verbose output:

```bash
# Show basic error messages
agent-craft entries/create --title="Test" -v

# Show detailed error information
agent-craft entries/create --title="Test" -vv

# Show full stack traces
agent-craft entries/create --title="Test" -vvv
```

### Output Format

All successful operations return JSON to stdout:

```json
{
  "id": 123,
  "title": "My Entry",
  "slug": "my-entry",
  "sectionId": 1,
  "url": "https://your-site.com/admin/entries/blog/123"
}
```

Errors are written to stderr with appropriate exit codes:
- `0` - Success
- `1` - General error
- `2` - Invalid arguments
- `3` - Craft not found or bootstrap failed

## Available Tools

All tools from the HTTP API are available via CLI. Common operations include:

### Entries
- `entries/create` - Create new entry
- `entries/get <id>` - Get entry by ID
- `entries/update <id>` - Update existing entry
- `entries/delete <id>` - Delete entry
- `entries/search` - Search entries

### Sections
- `sections/create` - Create new section
- `sections/list` - List all sections
- `sections/update <id>` - Update section
- `sections/delete <id>` - Delete section

### Entry Types
- `entry-types/create` - Create new entry type
- `entry-types/list` - List all entry types
- `entry-types/update <id>` - Update entry type
- `entry-types/delete <id>` - Delete entry type

### Fields
- `fields/create` - Create new field
- `fields/list` - List all fields
- `fields/types` - List available field types
- `fields/update <id>` - Update field
- `fields/delete <id>` - Delete field

### Drafts
- `drafts/create` - Create draft for entry
- `drafts/update <id>` - Update draft
- `drafts/apply <id>` - Apply draft to entry

### Assets
- `assets/create` - Upload new asset
- `assets/update <id>` - Update asset
- `assets/delete <id>` - Delete asset
- `volumes/list` - List asset volumes

### Other
- `sites/list` - List all sites
- `field-layouts/create` - Create field layout
- `field-layouts/get` - Get field layout
- `field-layouts/update <id>` - Update field layout

For detailed documentation on each tool, see the [SKILLS documentation](./SKILLS/).

## Development

### Running Tests

```bash
composer install
./vendor/bin/pest
```

### Static Analysis

```bash
./vendor/bin/phpstan analyse
```

### Building the PHAR

```bash
# Clone the repository
git clone https://github.com/happycog/craft-skill.git
cd craft-skill

# Build the PHAR
php -d phar.readonly=0 bin/build-phar.php

# The built PHAR will be at agent-craft.phar (under 1 MB)
```

## How It Works

The `agent-craft` PHAR is a self-contained CLI tool that:

1. Contains all plugin source code (under 1 MB)
2. Locates your Craft installation (via `--path` or current directory)
3. Bootstraps Craft and Yii2 frameworks from that installation
4. Parses CLI arguments and maps them to tool methods
5. Executes the requested operation using Craft's APIs
6. Returns JSON results to stdout

**No HTTP server. No Composer installation. No dependencies to manage.**

The PHAR bundles only the plugin code and bootstraps against your existing Craft installation, keeping it lightweight and compatible with any Craft version.

## Troubleshooting

### "Craft not found" Error

Ensure you're pointing to a valid Craft installation with `--path`:

```bash
agent-craft --path=/var/www/craft sections/list
```

The tool looks for `vendor/craftcms/cms` in the specified path.

### Permission Issues

Ensure the PHAR is executable:

```bash
chmod +x agent-craft
```

### Complex Field Data

When using bracket notation for structured data:

**Query String Style (Recommended)**
```bash
# Good - bracket notation is clean and readable
agent-craft entries/create --fields[bodyContent]="<p>Test</p>"

# Good - comma-separated for arrays
agent-craft entries/create --fields[items]=1,2,3

# Good - auto-indexed arrays
agent-craft entries/create --fields[items][]=1 --fields[items][]=2
```

**JSON Fallback (For Complex Nesting)**
```bash
# Use JSON strings for deeply nested structures
agent-craft entries/create --fields[matrix]='[{"type":"block","data":{...}}]'
```

**Type Handling**
- Numbers are automatically detected: `--sectionId=1` becomes integer `1`
- Booleans: `--enabled=true` or `--enabled=false`
- Strings: anything else remains a string
- Arrays: comma-separated or repeated `[]` flags
- Let Valinor handle final type casting based on tool method signatures

## Building from Source

### Prerequisites

- PHP 8.1 or higher
- Composer
- `phar.readonly` disabled (either in php.ini or via CLI flag)

### Build Process

The build script (`bin/build-phar.php`) creates a self-contained PHAR executable with:

1. **Plugin Source Code**: All files from `src/` directory
2. **CLI Entrypoint**: The `bin/agent-craft` script
3. **Minimal Autoloader**: PSR-4 autoloader for the plugin's namespace

The PHAR does NOT include Craft CMS or vendor dependencies. Instead, it:
- Loads the plugin's code from the PHAR archive
- Bootstraps Craft CMS from the target installation (specified via --path or current directory)
- Uses Craft's autoloader for all dependencies (Valinor, Craft core, etc.)

This architecture keeps the PHAR small (under 1 MB) while ensuring it works with any Craft installation.

### Build Command

```bash
# From the plugin repository
cd /path/to/craft-skill

# Build the PHAR
php -d phar.readonly=0 bin/build-phar.php

# Output: agent-craft.phar in project root
```

### Build Output

The build script provides progress information:

```
Building PHAR: /path/to/agent-craft.phar
  Added: autoload.php
  Added: 58 files from src/
  Added: 437 files from vendor/cuyz/valinor/
  Added: bin/agent-craft.php

PHAR built successfully!
  Output: /path/to/agent-craft.phar
  Size: 915 KB
  Files: 497 total
```

### Distribution

After building, you can distribute `agent-craft.phar` as a single file. Users only need:

- PHP 8.1+ installed
- A Craft CMS project to point the tool at

No Composer dependencies, no installation steps - just run the PHAR with `--path` pointing to a Craft installation.

## License

MIT License - see [LICENSE](./LICENSE) for details.

## Credits

Built by [Happy Cog](https://happycog.com) for the Craft CMS community.
