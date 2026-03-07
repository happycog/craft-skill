<?php

declare(strict_types=1);

namespace happycog\craftmcp\cli;

use function count;
use function is_array;
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
     * Verbosity level parsed from arguments.
     * Set early in parse() so it's available even if parsing fails later.
     */
    public int $verbosity = 0;

    /**
     * Path parsed from arguments.
     * Set early in parse() so it's available even if parsing fails later.
     */
    public ?string $path = null;

    /**
     * Help flag parsed from arguments.
     * Set early in parse() so it's available even if parsing fails later.
     */
    public bool $help = false;

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
        $nextIsPath = false;

        // First pass: extract verbosity, path, and help flags
        // This ensures these values are available even if later parsing fails
        foreach ($args as $arg) {
            if ($nextIsPath) {
                $this->path = $arg;
                $nextIsPath = false;
                continue;
            }

            if ($arg === '--help' || $arg === '-h') {
                $this->help = true;
            } elseif ($arg === '-v') {
                $this->verbosity = 1;
            } elseif ($arg === '-vv') {
                $this->verbosity = 2;
            } elseif ($arg === '-vvv') {
                $this->verbosity = 3;
            } elseif (str_starts_with($arg, '--path=')) {
                $this->path = substr($arg, 7);
            } elseif ($arg === '--path') {
                $nextIsPath = true;
            }
        }

        // Second pass: parse command, positional arguments, and flags
        $nextIsPath = false;
        $nextIsFlagValue = null;
        $i = 0;
        $argCount = count($args);
        
        while ($i < $argCount) {
            $arg = $args[$i];
            
            // Handle flag value from previous iteration
            if ($nextIsFlagValue !== null) {
                // This argument is the value for the previous flag
                $this->parseFlagWithValue($nextIsFlagValue, $arg, $flags);
                $nextIsFlagValue = null;
                $i++;
                continue;
            }
            
            // Skip --path value (already parsed)
            if ($nextIsPath) {
                $nextIsPath = false;
                $i++;
                continue;
            }

            // Skip special flags (already parsed)
            if ($arg === '--help' || $arg === '-h' ||
                $arg === '-v' || $arg === '-vv' || $arg === '-vvv' ||
                str_starts_with($arg, '--path=')) {
                $i++;
                continue;
            }
            if ($arg === '--path') {
                $nextIsPath = true;
                $i++;
                continue;
            }

            // Handle flag arguments (--key=value or --key)
            if (str_starts_with($arg, '--')) {
                // Check if this has an equals sign
                if (str_contains($arg, '=')) {
                    // Has equals sign, parse as single argument
                    $this->parseFlag($arg, $flags);
                } else {
                    // No equals sign, check if next arg is a value or another flag
                    $nextArg = ($i + 1 < $argCount) ? $args[$i + 1] : null;
                    
                    if ($nextArg === null || str_starts_with($nextArg, '--') || str_starts_with($nextArg, '-')) {
                        // Next arg is a flag or doesn't exist, treat as boolean
                        $this->parseFlag($arg, $flags);
                    } else {
                        // Next arg is the value for this flag
                        $nextIsFlagValue = substr($arg, 2); // Remove '--' prefix
                    }
                }
                $i++;
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
            
            $i++;
        }

        return [
            'command' => $command,
            'positional' => $positional,
            'flags' => $flags,
            'verbosity' => $this->verbosity,
            'path' => $this->path,
            'help' => $this->help,
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
     * Parse a flag with a separate value argument.
     *
     * This handles space-separated flag syntax like: --key value
     *
     * @param string $key The flag key (without '--' prefix)
     * @param string $value The value argument
     * @param array<string, mixed> $flags The flags array to modify (passed by reference)
     */
    private function parseFlagWithValue(string $key, string $value, array &$flags): void
    {
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
        $parsed = [];
        $valueStr = is_scalar($value) || $value === null ? (string) $value : '';
        parse_str($key . '=' . urlencode($valueStr), $parsed);

        // Merge the parsed structure into flags
        /** @var array<string, mixed> $parsedTyped */
        $parsedTyped = $parsed;
        $flags = $this->mergeArrays($flags, $parsedTyped);
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
     * Parse a scalar value (bool or string).
     *
     * Numeric strings are intentionally kept as strings. Valinor's
     * allowScalarValueCasting() handles string-to-int conversion when
     * the target parameter expects an int, which avoids type mismatches
     * when a numeric string is passed to a parameter expecting string|null.
     *
     * @param string $value The value to parse
     * @return bool|string The parsed scalar value
     */
    private function parseScalar(string $value): bool|string
    {
        // Check for boolean
        if ($value === 'true') {
            return true;
        }
        if ($value === 'false') {
            return false;
        }

        // Return as string — Valinor handles int casting when needed
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
