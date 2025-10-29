# Health Check

Get health status of the Craft Skill plugin installation.

## API Route

`GET /api/health`

## Parameters

None

## Returns

Returns a JSON object with health status information including:
- `status`: Always "ok" when the plugin is working
- `plugin`: Plugin information (name, version, installation status)
- `craft`: Craft CMS information (version, edition)
- `site`: Primary site information (name, base URL)

## Example Usage

### Request
```bash
curl -X GET http://craft-site.com/api/health
```

### Response
```json
{
  "status": "ok",
  "plugin": {
    "name": "Craft Skill",
    "version": "1.0.0",
    "installed": true
  },
  "craft": {
    "version": "5.0.0",
    "edition": "Pro"
  },
  "site": {
    "name": "My Craft Site",
    "baseUrl": "http://craft-site.com/"
  }
}
```

## Notes

- This endpoint is useful for monitoring and confirming the API is working
- Always returns HTTP 200 status code when the plugin is operational
- No authentication required - safe for automated health checks
- Can be used by load balancers and monitoring tools

## See Also

- [List Sites](list_sites.md) - Get detailed information about all configured sites
