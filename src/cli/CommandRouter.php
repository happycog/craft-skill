<?php

declare(strict_types=1);

namespace happycog\craftmcp\cli;

use Craft;
use CuyZ\Valinor\Mapper\ArgumentsMapper;
use happycog\craftmcp\base\CommandMap;

class CommandRouter
{
    private const ASSOCIATIVE_ARRAY_PARAMS = [
        'attributeAndFieldData' => true,
    ];

    public function __construct(
        protected ArgumentsMapper $mapper,
    ) {
    }

    /**
     * Route a command to its tool and execute.
     *
     * @param string $command
     * @param array<int, mixed> $positional Positional arguments
     * @param array<string, mixed> $flags Flag arguments
     * @return array<string, mixed> Tool execution result
     * @throws \InvalidArgumentException When command is not found
     * @throws \ReflectionException When method reflection fails
     */
    public function route(string $command, array $positional, array $flags): array
    {
        // Look up the command in the routing table
        $toolClass = CommandMap::getToolClass($command);

        if ($toolClass === null) {
            throw new \InvalidArgumentException("Unknown command: {$command}");
        }

        return $this->routeToolClass($toolClass, $positional, $flags);
    }

    /**
     * Execute an invokable tool class with the provided arguments.
     *
     * @param class-string $toolClass
     * @param array<int, mixed> $positional Positional arguments
     * @param array<string, mixed> $flags Flag arguments
     * @return array<string, mixed> Tool execution result
     * @throws \ReflectionException When method reflection fails
     */
    public function routeToolClass(string $toolClass, array $positional, array $flags): array
    {

        // Get the tool instance from DI container
        $tool = Craft::$container->get($toolClass);

        // Get __invoke method reflection to understand parameters
        $reflection = new \ReflectionMethod($tool, '__invoke');
        $parameters = $reflection->getParameters();

        // Merge positional arguments with flags by parameter name
        $mergedParams = $this->mergeArguments($parameters, $positional, $flags);

        $this->validateAssociativeArrayParameters($mergedParams);

        // Use Valinor to validate and type-cast parameters
        // @phpstan-ignore-next-line - Dynamic callable creation from verified command map
        $arguments = $this->mapper->mapArguments($tool, $mergedParams);

        // Call the tool with validated parameters
        /** @var array<string, mixed> $result */
        // @phpstan-ignore-next-line - Dynamic callable invocation from verified command map
        $result = $tool(...$arguments);

        return $result;
    }

    /**
     * @param array<string, mixed> $arguments
     */
    private function validateAssociativeArrayParameters(array $arguments): void
    {
        foreach (self::ASSOCIATIVE_ARRAY_PARAMS as $parameter => $_enabled) {
            if (!array_key_exists($parameter, $arguments)) {
                continue;
            }

            $value = $arguments[$parameter];

            if (!is_array($value)) {
                continue;
            }

            if (array_is_list($value) && $value !== []) {
                throw new \InvalidArgumentException("{$parameter} must be an object/map of attribute handles to values, not a list. Example: {$parameter}: {\"title\": \"Example\"}");
            }
        }
    }

    /**
     * Merge positional and flag arguments into a single array keyed by parameter name.
     *
     * Flags that don't match any method parameter are collected into the
     * 'attributeAndFieldData' parameter if it exists, allowing field data to be
     * passed via simple flags like --title=foo instead of --attributeAndFieldData[title]=foo
     *
     * @param array<int, \ReflectionParameter> $parameters
     * @param array<int, mixed> $positional
     * @param array<string, mixed> $flags
     * @return array<string, mixed>
     */
    private function mergeArguments(array $parameters, array $positional, array $flags): array
    {
        $merged = [];
        $paramNames = [];

        // Build a set of valid parameter names for quick lookup
        foreach ($parameters as $param) {
            $paramNames[$param->getName()] = true;
        }

        // Map positional arguments to parameter names in order
        foreach ($positional as $index => $value) {
            if (isset($parameters[$index])) {
                $paramName = $parameters[$index]->getName();
                $merged[$paramName] = $value;
            }
        }

        // Separate flags into direct parameters and field data
        $fieldData = [];
        foreach ($flags as $key => $value) {
            if (isset($paramNames[$key])) {
                // This flag matches a method parameter directly
                $merged[$key] = $value;
            } else {
                // This flag doesn't match a parameter, collect it as field data
                $fieldData[$key] = $value;
            }
        }

        // If there's unmatched field data and an attributeAndFieldData parameter exists,
        // merge the field data into it
        if (!empty($fieldData) && isset($paramNames['attributeAndFieldData'])) {
            $existing = $merged['attributeAndFieldData'] ?? [];
            if (!is_array($existing)) {
                $existing = [];
            }
            $merged['attributeAndFieldData'] = array_merge($existing, $fieldData);
        }

        return $merged;
    }
}
