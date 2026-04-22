<?php

declare(strict_types=1);

namespace happycog\craftmcp\llm;

use happycog\craftmcp\base\CommandMap;
use happycog\craftmcp\tools\OpenUrl;
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

    /** @var array<int, class-string> */
    private const CHAT_ONLY_TOOLS = [
        OpenUrl::class,
    ];

    /** @var array<string, array<string, mixed>>|null */
    private ?array $tools = null;

    /** @var array<string, array<string, mixed>>|null */
    private ?array $compactTools = null;

    /** @var array<string, array<string, mixed>>|null */
    private ?array $minimalTools = null;

    /** @var array<string, class-string>|null */
    private ?array $nameToClass = null;

    /** @var array<string, true>|null */
    private ?array $chatOnlyNames = null;

    /**
     * All tool definitions keyed by tool name.
     *
     * @param  array<int, string>|null $toolNames
     * @return array<string, array<string, mixed>>
     */
    public function getTools(?array $toolNames = null, bool $compact = false, bool $includeToolSearch = false, bool $minimal = false, bool $includeChatOnly = false): array
    {
        $tools = $minimal
            ? $this->minimalTools()
            : ($compact ? $this->compactTools() : $this->fullTools());

        $tools = $this->filterChatOnlyTools($tools, $includeChatOnly);

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
    public function getTool(string $toolName, bool $compact = false, bool $includeChatOnly = false): ?array
    {
        if ($toolName === self::TOOL_SEARCH) {
            return $this->buildToolSearchDefinition($compact);
        }

        $tools = $this->filterChatOnlyTools(
            $compact ? $this->compactTools() : $this->fullTools(),
            $includeChatOnly,
        );

        return $tools[$toolName] ?? null;
    }

    /**
     * Return a tool's input schema in a compact, agent-friendly shape.
     *
     * @return array<string, mixed>|null
     */
    public function getToolInputSchema(string $toolName, bool $includeChatOnly = false): ?array
    {
        $tool = $this->getTool($this->resolveToolDefinitionName($toolName), includeChatOnly: $includeChatOnly);

        if ($tool === null) {
            return null;
        }

        $parameters = is_array($tool['parameters'] ?? null) ? $tool['parameters'] : [];

        return [
            'type' => is_string($parameters['type'] ?? null) ? $parameters['type'] : 'object',
            'required' => is_array($parameters['required'] ?? null)
                ? array_values(array_filter($parameters['required'], 'is_string'))
                : [],
            'properties' => $this->summarizeParameters($parameters),
        ];
    }

    private function resolveToolDefinitionName(string $toolName): string
    {
        if (isset($this->fullTools()[$toolName])) {
            return $toolName;
        }

        $toolClass = CommandMap::getToolClass($toolName);

        if ($toolClass === null) {
            return $toolName;
        }

        $reflection = new ReflectionClass($toolClass);

        return $reflection->getShortName();
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
    public function searchTools(?string $query = null, ?array $names = null, int $limit = 8, bool $includeChatOnly = false): array
    {
        $limit = max(1, min($limit, 20));
        $tools = $this->filterChatOnlyTools($this->compactTools(), $includeChatOnly);

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
        $this->minimalTools = [];
        $this->nameToClass = [];
        $this->chatOnlyNames = [];

        foreach ($this->allToolClasses() as $class) {
            $reflection = new ReflectionClass($class);

            if (! $reflection->hasMethod('__invoke')) {
                continue;
            }

            $method = $reflection->getMethod('__invoke');
            $name   = $reflection->getShortName();

            $this->nameToClass[$name] = $class;

            if (in_array($class, self::CHAT_ONLY_TOOLS, true)) {
                $this->chatOnlyNames[$name] = true;
            }

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
            $this->minimalTools[$name] = [
                'name'        => $name,
                'description' => '',
                'parameters'  => [
                    'type' => 'object',
                    'properties' => (object) [],
                ],
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
     * @return array<string, array<string, mixed>>
     */
    private function minimalTools(): array
    {
        if ($this->minimalTools === null) {
            $this->build();
        }

        /** @var array<string, array<string, mixed>> */
        return $this->minimalTools;
    }

    /**
     * @return array<int, class-string>
     */
    private function allToolClasses(): array
    {
        /** @var array<int, class-string> */
        return array_values(array_unique([
            ...array_values(CommandMap::all()),
            ...self::CHAT_ONLY_TOOLS,
        ]));
    }

    /**
     * @param array<string, array<string, mixed>> $tools
     * @return array<string, array<string, mixed>>
     */
    private function filterChatOnlyTools(array $tools, bool $includeChatOnly): array
    {
        if ($includeChatOnly) {
            return $tools;
        }

        if ($this->chatOnlyNames === null) {
            $this->build();
        }

        foreach (array_keys($this->chatOnlyNames ?? []) as $toolName) {
            unset($tools[$toolName]);
        }

        return $tools;
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
                ? 'Find relevant tools first. Use a short capability query and a small limit.'
                : "Find relevant tools and inspect their parameters before calling them.\n\nUse this first to discover the smallest set of tools needed for the task. Prefer a short capability-style `query` like `find entry by slug`, `create draft from entry`, `update draft content`, or `list sections`, rather than a long natural-language sentence. Keep `limit` small, usually 3-5. If you already know candidate tool names, pass `names` to inspect those exact tools and get their parameter summaries.",
            'parameters' => [
                'type' => 'object',
                'properties' => (object) [
                    'query' => [
                        'type' => 'string',
                        'description' => $compact
                            ? 'Short capability phrase, like "create draft".'
                            : 'Short capability phrase describing what you need, like "find entry by slug", "create draft from entry", or "list sections". Prefer concise keywords over a full sentence.',
                    ],
                    'names' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                        'description' => $compact
                            ? 'Exact tool names to inspect further.'
                            : 'Optional exact tool names to inspect directly when you already know candidates from a previous ToolSearch result.',
                    ],
                    'limit' => [
                        'type' => 'integer',
                        'description' => $compact
                            ? 'Maximum matches. Usually 3-5.'
                            : 'Maximum number of matches to return. Keep this small, usually 3-5, to reveal only the most relevant tools.',
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
                ? 'None of the requested tool names were found. Try ToolSearch again with broader capability keywords.'
                : 'Requested tool details returned. If one of these tools matches the task, stop searching and call that revealed tool now. Only call ToolSearch again if none of these tools fit or you still need a narrower comparison.';
        }

        if ($needle === '') {
            return 'Review the returned tools. If one already sounds right, call that revealed tool now. Otherwise call ToolSearch again with a short capability query or exact names to narrow the list.';
        }

        return $matchCount === 0
            ? 'No tools matched that search. Try broader or simpler capability keywords, such as "entry", "draft", "section", or "asset", or inspect exact tool names.'
            : 'Relevant tools returned. If one of the revealed tools sounds like the right tool, call it now instead of searching again. Only call ToolSearch again when the current matches are ambiguous, too broad, or missing the needed capability.';
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
