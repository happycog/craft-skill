# create_site

Create new sites for multi-site/multi-language installations.

## Route

`POST /api/sites`

## Description

Creates a new site in Craft CMS. Sites allow you to manage multi-site/multi-language installations with site-specific content and URLs. Each site requires a unique name, handle, base URL, and language code.

After creating the site, always link the user back to the site settings in the Craft control panel for further configuration.

## Parameters

### Required Parameters

- **name** (string): The display name for the site
- **baseUrl** (string): The base URL for the site. Can be a full URL (e.g., `"https://example.com"`) or Craft alias (e.g., `"@web"` or `"@web/de"`)
- **language** (string): The language code for the site (e.g., `"en-US"`, `"de-DE"`, `"fr-FR"`, `"es-ES"`)

### Optional Parameters

- **handle** (string, optional): Machine-readable name. Auto-generated from name if not provided.
- **primary** (boolean, optional): Whether this site should be the primary site. Only one site can be primary. Default: `false`
- **enabled** (boolean, optional): Whether this site should be enabled. Default: `true`

## Return Value

Returns an object containing:

- **siteId** (integer): The newly created site's ID
- **name** (string): Site display name
- **handle** (string): Site handle
- **language** (string): Language code
- **baseUrl** (string): Base URL
- **primary** (boolean): Whether this is the primary site
- **enabled** (boolean): Whether the site is enabled
- **editUrl** (string): Craft control panel URL for site settings

## Example Usage

### Basic Site Creation
```json
{
  "name": "English Site",
  "baseUrl": "https://example.com",
  "language": "en-US"
}
```

### German Site with Custom Handle
```json
{
  "name": "Deutsch",
  "baseUrl": "https://example.com/de",
  "language": "de-DE",
  "handle": "german"
}
```

### Primary Site with @web Alias
```json
{
  "name": "Main Site",
  "baseUrl": "@web",
  "language": "en-US",
  "primary": true,
  "enabled": true
}
```

### Disabled Site
```json
{
  "name": "Future Spanish Site",
  "baseUrl": "https://example.com/es",
  "language": "es-ES",
  "enabled": false
}
```

## Example Response

```json
{
  "siteId": 2,
  "name": "Deutsch",
  "handle": "german",
  "language": "de-DE",
  "baseUrl": "https://example.com/de",
  "primary": false,
  "enabled": true,
  "editUrl": "https://example.com/admin/settings/sites/2"
}
```

## Notes

- Only one site can be primary at a time
- Setting a site as primary will automatically demote the previous primary site
- Site handles must be unique across the installation
- Use `@web` or other Craft aliases in baseUrl for environment-agnostic configuration
- Language codes should follow the format `language-COUNTRY` (e.g., `en-US`, `de-DE`)
- After creation, configure additional site settings in the Craft control panel
- Use `get_sites` to list all sites and verify configuration
