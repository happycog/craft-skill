<?php

declare(strict_types=1);

namespace happycog\craftmcp\cli;

use Craft;
use CuyZ\Valinor\Mapper\ArgumentsMapper;
use happycog\craftmcp\tools\AddFieldToFieldLayout;
use happycog\craftmcp\tools\ApplyDraft;
use happycog\craftmcp\tools\CreateAsset;
use happycog\craftmcp\tools\CreateDraft;
use happycog\craftmcp\tools\CreateEntry;
use happycog\craftmcp\tools\CreateEntryType;
use happycog\craftmcp\tools\CreateField;
use happycog\craftmcp\tools\CreateFieldLayout;
use happycog\craftmcp\tools\CreateSection;
use happycog\craftmcp\tools\DeleteAsset;
use happycog\craftmcp\tools\DeleteEntry;
use happycog\craftmcp\tools\DeleteEntryType;
use happycog\craftmcp\tools\DeleteField;
use happycog\craftmcp\tools\DeleteSection;
use happycog\craftmcp\tools\GetEntry;
use happycog\craftmcp\tools\GetEntryTypes;
use happycog\craftmcp\tools\GetFieldLayout;
use happycog\craftmcp\tools\GetFields;
use happycog\craftmcp\tools\GetFieldTypes;
use happycog\craftmcp\tools\GetSections;
use happycog\craftmcp\tools\GetSites;
use happycog\craftmcp\tools\GetVolumes;
use happycog\craftmcp\tools\SearchContent;
use happycog\craftmcp\tools\UpdateAsset;
use happycog\craftmcp\tools\UpdateDraft;
use happycog\craftmcp\tools\UpdateEntry;
use happycog\craftmcp\tools\UpdateEntryType;
use happycog\craftmcp\tools\UpdateField;
use happycog\craftmcp\tools\UpdateSection;

class CommandRouter
{
    /**
     * @var array<string, array{class: class-string, method: string}>
     */
    private const COMMAND_MAP = [
        'entries/create' => ['class' => CreateEntry::class, 'method' => 'create'],
        'entries/get' => ['class' => GetEntry::class, 'method' => 'get'],
        'entries/update' => ['class' => UpdateEntry::class, 'method' => 'update'],
        'entries/delete' => ['class' => DeleteEntry::class, 'method' => 'delete'],
        'entries/search' => ['class' => SearchContent::class, 'method' => 'search'],
        'sections/create' => ['class' => CreateSection::class, 'method' => 'create'],
        'sections/list' => ['class' => GetSections::class, 'method' => 'get'],
        'sections/update' => ['class' => UpdateSection::class, 'method' => 'update'],
        'sections/delete' => ['class' => DeleteSection::class, 'method' => 'delete'],
        'entry-types/create' => ['class' => CreateEntryType::class, 'method' => 'create'],
        'entry-types/list' => ['class' => GetEntryTypes::class, 'method' => 'getAll'],
        'entry-types/update' => ['class' => UpdateEntryType::class, 'method' => 'update'],
        'entry-types/delete' => ['class' => DeleteEntryType::class, 'method' => 'delete'],
        'fields/create' => ['class' => CreateField::class, 'method' => 'create'],
        'fields/list' => ['class' => GetFields::class, 'method' => 'get'],
        'fields/types' => ['class' => GetFieldTypes::class, 'method' => 'get'],
        'fields/update' => ['class' => UpdateField::class, 'method' => 'update'],
        'fields/delete' => ['class' => DeleteField::class, 'method' => 'delete'],
        'drafts/create' => ['class' => CreateDraft::class, 'method' => 'create'],
        'drafts/update' => ['class' => UpdateDraft::class, 'method' => 'update'],
        'drafts/apply' => ['class' => ApplyDraft::class, 'method' => 'apply'],
        'field-layouts/create' => ['class' => CreateFieldLayout::class, 'method' => 'create'],
        'field-layouts/get' => ['class' => GetFieldLayout::class, 'method' => 'get'],
        'sites/list' => ['class' => GetSites::class, 'method' => 'get'],
        'assets/create' => ['class' => CreateAsset::class, 'method' => 'create'],
        'assets/update' => ['class' => UpdateAsset::class, 'method' => 'update'],
        'assets/delete' => ['class' => DeleteAsset::class, 'method' => 'delete'],
        'volumes/list' => ['class' => GetVolumes::class, 'method' => 'get'],
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
        if (!isset(self::COMMAND_MAP[$command])) {
            throw new \InvalidArgumentException("Unknown command: {$command}");
        }

        $config = self::COMMAND_MAP[$command];
        $toolClass = $config['class'];
        $methodName = $config['method'];

        // Get the tool instance from DI container
        $tool = Craft::$container->get($toolClass);

        // Get method reflection to understand parameters
        $reflection = new \ReflectionMethod($tool, $methodName);
        $parameters = $reflection->getParameters();

        // Merge positional arguments with flags by parameter name
        $mergedParams = $this->mergeArguments($parameters, $positional, $flags);

        // Create a callable reference to the tool method
        $callable = [$tool, $methodName];

        // Use Valinor to validate and type-cast parameters
        // @phpstan-ignore-next-line - Dynamic callable creation from verified command map
        $arguments = $this->mapper->mapArguments($callable, $mergedParams);

        // Call the tool method with validated parameters
        /** @var array<string, mixed> $result */
        // @phpstan-ignore-next-line - Dynamic callable invocation from verified command map
        $result = $callable(...$arguments);

        return $result;
    }

    /**
     * Merge positional and flag arguments into a single array keyed by parameter name.
     *
     * @param array<int, \ReflectionParameter> $parameters
     * @param array<int, mixed> $positional
     * @param array<string, mixed> $flags
     * @return array<string, mixed>
     */
    private function mergeArguments(array $parameters, array $positional, array $flags): array
    {
        $merged = [];

        // Map positional arguments to parameter names in order
        foreach ($positional as $index => $value) {
            if (isset($parameters[$index])) {
                $paramName = $parameters[$index]->getName();
                $merged[$paramName] = $value;
            }
        }

        // Add all flag arguments
        foreach ($flags as $key => $value) {
            $merged[$key] = $value;
        }

        return $merged;
    }
}
