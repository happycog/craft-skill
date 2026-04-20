# Model Context Protocol (MCP) Server

This plugin exposes every Craft skill tool (all of `src/tools/*.php`) over the
[Model Context Protocol](https://modelcontextprotocol.io) using the official
[`mcp/sdk`](https://packagist.org/packages/mcp/sdk) package. The SDK is
responsible for the JSON-RPC framing, schema generation and transport
plumbing; this plugin provides a thin "tool bridge" that maps each invokable
tool class to an MCP tool registration.

## Architecture

```
┌──────────────────┐   registers       ┌───────────────────────────┐
│ CommandMap::MAP  │ ────────────────▶ │ Mcp\Server\Builder        │
│  (class list)    │                   │  ::addTool([class, fn])   │
└──────────────────┘                   └───────────────────────────┘
        │                                         │
        │ shared with CLI                         │ builds
        ▼                                         ▼
┌──────────────────┐                    ┌───────────────────────┐
│ src/tools/*.php  │ ◀── invokes ────── │ Mcp\Server            │
│ (__invoke tools) │                    │ + CraftContainer (DI) │
└──────────────────┘                    └───────────────────────┘
                                                  │
                     ┌────────────────────────────┼────────────────────────────┐
                     ▼                                                         ▼
       ┌──────────────────────────┐                           ┌───────────────────────────┐
       │ StreamableHttpTransport  │                           │ StdioTransport            │
       │ → controllers/McpCtrl    │                           │ → console/McpController   │
       └──────────────────────────┘                           └───────────────────────────┘
```

* `McpServerFactory::create()` iterates `base\CommandMap::MAP` and registers
  each tool via `Server::builder()->addTool([$class, '__invoke'])`. The SDK
  derives the tool name from the class's short name (e.g.
  `CreateSection` → `CreateSection`) and reflects on `__invoke` + its
  PHPDoc to generate the MCP `inputSchema` automatically.
* `CommandMap` is the single source of truth for which tool classes are
  exposed. The CLI addresses them under command strings like
  `sections/create`; the MCP surface addresses them under their class
  short name. One class, two idiomatic surfaces.
* `CraftContainer` is a PSR-11 adapter around `Craft::$container`; the SDK
  uses it to instantiate tool classes so Craft DI bindings keep working.

## Transports

### HTTP (Streamable)

The plugin registers a single site route at the path configured by the
`mcpPath` plugin setting (default: `mcp`). Point your MCP-capable client at:

```
https://example.test/mcp
```

The controller uses `Mcp\Server\Transport\StreamableHttpTransport`, which
handles `POST`, `DELETE` and `OPTIONS` per the MCP HTTP spec.

### Stdio

For clients that spawn the server as a subprocess (Claude Desktop, etc.)
run the console command from the host Craft installation:

```
php craft skills/mcp/serve
```

That boots Craft and hands STDIN/STDOUT to `StdioTransport`.

## Tool naming

MCP tool names are the PascalCase short name of each tool class — the
default the SDK uses when registering an invokable handler with no
explicit name. CLI command strings are defined independently in
`base\CommandMap`; the two surfaces do not share an identifier.

| CLI command (`CommandMap`)   | MCP tool name                      |
| ---------------------------- | ---------------------------------- |
| `sections/create`            | `CreateSection`                    |
| `field-layouts/add-field`    | `AddFieldToFieldLayout`            |
| `orders/search`              | `SearchOrders`                     |
| `health`                     | `GetHealth`                        |

A client can enumerate the full list by calling the `tools/list` method.
