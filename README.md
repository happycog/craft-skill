# Craft Skill Plugin

A Craft CMS plugin that provides a RESTful HTTP API with structured access to Craft CMS content management capabilities.

## Overview

This plugin exposes Craft CMS functionality through HTTP endpoints, enabling programmatic access to content creation, modification, search, and management operations. It provides a clean, type-safe API designed specifically for interaction with Large Language Models (LLMs).

### Claude Skills Integration

This project is structured as a **Claude Skill** - a specialized capability that can be connected to Claude (Anthropic's AI assistant) to enable it to interact with your Craft CMS installation.

By adding this plugin as a skill to Claude, you transform your Craft CMS site into an AI-accessible content management system, enabling natural language content operations and automated CMS administration tasks.

## Features

- **Entry Management**: Create, read, update, and delete entries with full field support
- **Content Search**: Search across entries with flexible query capabilities
- **Section Management**: Create and configure sections with different types (single, channel, structure)
- **Entry Type Management**: Define and manage entry types with custom field layouts
- **Field Management**: Create and configure custom fields with support for multiple field types
- **Field Layout Management**: Build and modify field layouts programmatically
- **Draft Support**: Create, update, and apply drafts for content workflows
- **Site Information**: Access multi-site configuration details

## Installation

### 1. Install via Composer

```bash
composer require happycog/craft-skill
```

### 2. Install the Plugin

From your Craft project directory:

```bash
php craft plugin/install skills
```

Or install via the Craft Control Panel:
- Navigate to Settings → Plugins
- Find "Craft Skill" in the list
- Click "Install"

### 3. Configure Base URL (optional)

Set your primary site URL in your `.env` file:

```env
PRIMARY_SITE_URL=https://your-craft-site.com
```

### 4. Configure API Prefix (optional)

Create `config/skills.php` to customize the API endpoint prefix (defaults to `api`):

```php
<?php

return [
    'apiPrefix' => 'api', // Change to your preferred prefix
];
```

### 5. Link Skill Documentation for Claude

To enable Claude to access the skill documentation, symlink the `SKILLS/` directory to Claude's skills directory:

```bash
# Create the Claude skills directory if it doesn't exist
mkdir -p ~/.claude/skills

# Symlink this project's SKILLS directory
ln -s vendor/happycog/craft-skills/SKILLS ~/.claude/skills/craft
```

Replace `/path/to/craft-skill` with the actual path to your plugin installation. Once linked, Claude will be able to discover and use the Craft CMS management capabilities.
