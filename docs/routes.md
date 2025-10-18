# HTTP API Routes Quick Reference

All routes are prefixed with `/api` (configurable via plugin settings).

## Sections
| Method | Route | Action | Tool Class |
|--------|-------|--------|------------|
| POST | `/api/sections` | Create section | `CreateSection` |
| GET | `/api/sections` | List sections | `GetSections` |
| PUT | `/api/sections/<id>` | Update section | `UpdateSection` |
| DELETE | `/api/sections/<id>` | Delete section | `DeleteSection` |

## Entry Types
| Method | Route | Action | Tool Class |
|--------|-------|--------|------------|
| POST | `/api/entry-types` | Create entry type | `CreateEntryType` |
| GET | `/api/entry-types` | List entry types | `GetEntryTypes` |
| PUT | `/api/entry-types/<id>` | Update entry type | `UpdateEntryType` |
| DELETE | `/api/entry-types/<id>` | Delete entry type | `DeleteEntryType` |

## Fields
| Method | Route | Action | Tool Class |
|--------|-------|--------|------------|
| POST | `/api/fields` | Create field | `CreateField` |
| GET | `/api/fields` | List fields | `GetFields` |
| GET | `/api/fields/types` | Get field types | `GetFieldTypes` |
| PUT | `/api/fields/<id>` | Update field | `UpdateField` |
| DELETE | `/api/fields/<id>` | Delete field | `DeleteField` |

## Entries
| Method | Route | Action | Tool Class |
|--------|-------|--------|------------|
| POST | `/api/entries` | Create entry | `CreateEntry` |
| GET | `/api/entries/search` | Search entries | `SearchContent` |
| GET | `/api/entries/<id>` | Get entry | `GetEntry` |
| PUT | `/api/entries/<id>` | Update entry | `UpdateEntry` |
| DELETE | `/api/entries/<id>` | Delete entry | `DeleteEntry` |

## Drafts
| Method | Route | Action | Tool Class |
|--------|-------|--------|------------|
| POST | `/api/drafts` | Create draft | `CreateDraft` |
| PUT | `/api/drafts/<id>` | Update draft | `UpdateDraft` |
| POST | `/api/drafts/<id>/apply` | Apply draft | `ApplyDraft` |

## Field Layouts
| Method | Route | Action | Tool Class |
|--------|-------|--------|------------|
| POST | `/api/field-layouts` | Create field layout | `CreateFieldLayout` |
| GET | `/api/field-layouts` | Get field layout | `GetFieldLayout` |
| PUT | `/api/field-layouts/<id>` | Update field layout | `UpdateFieldLayout` |

## Sites
| Method | Route | Action | Tool Class |
|--------|-------|--------|------------|
| GET | `/api/sites` | List sites | `GetSites` |

## MCP Protocol Routes
| Method | Route | Action | Description |
|--------|-------|--------|-------------|
| GET | `/mcp` | Listen | SSE streaming for MCP protocol |
| POST | `/mcp` | Message | JSON-RPC message processing |
| DELETE | `/mcp` | Disconnect | Session cleanup |

## Legacy SSE Routes
| Method | Route | Action | Description |
|--------|-------|--------|-------------|
| GET | `/sse` | SSE | Legacy SSE endpoint |
| POST | `/message` | Message | Legacy message endpoint |

## Route Pattern Summary

### Controller Mapping
- `/api/sections/*` → `SectionsController`
- `/api/entry-types/*` → `EntryTypesController`
- `/api/fields/*` → `FieldsController`
- `/api/entries/*` → `EntriesController`
- `/api/drafts/*` → `DraftsController`
- `/api/field-layouts/*` → `FieldLayoutsController`
- `/api/sites/*` → `SitesController`

### URL Parameter Mapping
Path parameters like `<id>` are automatically mapped to tool-specific parameter names:
- Sections: `sectionId`
- Entry Types: `entryTypeId`
- Fields: `fieldId`
- Entries: `entryId`
- Drafts: `draftId`
- Field Layouts: `fieldLayoutId`

### Request Body Processing
- POST/PUT requests: JSON body parameters mapped to tool method parameters
- GET/DELETE requests: Query string parameters mapped to tool method parameters
- Validation via Valinor with automatic type casting and error handling
