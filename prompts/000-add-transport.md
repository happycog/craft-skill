# add integrated http transport

this package currentlt implements the MCP spec manually within the controller methods

it doesnt need to do that though, because the php-mcp/server package provides an abstraction that will do that for us. it is called the "transport".

we need to make a transport that integrates with the native Craft/Yii routes and calls php-mcp/server transport

there is an example in samples/php-mcp-laravel/src/Transports/StreamableHttpServerTransport.php that we'll want to model our implementaion after. note: the sample is written for Laravel so while the overall structure and flow will work the same the method bodies will need ro interact with the Yii SDK instead of Laravel

start by adding this new transport and the appropriate tests to ensure the new transport is working. you can run the tests by calling ./vendor/bin/pest. note: the tests will emit a warning about a cache file, this does not need to be corrected. the cache warning while testing is a benign warning caused by an incompatibility between craft and pestphp.

## Additional Context Discovered:

### Current Architecture:
- The existing McpController manually implements JSON-RPC 2.0 protocol handling
- Message routing is done via dynamic class resolution (tools/list → ListMessage)
- The plugin already uses php-mcp/server package for tool registry and server configuration
- Routes are registered via #[RegisterListener] attributes on UrlManager events
- Controller uses Craft's web\Controller base class with CSRF disabled and anonymous access

### Key Integration Points:
1. **Transport Implementation**: Create StreamableHttpServerTransport implementing ServerTransportInterface
2. **Controller Adaptation**: Replace current manual JSON-RPC handling with transport delegation
3. **Route Enhancement**: Add GET route for SSE streaming alongside existing POST route
4. **Dependency Injection**: Register transport using #[BindToContainer(singleton: true)] attribute
5. **Laravel-to-Craft Adaptations**:
   - Replace Laravel Request/Response with Yii equivalents
   - Replace config() helper with Craft settings
   - Replace response() helper with $this->asJson() patterns
   - Use Craft::$container instead of app() helper
   - Adapt event emission to Craft's event system

### Expected Benefits:
- Eliminate manual JSON-RPC protocol implementation
- Add SSE streaming support for real-time communication
- Better separation of concerns between transport and business logic
- Leverage php-mcp/server's built-in protocol handling
