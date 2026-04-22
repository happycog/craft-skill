<?php

declare(strict_types=1);

namespace happycog\craftmcp\cli;

use CuyZ\Valinor\Mapper\MappingError;
use happycog\craftmcp\llm\ToolSchemaBuilder;

final class ValidationErrorFormatter
{
    public function formatMappingError(MappingError $error, ?string $toolName = null): string
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

        $lines = [
            '# Tool validation failed',
            '',
        ];

        if ($toolName !== null) {
            $lines[] = "Tool: `{$toolName}`";
            $lines[] = '';
        }

        $lines[] = '## Problems';
        $lines[] = '';

        foreach ($errors as $validationError) {
            $parameter = $validationError['parameter'];
            $message = $validationError['message'];
            $lines[] = "- `{$parameter}`: {$message}";
        }

        if ($toolName !== null) {
            $schema = (new ToolSchemaBuilder())->getToolInputSchema($toolName);
            $schemaJson = json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $lines[] = '';
            $lines[] = '## Retry tips';
            $lines[] = '';
            $lines[] = '- Read the validation messages carefully before retrying; they often identify the wrong parameter or shape directly.';
            $lines[] = '- Retry with arguments that match the schema below.';
            $lines[] = '';
            $lines[] = '## Input schema';
            $lines[] = '';
            $lines[] = '```json';
            $lines[] = $schemaJson === false ? '{}' : $schemaJson;
            $lines[] = '```';
        }

        return implode("\n", $lines);
    }

    public function formatToolArgumentError(string $toolName, string $message): string
    {
        $schema = (new ToolSchemaBuilder())->getToolInputSchema($toolName);
        $schemaJson = json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return implode("\n", [
            '# Tool call failed',
            '',
            "Tool: `{$toolName}`",
            '',
            '## Error',
            '',
            $message,
            '',
            '## Retry tips',
            '',
            '- Check the schema and required parameters below before retrying.',
            '- Retry with corrected arguments.',
            '',
            '## Input schema',
            '',
            '```json',
            $schemaJson === false ? '{}' : $schemaJson,
            '```',
        ]);
    }
}
