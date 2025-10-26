# update_site

Update existing site properties and settings.

## Route

`PUT /api/sites/<id>`

## Description

Updates an existing site in Craft CMS. Allows modification of site properties including name, handle, base URL, language, primary status, and enabled status.

Only one site can be primary at a time. If you set a site as primary, the previous primary site will automatically be demoted.

After updating the site, always link the user back to the site settings in the Craft control panel to review the changes.

## Parameters

### Required Parameters

- **siteId** (integer): The ID of the site to update (passed in URL as `<id>`)

### Optional Parameters

All parameters are optional - only provide the properties you want to update:

- **name** (string, optional): The display name for the site
- **handle** (string, optional): Machine-readable name
- **baseUrl** (string, optional): The base URL for the site (full URL or Craft alias)
- **language** (string, optional): The language code (e.g., `"en-US"`, `"de-DE"`, `"fr-FR"`)
- **primary** (boolean, optional): Whether this site should be the primary site
- **enabled** (boolean, optional): Whether this site should be enabled

## Return Value

Returns an object containing:

- **siteId** (integer): The site's ID
- **name** (string): Site display name
- **handle** (string): Site handle
- **language** (string): Language code
- **baseUrl** (string): Base URL
- **primary** (boolean): Whether this is the primary site
- **enabled** (boolean): Whether the site is enabled
- **editUrl** (string): Craft control panel URL for site settings

## Example Usage

### Update Site Name
```json
{
  "name": "New Site Name"
}
```

### Change Site Language and URL
```json
{
  "language": "de-DE",
  "baseUrl": "https://example.de"
}
```

### Make Site Primary
```json
{
  "primary": true
}
```

### Disable Site
```json
{
  "enabled": false
}
```

### Update Multiple Properties
```json
{
  "name": "Updated Deutsch Site",
  "handle": "deutsch",
  "baseUrl": "https://example.com/de",
  "language": "de-DE",
  "enabled": true
}
```

## Example Response

```json
{
  "siteId": 2,
  "name": "Updated Deutsch Site",
  "handle": "deutsch",
  "language": "de-DE",
  "baseUrl": "https://example.com/de",
  "primary": false,
  "enabled": true,
  "editUrl": "https://example.com/admin/settings/sites/2"
}
```

## Notes

- Only properties you specify will be updated - all others remain unchanged
- Only one site can be primary - setting primary=true will demote the current primary site
- Site handles must be unique across the installation
- Changing site handle may affect templates and configurations that reference it
- Use `@web` or other Craft aliases in baseUrl for environment-agnostic configuration
- Language codes should follow the format `language-COUNTRY` (e.g., `en-US`, `de-DE`)
- After updating, verify changes in the Craft control panel
- Use `get_sites` to verify the updated configuration

## See Also

- `create_site` - Create new sites
- `get_sites` - List all sites
