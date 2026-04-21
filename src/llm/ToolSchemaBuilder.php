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
    public const TOOL_SEARCH = 'ToolSearch';

    /** @var array<string, array<string, mixed>>|null */
    private ?array $tools = null;

    /** @var array<string, array<string, mixed>>|null */
    private ?array $compactTools = null;

    /** @var array<string, class-string>|null */
    private ?array $nameToClass = null;

    /**
     * All tool definitions keyed by tool name.
     *
     * @param  array<int, string>|null $toolNames
     * @return array<string, array<string, mixed>>
     */
    public function getTools(?array $toolNames = null, bool $compact = false, bool $includeToolSearch = false): array
    {
        $tools = $compact ? $this->compactTools() : $this->fullTools();

        if ($toolNames !== null) {
            $tools = array_intersect_key($tools, array_fill_keys($toolNames, true));
        }

        if ($includeToolSearch) {
            $tools[self::TOOL_SEARCH] = $this->buildToolSearchDefinition($compact);
        }

        /** @var array<string, array<string, mixed>> */
        return $tools;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getTool(string $toolName, bool $compact = false): ?array
    {
        if ($toolName === self::TOOL_SEARCH) {
            return $this->buildToolSearchDefinition($compact);
        }

        $tools = $compact ? $this->compactTools() : $this->fullTools();

        return $tools[$toolName] ?? null;
    }

    /**
     * Resolve a tool name back to its FQCN.
     *
     * @return class-string|null
     */
    public function getClass(string $toolName): ?string
    {
        if ($toolName === self::TOOL_SEARCH) {
            return null;
        }

        if ($this->nameToClass === null) {
            $this->build();
        }

        return $this->nameToClass[$toolName] ?? null;
    }

    /**
     * Return compact metadata for tools matching a user intent.
     *
     * @param  array<string>|null $names
     * @return array<string, mixed>
     */
    public function searchTools(?string $query = null, ?array $names = null, int $limit = 8): array
    {
        $limit = max(1, min($limit, 20));
        $tools = $this->compactTools();

        /** @var array<int, string> $requestedNames */
        $requestedNames = array_values(array_filter(
            $names ?? [],
            static fn (mixed $name): bool => is_string($name) && $name !== '',
        ));

        $needle  = mb_strtolower(trim($query ?? ''));
        $matches = [];

        foreach ($tools as $name => $tool) {
            if ($requestedNames !== [] && ! in_array($name, $requestedNames, true)) {
                continue;
            }

            $score = $this->toolSearchScore($name, $tool, $needle, $requestedNames);

            if ($score <= 0) {
                continue;
            }

            $matches[] = [
                'score' => $score,
                'tool' => [
                    'name' => $name,
                    'description' => $tool['description'],
                    'parameters' => $this->summarizeParameters(
                        is_array($tool['parameters'] ?? null) ? $tool['parameters'] : [],
                    ),
                ],
            ];
        }

        usort($matches, static function (array $left, array $right): int {
            $scoreCompare = $right['score'] <=> $left['score'];

            if ($scoreCompare !== 0) {
                return $scoreCompare;
            }

            return strcmp($left['tool']['name'], $right['tool']['name']);
        });

        $matches = array_slice($matches, 0, $limit);

        return [
            '_notes' => $this->buildToolSearchNotes($needle, $requestedNames, count($matches)),
            'revealedTools' => array_values(array_map(
                static fn (array $match): string => $match['tool']['name'],
                $matches,
            )),
            'matches' => array_values(array_map(
                static fn (array $match): array => $match['tool'],
                $matches,
            )),
        ];
    }

    // ------------------------------------------------------------------
    // Internals
    // ------------------------------------------------------------------

    private function build(): void
    {
        $this->tools = [];
        $this->compactTools = [];
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
            $this->compactTools[$name] = [
                'name'        => $name,
                'description' => $this->extractDescription($method, compact: true),
                'parameters'  => $this->buildParameterSchema($method, compact: true),
            ];
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function fullTools(): array
    {
        if ($this->tools === null) {
            $this->build();
        }

        /** @var array<string, array<string, mixed>> */
        return $this->tools;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function compactTools(): array
    {
        if ($this->compactTools === null) {
            $this->build();
        }

        /** @var array<string, array<string, mixed>> */
        return $this->compactTools;
    }

    /**
     * Pull the free-text description from a method's PHPDoc (everything before the first @-tag).
     */
    private function extractDescription(ReflectionMethod $method, bool $compact = false): string
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

        $text = implode("\n", $description);

        return $compact ? $this->compactText($text) : $text;
    }

    /**
     * @param  bool $compact
     * @return array<string, mixed>
     */
    private function buildParameterSchema(ReflectionMethod $method, bool $compact = false): array
    {
        $properties = [];
        $required   = [];
        $paramDocs  = $this->extractParamDocs($method);

        foreach ($method->getParameters() as $param) {
            $name   = $param->getName();
            $schema = $this->parameterToSchema($param, $method, $compact);

            if (isset($paramDocs[$name])) {
                $schema['description'] = $compact ? $this->compactText($paramDocs[$name]) : $paramDocs[$name];
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
    private function parameterToSchema(ReflectionParameter $param, ReflectionMethod $method, bool $compact = false): array
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

        if (! $compact && $param->isDefaultValueAvailable()) {
            $default = $param->getDefaultValue();

            if ($default !== null) {
                $schema['default'] = $default;
            }
        }

        return $schema;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildToolSearchDefinition(bool $compact): array
    {
        return [
            'name' => self::TOOL_SEARCH,
            'description' => $compact
                ? 'Find relevant tools and inspect their parameters before calling them.'
                : "Find relevant tools and inspect their parameters before calling them.\n\nUse this first to discover the smallest set of tools needed for the task. Pass `query` for intent-based matching, or `names` when you already know exact tool names and want their parameter summaries.",
            'parameters' => [
                'type' => 'object',
                'properties' => (object) [
                    'query' => [
                        'type' => 'string',
                        'description' => $compact
                            ? 'What you want to do.'
                            : 'Short description of the task or capability you need, like "create an entry" or "list sections".',
                    ],
                    'names' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                        'description' => $compact
                            ? 'Exact tool names to inspect.'
                            : 'Optional exact tool names to inspect directly when you already know candidates.',
                    ],
                    'limit' => [
                        'type' => 'integer',
                        'description' => $compact
                            ? 'Maximum matches to return.'
                            : 'Maximum number of matches to return. Keep this small to reveal only the most relevant tools.',
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed> $tool
     * @param  array<int, string> $requestedNames
     */
    private function toolSearchScore(string $name, array $tool, string $needle, array $requestedNames): int
    {
        if ($requestedNames !== []) {
            return in_array($name, $requestedNames, true) ? 1000 : 0;
        }

        if ($needle === '') {
            return 100;
        }

        $haystacks = [
            mb_strtolower($name),
            mb_strtolower(is_string($tool['description'] ?? null) ? $tool['description'] : ''),
        ];

        $score = 0;

        foreach ($haystacks as $haystack) {
            if ($haystack === '') {
                continue;
            }

            if ($haystack === $needle) {
                $score = max($score, 900);
            } elseif (str_contains($haystack, $needle)) {
                $score = max($score, 700);
            }
        }

        foreach (preg_split('/\s+/', $needle) ?: [] as $token) {
            if ($token === '') {
                continue;
            }

            foreach ($haystacks as $haystack) {
                if ($haystack !== '' && str_contains($haystack, $token)) {
                    $score += 50;
                }
            }
        }

        return $score;
    }

    /**
     * @param  array<string, mixed> $parameters
     * @return array<int, array<string, mixed>>
     */
    private function summarizeParameters(array $parameters): array
    {
        $properties = $parameters['properties'] ?? [];
        $required = $parameters['required'] ?? [];

        if (! is_object($properties) && ! is_array($properties)) {
            return [];
        }

        /** @var array<string, mixed> $propertyList */
        $propertyList = (array) $properties;
        /** @var array<int, string> $requiredList */
        $requiredList = is_array($required) ? array_values(array_filter($required, 'is_string')) : [];

        $summary = [];

        foreach ($propertyList as $name => $schema) {
            if (! is_array($schema)) {
                continue;
            }

            $type = is_string($schema['type'] ?? null) ? $schema['type'] : 'string';

            if ($type === 'array' && is_array($schema['items'] ?? null) && is_string($schema['items']['type'] ?? null)) {
                $type .= '<' . $schema['items']['type'] . '>';
            }

            $row = [
                'name' => $name,
                'type' => $type,
                'required' => in_array($name, $requiredList, true),
            ];

            if (is_string($schema['description'] ?? null) && $schema['description'] !== '') {
                $row['description'] = $schema['description'];
            }

            $summary[] = $row;
        }

        return $summary;
    }

    /**
     * @param  array<int, string> $requestedNames
     */
    private function buildToolSearchNotes(string $needle, array $requestedNames, int $matchCount): string
    {
        if ($requestedNames !== []) {
            return $matchCount === 0
                ? 'None of the requested tool names were found.'
                : 'Requested tool details returned. Only the listed tools will be revealed for direct use.';
        }

        if ($needle === '') {
            return 'Browse the returned tools, then call ToolSearch again with exact names to inspect a narrower set.';
        }

        return $matchCount === 0
            ? 'No tools matched that search. Try broader wording or inspect exact tool names.'
            : 'Relevant tools returned. Use the revealed tool names directly on the next turn, or call ToolSearch again with exact names for a tighter set.';
    }

    private function compactText(string $text, int $maxLength = 160): string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? $text);

        if ($text === '') {
            return '';
        }

        $sentence = preg_split('/(?<=[.!?])\s+/', $text, 2)[0] ?? $text;

        if (mb_strlen($sentence) <= $maxLength) {
            return $sentence;
        }

        return rtrim(mb_substr($sentence, 0, $maxLength - 1)) . '…';
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
