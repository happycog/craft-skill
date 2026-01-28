<?php

declare(strict_types=1);

namespace happycog\craftmcp\cli;

use ReflectionMethod;

class HelpGenerator
{
    /**
     * @var array<string, array{class: class-string, method: string}>
     */
    private const COMMAND_MAP = [
        'assets/create' => ['class' => \happycog\craftmcp\tools\CreateAsset::class, 'method' => 'create'],
        'assets/delete' => ['class' => \happycog\craftmcp\tools\DeleteAsset::class, 'method' => 'delete'],
        'assets/update' => ['class' => \happycog\craftmcp\tools\UpdateAsset::class, 'method' => 'update'],
        'drafts/apply' => ['class' => \happycog\craftmcp\tools\ApplyDraft::class, 'method' => 'apply'],
        'drafts/create' => ['class' => \happycog\craftmcp\tools\CreateDraft::class, 'method' => 'create'],
        'drafts/update' => ['class' => \happycog\craftmcp\tools\UpdateDraft::class, 'method' => 'update'],
        'entries/create' => ['class' => \happycog\craftmcp\tools\CreateEntry::class, 'method' => 'create'],
        'entries/delete' => ['class' => \happycog\craftmcp\tools\DeleteEntry::class, 'method' => 'delete'],
        'entries/get' => ['class' => \happycog\craftmcp\tools\GetEntry::class, 'method' => 'get'],
        'entries/search' => ['class' => \happycog\craftmcp\tools\SearchContent::class, 'method' => 'search'],
        'entries/update' => ['class' => \happycog\craftmcp\tools\UpdateEntry::class, 'method' => 'update'],
        'entry-types/create' => ['class' => \happycog\craftmcp\tools\CreateEntryType::class, 'method' => 'create'],
        'entry-types/delete' => ['class' => \happycog\craftmcp\tools\DeleteEntryType::class, 'method' => 'delete'],
        'entry-types/list' => ['class' => \happycog\craftmcp\tools\GetEntryTypes::class, 'method' => 'getAll'],
        'entry-types/update' => ['class' => \happycog\craftmcp\tools\UpdateEntryType::class, 'method' => 'update'],
        'field-layouts/create' => ['class' => \happycog\craftmcp\tools\CreateFieldLayout::class, 'method' => 'create'],
        'field-layouts/get' => ['class' => \happycog\craftmcp\tools\GetFieldLayout::class, 'method' => 'get'],
        'fields/create' => ['class' => \happycog\craftmcp\tools\CreateField::class, 'method' => 'create'],
        'fields/delete' => ['class' => \happycog\craftmcp\tools\DeleteField::class, 'method' => 'delete'],
        'fields/list' => ['class' => \happycog\craftmcp\tools\GetFields::class, 'method' => 'get'],
        'fields/types' => ['class' => \happycog\craftmcp\tools\GetFieldTypes::class, 'method' => 'get'],
        'fields/update' => ['class' => \happycog\craftmcp\tools\UpdateField::class, 'method' => 'update'],
        'sections/create' => ['class' => \happycog\craftmcp\tools\CreateSection::class, 'method' => 'create'],
        'sections/delete' => ['class' => \happycog\craftmcp\tools\DeleteSection::class, 'method' => 'delete'],
        'sections/list' => ['class' => \happycog\craftmcp\tools\GetSections::class, 'method' => 'get'],
        'sections/update' => ['class' => \happycog\craftmcp\tools\UpdateSection::class, 'method' => 'update'],
        'sites/list' => ['class' => \happycog\craftmcp\tools\GetSites::class, 'method' => 'get'],
        'volumes/list' => ['class' => \happycog\craftmcp\tools\GetVolumes::class, 'method' => 'get'],
    ];

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

        foreach (self::COMMAND_MAP as $command => $config) {
            $description = $this->extractDescription($config['class'], $config['method']);
            $commands[$command] = $description;
        }

        return $commands;
    }

    /**
     * Extract the first line of a method's docblock.
     *
     * @param class-string $class
     * @param string $method
     * @return string The first line of the docblock, or empty string if none
     */
    private function extractDescription(string $class, string $method): string
    {
        try {
            $reflection = new ReflectionMethod($class, $method);
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
        if (!isset(self::COMMAND_MAP[$command])) {
            throw new \InvalidArgumentException("Unknown command: {$command}");
        }

        $config = self::COMMAND_MAP[$command];
        $output = "Command: {$command}\n\n";

        try {
            $reflection = new ReflectionMethod($config['class'], $config['method']);

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
                            ? var_export($param->getDefaultValue(), true)
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
