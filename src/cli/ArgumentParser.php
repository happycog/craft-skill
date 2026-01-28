<?php

declare(strict_types=1);

namespace happycog\craftmcp\cli;

use function count;
use function is_array;
use function is_numeric;
use function json_decode;
use function parse_str;
use function preg_match;
use function str_contains;
use function str_starts_with;
use function strlen;
use function strpos;
use function substr;

class ArgumentParser
{
    /**
     * Parse CLI arguments into structured data.
     *
     * Takes raw CLI arguments and parses them into a structured format suitable
     * for AI agent tool invocations. Supports positional args, flags, bracket
     * notation, arrays, JSON, and special flags like verbosity and path.
     *
     * @param array<int, string> $argv Raw CLI arguments (from global $argv)
     * @return array{command: string|null, positional: array<int, mixed>, flags: array<string, mixed>, verbosity: int, path: string|null, help: bool}
     */
    public function parse(array $argv): array
    {
        // Skip script name ($argv[0])
        $args = array_slice($argv, 1);

        $command = null;
        /** @var array<int, mixed> $positional */
        $positional = [];
        /** @var array<string, mixed> $flags */
        $flags = [];
        $verbosity = 0;
        $path = null;
        $help = false;
        $nextIsPath = false;

        foreach ($args as $arg) {
            // Handle value for --path when it was the previous argument
            if ($nextIsPath) {
                $path = $arg;
                $nextIsPath = false;
                continue;
            }

            // Handle help flags
            if ($arg === '--help' || $arg === '-h') {
                $help = true;
                continue;
            }

            // Handle verbosity flags
            if ($arg === '-v') {
                $verbosity = 1;
                continue;
            }
            if ($arg === '-vv') {
                $verbosity = 2;
                continue;
            }
            if ($arg === '-vvv') {
                $verbosity = 3;
                continue;
            }

            // Handle --path flag separately (supports both --path=value and --path value)
            if (str_starts_with($arg, '--path=')) {
                $path = substr($arg, 7); // Remove '--path=' prefix
                continue;
            }
            if ($arg === '--path') {
                $nextIsPath = true;
                continue;
            }

            // Handle flag arguments (--key=value or --key)
            if (str_starts_with($arg, '--')) {
                $this->parseFlag($arg, $flags);
                continue;
            }

            // Handle positional arguments
            if ($command === null) {
                // First positional argument is the command
                $command = $arg;
            } else {
                // Subsequent positional arguments
                $positional[] = $this->parseValue($arg);
            }
        }

        return [
            'command' => $command,
            'positional' => $positional,
            'flags' => $flags,
            'verbosity' => $verbosity,
            'path' => $path,
            'help' => $help,
        ];
    }

    /**
     * Parse a flag argument and merge it into the flags array.
     *
     * Handles various flag formats:
     * - Simple flags: --key=value
     * - Bracket notation: --fields[body]=text
     * - Auto-indexed arrays: --items[]=1
     * - Boolean flags: --enabled
     *
     * @param string $arg The flag argument to parse
     * @param array<string, mixed> $flags The flags array to modify (passed by reference)
     */
    private function parseFlag(string $arg, array &$flags): void
    {
        // Remove leading '--'
        $arg = substr($arg, 2);

        // Check if this is a key=value pair
        $equalsPos = strpos($arg, '=');
        if ($equalsPos === false) {
            // Boolean flag (no value)
            $flags[$arg] = true;
            return;
        }

        $key = substr($arg, 0, $equalsPos);
        $value = substr($arg, $equalsPos + 1);

        // Parse the value
        $parsedValue = $this->parseValue($value);

        // Handle bracket notation (e.g., fields[body]=text or items[]=1)
        if (str_contains($key, '[')) {
            $this->parseBracketNotation($key, $parsedValue, $flags);
        } else {
            // Simple key=value
            $flags[$key] = $parsedValue;
        }
    }

    /**
     * Parse bracket notation and merge into flags array.
     *
     * Handles both named keys (fields[body]=text) and auto-indexed arrays (items[]=1).
     *
     * @param string $key The key with bracket notation
     * @param mixed $value The parsed value
     * @param array<string, mixed> $flags The flags array to modify (passed by reference)
     */
    private function parseBracketNotation(string $key, mixed $value, array &$flags): void
    {
        // Use parse_str to handle bracket notation
        /** @var array<string, mixed> $parsed */
        $parsed = [];
        parse_str($key . '=' . urlencode((string) $value), $parsed);

        // Merge the parsed structure into flags
        $flags = $this->mergeArrays($flags, $parsed);
    }

    /**
     * Recursively merge arrays, handling auto-indexed arrays correctly.
     *
     * @param array<string, mixed> $array1 The base array
     * @param array<string, mixed> $array2 The array to merge in
     * @return array<string, mixed> The merged array
     */
    private function mergeArrays(array $array1, array $array2): array
    {
        foreach ($array2 as $key => $value) {
            if (isset($array1[$key]) && is_array($array1[$key]) && is_array($value)) {
                // Both values are arrays, merge recursively
                $array1[$key] = $this->mergeArrays($array1[$key], $value);
            } else {
                // Overwrite or set new value
                $array1[$key] = $value;
            }
        }

        return $array1;
    }

    /**
     * Parse a value string and auto-detect its type.
     *
     * Handles:
     * - JSON objects/arrays (strings starting with { or [)
     * - Comma-separated arrays (1,2,3)
     * - Booleans (true/false)
     * - Numbers (integers)
     * - Strings (default)
     *
     * @param string $value The value to parse
     * @return mixed The parsed value with appropriate type
     */
    private function parseValue(string $value): mixed
    {
        // Check for JSON
        if ($this->isJson($value)) {
            $decoded = json_decode($value, true);
            if ($decoded !== null) {
                return $decoded;
            }
        }

        // Check for comma-separated array
        if (str_contains($value, ',')) {
            /** @var array<int, mixed> $arrayValue */
            $arrayValue = [];
            foreach (explode(',', $value) as $item) {
                $arrayValue[] = $this->parseScalar(trim($item));
            }
            return $arrayValue;
        }

        // Parse as scalar value
        return $this->parseScalar($value);
    }

    /**
     * Parse a scalar value (bool, int, or string).
     *
     * @param string $value The value to parse
     * @return bool|int|string The parsed scalar value
     */
    private function parseScalar(string $value): bool|int|string
    {
        // Check for boolean
        if ($value === 'true') {
            return true;
        }
        if ($value === 'false') {
            return false;
        }

        // Check for integer
        if (is_numeric($value) && (string) (int) $value === $value) {
            return (int) $value;
        }

        // Return as string
        return $value;
    }

    /**
     * Check if a string is valid JSON.
     *
     * @param string $value The value to check
     * @return bool True if the value is valid JSON
     */
    private function isJson(string $value): bool
    {
        // Quick check: must start with { or [
        if (!str_starts_with($value, '{') && !str_starts_with($value, '[')) {
            return false;
        }

        // Attempt to decode
        json_decode($value);
        return json_last_error() === JSON_ERROR_NONE;
    }
}
