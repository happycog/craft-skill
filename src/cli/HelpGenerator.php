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
     * Parse the first meaningful line from a docblock.
     *
     * @param string $docComment The raw docblock comment
     * @return string The first line of description text
     */
    private function parseFirstLine(string $docComment): string
    {
        // Remove the opening /** and closing */
        $docComment = preg_replace('/^\/\*\*\s*/', '', $docComment);
        $docComment = preg_replace('/\s*\*\/$/', '', $docComment ?? '');

        // Split into lines
        $lines = explode("\n", $docComment ?? '');

        $description = '';

        foreach ($lines as $line) {
            // Remove leading asterisks and whitespace
            $line = preg_replace('/^\s*\*\s?/', '', $line);
            $line = trim($line ?? '');

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
