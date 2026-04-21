<?php

declare(strict_types=1);

namespace happycog\craftmcp\llm;

use happycog\craftmcp\base\CommandMap;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * Builds JSON-Schema tool definitions from the existing invokable tool classes.
 *
 * Reflects on every tool in CommandMap, inspecting its __invoke() signature
 * and PHPDoc to produce provider-agnostic schemas that drivers convert to
 * their wire format (Anthropic input_schema, OpenAI function parameters, etc.).
 */
final class ToolSchemaBuilder
{
    /** @var array<string, array<string, mixed>>|null */
    private ?array $tools = null;

    /** @var array<string, class-string>|null */
    private ?array $nameToClass = null;

    /**
     * All tool definitions keyed by tool name.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getTools(): array
    {
        if ($this->tools === null) {
            $this->build();
        }

        /** @var array<string, array<string, mixed>> */
        return $this->tools;
    }

    /**
     * Resolve a tool name back to its FQCN.
     *
     * @return class-string|null
     */
    public function getClass(string $toolName): ?string
    {
        if ($this->nameToClass === null) {
            $this->build();
        }

        return $this->nameToClass[$toolName] ?? null;
    }

    // ------------------------------------------------------------------
    // Internals
    // ------------------------------------------------------------------

    private function build(): void
    {
        $this->tools = [];
        $this->nameToClass = [];

        foreach (CommandMap::all() as $class) {
            $reflection = new ReflectionClass($class);

            if (! $reflection->hasMethod('__invoke')) {
                continue;
            }

            $method = $reflection->getMethod('__invoke');
            $name   = $reflection->getShortName();

            $this->nameToClass[$name] = $class;
            $this->tools[$name] = [
                'name'        => $name,
                'description' => $this->extractDescription($method),
                'parameters'  => $this->buildParameterSchema($method),
            ];
        }
    }

    /**
     * Pull the free-text description from a method's PHPDoc (everything before the first @-tag).
     */
    private function extractDescription(ReflectionMethod $method): string
    {
        $doc = $method->getDocComment();

        if ($doc === false) {
            return '';
        }

        $lines       = explode("\n", $doc);
        $description = [];

        foreach ($lines as $line) {
            $line = trim($line, " \t/*");

            if (str_starts_with($line, '@')) {
                break;
            }

            if ($line !== '') {
                $description[] = $line;
            }
        }

        return implode("\n", $description);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildParameterSchema(ReflectionMethod $method): array
    {
        $properties = [];
        $required   = [];
        $paramDocs  = $this->extractParamDocs($method);

        foreach ($method->getParameters() as $param) {
            $name   = $param->getName();
            $schema = $this->parameterToSchema($param, $method);

            if (isset($paramDocs[$name])) {
                $schema['description'] = $paramDocs[$name];
            }

            $properties[$name] = $schema;

            if (! $param->isDefaultValueAvailable() && ! $param->allowsNull()) {
                $required[] = $name;
            }
        }

        $result = [
            'type'       => 'object',
            'properties' => (object) $properties, // force JSON object even when empty
        ];

        if ($required !== []) {
            $result['required'] = $required;
        }

        return $result;
    }

    /**
     * Map a single PHP parameter to a JSON-Schema fragment.
     *
     * @return array<string, mixed>
     */
    private function parameterToSchema(ReflectionParameter $param, ReflectionMethod $method): array
    {
        $type = $param->getType();

        if (! $type instanceof ReflectionNamedType) {
            return ['type' => 'string'];
        }

        $typeName = $type->getName();

        // For arrays, attempt to determine the inner type from PHPDoc
        if ($typeName === 'array') {
            $schema = $this->resolveArraySchema($param, $method);
        } else {
            $schema = match ($typeName) {
                'int'    => ['type' => 'integer'],
                'float'  => ['type' => 'number'],
                'bool'   => ['type' => 'boolean'],
                'string' => ['type' => 'string'],
                default  => ['type' => 'string'],
            };
        }

        if ($param->isDefaultValueAvailable()) {
            $default = $param->getDefaultValue();

            if ($default !== null) {
                $schema['default'] = $default;
            }
        }

        return $schema;
    }

    /**
     * Resolve array parameters to the best JSON-Schema representation
     * by inspecting @param annotations for generic types.
     *
     * @return array<string, mixed>
     */
    private function resolveArraySchema(ReflectionParameter $param, ReflectionMethod $method): array
    {
        $doc = $method->getDocComment();

        if ($doc === false) {
            return ['type' => 'object'];
        }

        $name = preg_quote($param->getName(), '/');

        // Match @param array<int> $name  →  array of integers
        if (preg_match('/@param\s+(?:\?)?array<int>\s*(?:\|null)?\s+\$' . $name . '/', $doc)) {
            return ['type' => 'array', 'items' => ['type' => 'integer']];
        }

        // Match @param array<string> $name  →  array of strings
        if (preg_match('/@param\s+(?:\?)?array<string>\s*(?:\|null)?\s+\$' . $name . '/', $doc)) {
            return ['type' => 'array', 'items' => ['type' => 'string']];
        }

        // Match @param array<int>|null $name  →  array of integers (nullable variant)
        if (preg_match('/@param\s+array<int>\|null\s+\$' . $name . '/', $doc)) {
            return ['type' => 'array', 'items' => ['type' => 'integer']];
        }

        // Default: associative array → JSON object
        return ['type' => 'object'];
    }

    /**
     * Extract @param descriptions from PHPDoc and inline doc comments on parameters.
     *
     * @return array<string, string>
     */
    private function extractParamDocs(ReflectionMethod $method): array
    {
        $docs = [];

        // 1. Standard @param tags from the method doc block
        $docComment = $method->getDocComment();

        if ($docComment !== false) {
            // Match @param type $name description (possibly multi-word)
            preg_match_all(
                '/@param\s+\S+\s+\$(\w+)\s+(.+?)(?=\n\s*\*\s*@|\n\s*\*\/|\z)/s',
                $docComment,
                $matches,
                PREG_SET_ORDER,
            );

            foreach ($matches as $match) {
                $paramDescription = preg_replace('/\s*\*\s*/', ' ', $match[2]) ?? $match[2];
                $docs[$match[1]] = trim($paramDescription);
            }
        }

        // 2. Inline /** ... */ comments before parameters in the method signature
        $filename = $method->getFileName();

        if ($filename !== false && is_readable($filename)) {
            $allLines = file($filename) ?: [];
            $start    = $method->getStartLine() - 1;
            $end      = min($method->getEndLine(), count($allLines));
            $source   = implode('', array_slice($allLines, $start, $end - $start));

            // Match inline doc comments: /** description */ before $paramName
            preg_match_all(
                '/\/\*\*\s*(.*?)\s*\*\/\s*(?:(?:\?)?[A-Za-z_\\\\|]+\s+)?\$([a-zA-Z_]\w*)/s',
                $source,
                $inlineMatches,
                PREG_SET_ORDER,
            );

            foreach ($inlineMatches as $match) {
                $comment = preg_replace('/\s*\*\s*/', ' ', $match[1]) ?? $match[1];
                $comment = trim($comment);

                // Inline comments override @param (they're more specific)
                if ($comment !== '') {
                    $docs[$match[2]] = $comment;
                }
            }
        }

        return $docs;
    }
}
