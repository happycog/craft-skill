<?php

declare(strict_types=1);

namespace happycog\craftmcp\mcp;

use Craft;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Result\CallToolResult;

final class ToolErrorFormatter
{
    /**
     * @return array<string, mixed>
     */
    public function formatStructuredError(string $toolName, \Throwable $exception): array
    {
        $payload = [
            'error' => $exception->getMessage(),
            'tool' => $toolName,
            'exception' => $exception::class,
        ];

        if (Craft::$app->getConfig()->getGeneral()->devMode) {
            $payload['debug'] = [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ];
        }

        return $payload;
    }

    public function formatToolError(string $toolName, \Throwable $exception): CallToolResult
    {
        $structured = $this->formatStructuredError($toolName, $exception);
        $message = $this->formatTextMessage($toolName, $exception);

        return new CallToolResult(
            content: [new TextContent($message)],
            isError: true,
            structuredContent: $structured,
        );
    }

    private function formatTextMessage(string $toolName, \Throwable $exception): string
    {
        $message = "{$toolName} failed: {$exception->getMessage()}";
        $exceptionClass = $exception::class;

        if (!Craft::$app->getConfig()->getGeneral()->devMode) {
            return $message;
        }

        return implode("\n", [
            $message,
            '',
            'Debug:',
            "- Exception: {$exceptionClass}",
            "- File: {$exception->getFile()}:{$exception->getLine()}",
            '',
            $exception->getTraceAsString(),
        ]);
    }
}
