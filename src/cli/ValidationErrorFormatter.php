<?php

declare(strict_types=1);

namespace happycog\craftmcp\cli;

use CuyZ\Valinor\Mapper\MappingError;
use happycog\craftmcp\llm\ToolSchemaBuilder;

final class ValidationErrorFormatter
{
    /**
     * @return array<string, mixed>
     */
    public function formatMappingError(MappingError $error, ?string $toolName = null): array
    {
        $errors = [];

        foreach ($error->messages() as $message) {
            $paramName = $message->name();
            $msgText = (string) $message;

            if ($paramName !== '') {
                $errors[] = [
                    'parameter' => $paramName,
                    'message' => $msgText,
                ];
            }
        }

        if ($errors === []) {
            foreach ($error->messages() as $message) {
                $errors[] = [
                    'parameter' => $message->name() ?: 'root',
                    'message' => (string) $message,
                ];
            }
        }

        $payload = [
            'validation_errors' => $errors,
        ];

        if ($toolName !== null) {
            $payload['tool'] = $toolName;
            $payload['input_schema'] = (new ToolSchemaBuilder())->getToolInputSchema($toolName);
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public function formatToolArgumentError(string $toolName, string $message): array
    {
        return [
            'error' => $message,
            'tool' => $toolName,
            'input_schema' => (new ToolSchemaBuilder())->getToolInputSchema($toolName),
        ];
    }
}
