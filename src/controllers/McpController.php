<?php

declare(strict_types=1);

namespace happycog\craftmcp\controllers;

use craft\web\Controller as CraftController;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\ServerRequest;
use happycog\craftmcp\mcp\McpServerFactory;
use Mcp\Server\Transport\StreamableHttpTransport;
use Psr\Http\Message\ResponseInterface;
use yii\web\Response;

class McpController extends CraftController
{
    protected array|int|bool $allowAnonymous = self::ALLOW_ANONYMOUS_LIVE;
    public $enableCsrfValidation = false;

    public function actionIndex(): Response
    {
        $factory = \Craft::$container->get(McpServerFactory::class);
        $server = $factory->create();

        $psr7Request = ServerRequest::fromGlobals();
        $psr17 = new HttpFactory();

        $transport = new StreamableHttpTransport(
            request: $psr7Request,
            responseFactory: $psr17,
            streamFactory: $psr17,
        );

        $psr7Response = $server->run($transport);

        return $this->emit($psr7Response);
    }

    private function emit(ResponseInterface $psr7Response): Response
    {
        $response = $this->response;
        $response->setStatusCode($psr7Response->getStatusCode(), $psr7Response->getReasonPhrase());

        foreach ($psr7Response->getHeaders() as $name => $values) {
            $response->headers->set($name, implode(', ', $values));
        }

        $response->format = Response::FORMAT_RAW;
        $response->content = (string) $psr7Response->getBody();

        return $response;
    }
}
