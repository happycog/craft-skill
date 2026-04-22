<?php

declare(strict_types=1);

use CuyZ\Valinor\Mapper\MappingError;
use CuyZ\Valinor\MapperBuilder;
use happycog\craftmcp\cli\ValidationErrorFormatter;

test('formatMappingError returns markdown with retry tips and schema', function () {
    $mapper = (new MapperBuilder())
        ->allowPermissiveTypes()
        ->allowScalarValueCasting()
        ->mapper();

    try {
        $mapper->map('array{sectionId: int}', ['sectionId' => 'nope']);
        $this->fail('Expected mapping to fail.');
    } catch (MappingError $error) {
        $formatted = (new ValidationErrorFormatter())->formatMappingError($error, 'entries/create');

        expect($formatted)->toContain('# Tool validation failed')
            ->toContain('Tool: `entries/create`')
            ->toContain('## Problems')
            ->toContain('sectionId')
            ->toContain('## Retry tips')
            ->toContain('## Input schema')
            ->toContain('```json');
    }
});

test('formatToolArgumentError returns markdown with schema', function () {
    $formatted = (new ValidationErrorFormatter())->formatToolArgumentError(
        'entries/get',
        'Provide exactly one of entryId, uri, or slug.'
    );

    expect($formatted)->toContain('# Tool call failed')
        ->toContain('Tool: `entries/get`')
        ->toContain('## Error')
        ->toContain('Provide exactly one of entryId, uri, or slug.')
        ->toContain('## Input schema')
        ->toContain('```json');
});
