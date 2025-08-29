<?php

namespace happycog\craftmcp\controllers;

use craft\web\Controller;
use happycog\craftmcp\transports\HttpServerTransport;
use yii\web\Response;

class SseTransportController extends Controller
{
    protected array|int|bool $allowAnonymous = self::ALLOW_ANONYMOUS_LIVE;
    public $enableCsrfValidation = false;

    public function actionMessage(HttpServerTransport $transport): Response
    {
        return $transport->handleMessageRequest($this->request, $this->response);
    }

    public function actionSse(HttpServerTransport $transport): Response
    {
        return $transport->handleSseRequest($this->request, $this->response);
    }
}