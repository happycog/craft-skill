<?php

declare(strict_types=1);

namespace happycog\craftmcp\cli;

use Craft;
use CuyZ\Valinor\Mapper\ArgumentsMapper;

class CommandRouter
{

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

        // Get the tool instance from DI container
        $tool = Craft::$container->get($toolClass);

        // Get __invoke method reflection to understand parameters
        $reflection = new \ReflectionMethod($tool, '__invoke');
        $parameters = $reflection->getParameters();

        // Merge positional arguments with flags by parameter name
        $mergedParams = $this->mergeArguments($parameters, $positional, $flags);

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
     * Merge positional and flag arguments into a single array keyed by parameter name.
     *
     * Resolves file references (values like @filename.json) by reading the file contents
     * and parsing as JSON. File references are resolved relative to the current working directory.
     *
     * @param array<int, \ReflectionParameter> $parameters
     * @param array<int, mixed> $positional
     * @param array<string, mixed> $flags
     * @return array<string, mixed>
     * @throws \InvalidArgumentException When file cannot be read or parsed
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
                $merged[$paramName] = $this->resolveFileReference($value);
            }
        }

        // Resolve file references in flags
        foreach ($flags as $key => $value) {
            if (isset($paramNames[$key])) {
                // This flag matches a method parameter directly
                $merged[$key] = $this->resolveFileReference($value);
            }
        }

        return $merged;
    }

    /**
     * Resolve file references in argument values.
     *
     * If the value is a marker array with '__file__' key (created by ArgumentParser),
     * reads the file and parses it as JSON. Otherwise returns the value unchanged.
     *
     * @param mixed $value The value to potentially resolve
     * @return mixed The resolved value
     * @throws \InvalidArgumentException When file cannot be read or contains invalid JSON
     */
    private function resolveFileReference(mixed $value): mixed
    {
        // Check if this is a file reference marker array
        if (is_array($value) && isset($value['__file__']) && count($value) === 1) {
            $filename = $value['__file__'];
            
            // Read file relative to current working directory
            $filepath = getcwd() . '/' . $filename;
            
            if (!file_exists($filepath)) {
                throw new \InvalidArgumentException("File not found: {$filename}");
            }
            
            $contents = file_get_contents($filepath);
            if ($contents === false) {
                throw new \InvalidArgumentException("Failed to read file: {$filename}");
            }
            
            $decoded = json_decode($contents, true);
            if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
                throw new \InvalidArgumentException(
                    "Invalid JSON in file {$filename}: " . json_last_error_msg()
                );
            }
            
            return $decoded ?? [];
        }
        
        // Not a file reference, return as-is
        return $value;
    }
}
