<?php

namespace markhuot\craftmcp\controllers;

use Craft;
use craft\helpers\StringHelper;
use craft\web\Controller;
use yii\web\Response;

class McpController extends Controller
{
    protected array|int|bool $allowAnonymous = self::ALLOW_ANONYMOUS_LIVE;
    public $enableCsrfValidation = false;

    public function actionMessage(): Response
    {
        $this->response->format = Response::FORMAT_JSON;

        if (! $this->request->getIsJson()) {
            $this->response->setStatusCode(400);
            return $this->asJson([
                'jsonrpc' => '2.0',
                'error' => [
                    'code' => -32600,
                    'message' => 'Invalid Request: Content-Type must be application/json',
                ],
            ]);
        }

//        $clientId = $this->request->getQueryParam('clientId');
//        if (! $clientId || ! is_string($clientId)) {
//            $this->response->setStatusCode(400);
//            return $this->asJson([
//                'jsonrpc' => '2.0',
//                'error' => [
//                    'code' => -32600,
//                    'message' => 'Invalid Request: Missing or invalid clientId query parameter',
//                ],
//            ]);
//        }

        $content = $this->request->getBodyParams();
        if (empty($content['method'])) {
            $this->response->setStatusCode(400);
            return $this->asJson([
                'jsonrpc' => '2.0',
                'error' => [
                    'code' => -32600,
                    'message' => 'Invalid Request: Missing method',
                ],
            ]);
        }

        // Reformat our method in to a fully qualified class name so an initialize
        // method becomes messages\InitializeMessage and a tools/list method becomes
        // messages\tools\ListMessage.
        $method = $content['method'];
        $path = StringHelper::split($method, '/');
        $action = array_pop($path);
        $fqcn = '\\markhuot\\craftmcp\\messages\\' . implode('\\', [
            ...$path,
            StringHelper::upperCaseFirst($action)
        ]) . 'Message';

        // Process the message
        $action = (new $fqcn);
        $reflect = new \ReflectionClass($action);
        $method = $reflect->getMethod('__invoke');
        $parameters = $method?->getParameters() ?? [];

        $params = array_map(function ($param) use ($content) {
            return $content[$param->getName()];
        }, $parameters);

        $response = $action(...$params);

        return $this->asJson($response);
    }

//    public function actionListen()
//    {
//        header('Content-Type: text/event-stream');
//        header('Cache-Control: no-cache, must-revalidate');
//        header('Connection: keep-alive');
//        header('Access-Control-Allow-Origin: *');
//        header('X-Accel-Buffering: no');
//
//        $id = 0;
//        while ($id++ < 10) {
//            echo 'event: message' . "\n";
//            echo 'data: ' . json_encode([
//                    'jsonrpc' => '2.0',
//                    'id' => $id,
//                    'method' => 'ping',
//                ]) . "\n\n";
//
//            while (ob_get_level() > 0) {
//                ob_end_flush();
//            }
//            flush();
//
//            if (connection_aborted()) {
//                break;
//            }
//
//            sleep(1);
//        }
//    }

    public function actionListen()
    {
        $this->response->stream = function () {
            yield 'event: ready' . "\n\n";

            $id = 0;
            while ($id++ >= 0) {
                yield 'event: message' . "\n";
                yield 'data: ' . json_encode([
                        'jsonrpc' => '2.0',
                        'id' => $id,
                        'method' => 'ping',
                    ]) . "\n\n";

                while (ob_get_level() > 0) {
                    ob_end_flush();
                }
                flush();

                if (connection_aborted()) {
                    break;
                }

                sleep(1);
            }

        };

        Craft::$app->response->format = \yii\web\Response::FORMAT_RAW;
        Craft::$app->getResponse()->headers->set('Content-Type', 'text/event-stream');
        Craft::$app->getResponse()->headers->set('Cache-Control', 'no-cache, must-revalidate');
        Craft::$app->getResponse()->headers->set('Connection', 'keep-alive');
        Craft::$app->getResponse()->headers->set('Access-Control-Allow-Origin', '*');
        Craft::$app->getResponse()->headers->set('X-Accel-Buffering', 'no');
        Craft::$app->getResponse()->send();
        Craft::$app->end();
    }
}
