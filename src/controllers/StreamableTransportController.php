<?php

namespace happycog\craftmcp\controllers;

use craft\web\Controller;
use happycog\craftmcp\transports\StreamableHttpServerTransport;
use yii\web\Response;

class StreamableTransportController extends Controller
{
    protected array|int|bool $allowAnonymous = self::ALLOW_ANONYMOUS_LIVE;
    public $enableCsrfValidation = false;

    public function actionMessage(StreamableHttpServerTransport $transport): Response
    {
        return $transport->handlePost($this->request, $this->response);
    }

    public function actionListen(StreamableHttpServerTransport $transport): Response
    {
        return $transport->handleGet($this->request, $this->response);
    }

    public function actionDisconnect(StreamableHttpServerTransport $transport): Response
    {
        return $transport->handleDelete($this->request, $this->response);
    }
}
