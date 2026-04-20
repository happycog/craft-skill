<?php

declare(strict_types=1);

namespace happycog\craftmcp\console;

use Craft;
use happycog\craftmcp\mcp\McpServerFactory;
use Mcp\Server\Transport\StdioTransport;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Runs the Craft skills MCP server over stdio.
 *
 * Intended for desktop clients (e.g. Claude Desktop) that spawn the
 * server as a subprocess and communicate via JSON-RPC over STDIN/STDOUT.
 *
 * @extends Controller<\yii\base\Module>
 */
class McpController extends Controller
{
    public $defaultAction = 'serve';

    public function actionServe(): int
    {
        $factory = Craft::$container->get(McpServerFactory::class);
        $server = $factory->create();

        $server->run(new StdioTransport());

        return ExitCode::OK;
    }
}
