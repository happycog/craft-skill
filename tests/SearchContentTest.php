<?php

use happycog\craftmcp\tools\SearchContent;
use happycog\craftmcp\tools\GetSections;

beforeEach(function () {
    $this->searchContent = Craft::$container->get(SearchContent::class);
    $this->sections = Craft::$container->get(GetSections::class)->get();
    $this->validSectionId = $this->sections[0]['id'];
    $this->invalidSectionId = 99999;
});

it('searches content with query parameter (backward compatibility)', function () {
    $result = $this->searchContent->search('content');
    
    expect($result)->toBeArray();
    expect($result)->toHaveKeys(['_notes', 'results']);
    expect($result['_notes'])->toContain('search query "content"');
    expect($result['results'])->toBeInstanceOf(\Illuminate\Support\Collection::class);
});

it('searches content with both query and sectionIds parameters', function () {
    $result = $this->searchContent->search('content', 5, 'live', [$this->validSectionId]);
    
    expect($result)->toBeArray();
    expect($result)->toHaveKeys(['_notes', 'results']);
    expect($result['_notes'])->toContain('search query "content"');
    expect($result['_notes'])->toContain('section(s):');
    expect($result['results'])->toBeInstanceOf(\Illuminate\Support\Collection::class);
});

it('returns all entries from specific sections without query', function () {
    $result = $this->searchContent->search(null, 5, 'live', [$this->validSectionId]);
    
    expect($result)->toBeArray();
    expect($result)->toHaveKeys(['_notes', 'results']);
    expect($result['_notes'])->toContain('section(s):');
    expect($result['_notes'])->not->toContain('search query');
    expect($result['results'])->toBeInstanceOf(\Illuminate\Support\Collection::class);
});

it('returns all entries when no parameters provided', function () {
    $result = $this->searchContent->search();
    
    expect($result)->toBeArray();
    expect($result)->toHaveKeys(['_notes', 'results']);
    expect($result['_notes'])->toBe('The following entries were found.');
    expect($result['results'])->toBeInstanceOf(\Illuminate\Support\Collection::class);
});

it('handles multiple section IDs', function () {
    $sectionIds = array_slice(array_column($this->sections, 'id'), 0, 2);
    $result = $this->searchContent->search(null, 5, 'live', $sectionIds);
    
    expect($result)->toBeArray();
    expect($result)->toHaveKeys(['_notes', 'results']);
    expect($result['_notes'])->toContain('section(s):');
    expect($result['results'])->toBeInstanceOf(\Illuminate\Support\Collection::class);
});

it('throws exception for invalid section ID', function () {
    expect(fn() => $this->searchContent->search(null, 5, 'live', [$this->invalidSectionId]))
        ->toThrow(\RuntimeException::class, "Section with ID {$this->invalidSectionId} not found");
});

it('throws exception for mix of valid and invalid section IDs', function () {
    $mixedSectionIds = [$this->validSectionId, $this->invalidSectionId];
    
    expect(fn() => $this->searchContent->search(null, 5, 'live', $mixedSectionIds))
        ->toThrow(\RuntimeException::class, "Section with ID {$this->invalidSectionId} not found");
});

it('respects limit parameter with section filtering', function () {
    $limit = 2;
    $result = $this->searchContent->search(null, $limit, 'live', [$this->validSectionId]);
    
    expect($result['results']->count())->toBeLessThanOrEqual($limit);
});

it('respects status parameter with section filtering', function () {
    $result = $this->searchContent->search(null, 5, 'live', [$this->validSectionId]);
    
    expect($result)->toBeArray();
    expect($result)->toHaveKeys(['_notes', 'results']);
});

it('returns proper entry structure in results', function () {
    $result = $this->searchContent->search(null, 1, 'live', [$this->validSectionId]);
    
    if ($result['results']->isNotEmpty()) {
        $entry = $result['results']->first();
        expect($entry)->toHaveKeys(['entryId', 'title', 'url']);
        expect($entry['entryId'])->toBeInt();
        expect($entry['title'])->toBeString();
        expect($entry['url'])->toBeString();
        expect($entry['url'])->toContain('admin/edit/');
    }
});

it('handles empty sectionIds array', function () {
    $result = $this->searchContent->search('content', 5, 'live', []);
    
    expect($result)->toBeArray();
    expect($result)->toHaveKeys(['_notes', 'results']);
    expect($result['results'])->toBeInstanceOf(\Illuminate\Support\Collection::class);
});

it('maintains backward compatibility with existing calls', function () {
    // Test the old way of calling the method (positional parameters)
    $result = $this->searchContent->search('test');
    
    expect($result)->toBeArray();
    expect($result)->toHaveKeys(['_notes', 'results']);
    expect($result['_notes'])->toContain('search query "test"');
    expect($result['results'])->toBeInstanceOf(\Illuminate\Support\Collection::class);
});

it('generates correct notes for different parameter combinations', function () {
    // Query only
    $result1 = $this->searchContent->search('test');
    expect($result1['_notes'])->toBe('The following entries were found matching search query "test".');
    
    // Sections only
    $result2 = $this->searchContent->search(null, 5, 'live', [$this->validSectionId]);
    expect($result2['_notes'])->toMatch('/The following entries were found matching section\(s\): .+\./');
    
    // Neither
    $result3 = $this->searchContent->search();
    expect($result3['_notes'])->toBe('The following entries were found.');
});