<?php

declare(strict_types=1);

namespace happycog\craftmcp\cli;

use ReflectionMethod;

class HelpGenerator
{

    /**
     * Generate help output listing all available commands with descriptions.
     *
     * @return string Formatted help text
     */
    public function generate(): string
    {
        $output = "Agent Craft CLI - Craft CMS content management for AI agents\n\n";
        $output .= "Usage: agent-craft <command> [arguments] [options]\n\n";
        $output .= "Options:\n";
        $output .= "  -h, --help     Show this help message\n";
        $output .= "  -v, -vv, -vvv  Increase verbosity level\n";
        $output .= "  --path=<path>  Path to Craft installation\n\n";
        $output .= "Available commands:\n";

        $commands = $this->getCommandDescriptions();

        // Find the longest command name for alignment
        $maxLength = 0;
        foreach ($commands as $command => $description) {
            $maxLength = max($maxLength, strlen($command));
        }

        // Format each command with its description
        foreach ($commands as $command => $description) {
            $padding = str_repeat(' ', $maxLength - strlen($command) + 2);
            $output .= "  {$command}{$padding}{$description}\n";
        }

        return $output;
    }

    /**
     * Get all commands with their descriptions from docblocks.
     *
     * @return array<string, string> Command names mapped to descriptions
     */
    public function getCommandDescriptions(): array
    {
        $commands = [];

        foreach (CommandMap::all() as $command => $toolClass) {
            $description = $this->extractDescription($toolClass);
            $commands[$command] = $description;
        }

        return $commands;
    }

    /**
     * Extract the first line of a method's docblock.
     *
     * @param class-string $class
     * @return string The first line of the docblock, or empty string if none
     */
    private function extractDescription(string $class): string
    {
        try {
            $reflection = new ReflectionMethod($class, '__invoke');
            $docComment = $reflection->getDocComment();

            if ($docComment === false) {
                return '';
            }

            return $this->parseFirstLine($docComment);
        } catch (\ReflectionException) {
            return '';
        }
    }

    /**
     * Generate detailed help for a specific command.
     *
     * @param string $command The command to generate help for
     * @return string Formatted help text
     * @throws \InvalidArgumentException If command is not found
     */
    public function generateForCommand(string $command): string
    {
        $toolClass = CommandMap::getToolClass($command);
        
        if ($toolClass === null) {
            throw new \InvalidArgumentException("Unknown command: {$command}");
        }

        $output = "Command: {$command}\n\n";

        try {
            $reflection = new ReflectionMethod($toolClass, '__invoke');

            // Extract full docblock
            $docComment = $reflection->getDocComment();
            if ($docComment !== false) {
                $formatted = $this->formatDocblock($docComment);
                $output .= $formatted . "\n\n";
            }

            // Extract parameters
            $parameters = $reflection->getParameters();
            if (count($parameters) > 0) {
                $output .= "Parameters:\n";
                foreach ($parameters as $param) {
                    $paramName = $param->getName();
                    $paramType = $param->getType();
                    $typeStr = $paramType ? $paramType->__toString() : 'mixed';

                    // Show if optional and default value
                    if ($param->isOptional()) {
                        $defaultValue = $param->isDefaultValueAvailable()
                            ? $this->formatDefaultValue($param->getDefaultValue())
                            : 'null';
                        $output .= "  --{$paramName}  ({$typeStr}, optional, default: {$defaultValue})\n";
                    } else {
                        $output .= "  --{$paramName}  ({$typeStr}, required)\n";
                    }
                }
            }
        } catch (\ReflectionException $e) {
            $output .= "Error: Unable to load command details\n";
        }

        return $output;
    }

    /**
     * Format a docblock for display.
     *
     * Removes comment delimiters, asterisks, and PHPDoc tags (@param, @return, etc.)
     * while preserving content structure.
     *
     * @param string $docComment The raw docblock comment
     * @return string Formatted docblock text
     */
    private function formatDocblock(string $docComment): string
    {
        // Remove opening /** and closing */
        $docComment = preg_replace('/^\/\*\*\s*/', '', $docComment) ?? '';
        $docComment = preg_replace('/\s*\*\/$/', '', $docComment) ?? '';

        // Split into lines
        $lines = explode("\n", $docComment);

        $formatted = [];
        $skipRemainingLines = false;

        foreach ($lines as $line) {
            // Remove leading asterisks and exactly one space if present
            $line = preg_replace('/^\s*\*\s?/', '', $line) ?? '';

            // Stop processing when we hit the first @param, @return, or other PHPDoc tag
            if (preg_match('/^\s*@(param|return|throws|var|see|deprecated|since|author|copyright|license|link|todo|internal|ignore)/', $line)) {
                $skipRemainingLines = true;
                continue;
            }

            // Skip all lines after we've hit a PHPDoc tag
            if ($skipRemainingLines) {
                continue;
            }

            // Keep the line (including empty lines for structure)
            $formatted[] = $line;
        }

        // Trim trailing empty lines
        while (count($formatted) > 0 && trim($formatted[count($formatted) - 1]) === '') {
            array_pop($formatted);
        }

        return implode("\n", $formatted);
    }

    /**
     * Format a default value for display in help text.
     *
     * @param mixed $value The default value to format
     * @return string Formatted representation of the value
     */
    private function formatDefaultValue(mixed $value): string
    {
        return match (true) {
            is_null($value) => 'null',
            is_bool($value) => $value ? 'true' : 'false',
            is_string($value) => "'{$value}'",
            is_int($value) || is_float($value) => (string) $value,
            is_array($value) => $value === [] ? '[]' : '[...]',
            default => gettype($value),
        };
    }

    /**
     * Parse the first meaningful line from a docblock.
     *
     * @param string $docComment The raw docblock comment
     * @return string The first line of description text
     */
    private function parseFirstLine(string $docComment): string
    {
        // Remove the opening /** and closing */
        $docComment = preg_replace('/^\/\*\*\s*/', '', $docComment) ?? '';
        $docComment = preg_replace('/\s*\*\/$/', '', $docComment) ?? '';

        // Split into lines
        $lines = explode("\n", $docComment);

        $description = '';

        foreach ($lines as $line) {
            // Remove leading asterisks and whitespace
            $line = preg_replace('/^\s*\*\s?/', '', $line) ?? '';
            $line = trim($line);

            // Skip empty lines at the start
            if ($line === '' && $description === '') {
                continue;
            }

            // Stop at @param, @return, or empty line after content
            if (str_starts_with($line, '@') || ($description !== '' && $line === '')) {
                break;
            }

            // Accumulate the first sentence/line
            if ($description !== '') {
                $description .= ' ';
            }
            $description .= $line;

            // Stop at the first period followed by space or end
            if (preg_match('/\.\s|\.$/u', $description)) {
                // Trim to the first sentence
                if (preg_match('/^(.+?\.)\s/u', $description, $matches)) {
                    $description = $matches[1];
                }
                break;
            }
        }

        return trim($description);
    }
}
