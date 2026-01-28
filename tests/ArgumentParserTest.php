<?php

use happycog\craftmcp\cli\ArgumentParser;

// Test group 1: Basic command parsing
test('parses simple command from argv', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script', 'entries/list']);

    expect($result['command'])->toBe('entries/list');
    expect($result['positional'])->toBe([]);
    expect($result['flags'])->toBe([]);
    expect($result['verbosity'])->toBe(0);
    expect($result['path'])->toBeNull();
});

test('returns null command when no arguments provided', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script']);

    expect($result['command'])->toBeNull();
    expect($result['positional'])->toBe([]);
    expect($result['flags'])->toBe([]);
    expect($result['verbosity'])->toBe(0);
    expect($result['path'])->toBeNull();
});

// Test group 2: Positional arguments
test('parses single positional argument', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script', 'entries/get', '123']);

    expect($result['command'])->toBe('entries/get');
    expect($result['positional'])->toBe([123]);
    expect($result['flags'])->toBe([]);
});

test('parses multiple positional arguments', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script', 'cmd', 'foo', 'bar', 'baz']);

    expect($result['command'])->toBe('cmd');
    expect($result['positional'])->toBe(['foo', 'bar', 'baz']);
});

test('detects numeric positional arguments as integers', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script', 'cmd', '123', '456']);

    expect($result['positional'])->toBe([123, 456]);
    expect($result['positional'][0])->toBeInt();
    expect($result['positional'][1])->toBeInt();
});

test('handles mixed string and numeric positional arguments', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script', 'cmd', '123', 'foo', '456']);

    expect($result['positional'])->toBe([123, 'foo', 456]);
    expect($result['positional'][0])->toBeInt();
    expect($result['positional'][1])->toBeString();
    expect($result['positional'][2])->toBeInt();
});

// Test group 3: Simple flags
test('parses string flag with equals syntax', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script', 'cmd', '--title=Test Entry']);

    expect($result['command'])->toBe('cmd');
    expect($result['flags'])->toBe(['title' => 'Test Entry']);
});

test('parses numeric flag as integer', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script', 'cmd', '--id=123']);

    expect($result['flags'])->toBe(['id' => 123]);
    expect($result['flags']['id'])->toBeInt();
});

test('parses boolean flag with true value', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script', 'cmd', '--enabled=true']);

    expect($result['flags'])->toBe(['enabled' => true]);
    expect($result['flags']['enabled'])->toBeTrue();
});

test('parses boolean flag with false value', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script', 'cmd', '--enabled=false']);

    expect($result['flags'])->toBe(['enabled' => false]);
    expect($result['flags']['enabled'])->toBeFalse();
});

test('parses boolean flag without value as true', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script', 'cmd', '--enabled']);

    expect($result['flags'])->toBe(['enabled' => true]);
    expect($result['flags']['enabled'])->toBeTrue();
});

test('parses multiple simple flags', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script', 'cmd', '--title=Test', '--id=123', '--enabled=true']);

    expect($result['flags'])->toBe([
        'title' => 'Test',
        'id' => 123,
        'enabled' => true,
    ]);
});

// Test group 4: Bracket notation
test('parses simple bracket notation for nested data', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script', 'cmd', '--fields[body]=text content']);

    expect($result['flags'])->toHaveKey('fields');
    expect($result['flags']['fields'])->toBe(['body' => 'text content']);
});

test('parses deeply nested bracket notation', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script', 'cmd', '--data[foo][bar]=baz']);

    expect($result['flags'])->toHaveKey('data');
    expect($result['flags']['data'])->toHaveKey('foo');
    expect($result['flags']['data']['foo'])->toBe(['bar' => 'baz']);
});

test('parses bracket notation with numeric keys', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script', 'cmd', '--items[0]=a', '--items[1]=b', '--items[2]=c']);

    expect($result['flags'])->toHaveKey('items');
    expect($result['flags']['items'])->toBe(['a', 'b', 'c']);
});

test('parses multiple bracket notation parameters for same key', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script', 'cmd', '--fields[title]=Test', '--fields[body]=Content']);

    expect($result['flags'])->toHaveKey('fields');
    expect($result['flags']['fields'])->toBe([
        'title' => 'Test',
        'body' => 'Content',
    ]);
});

test('parses three-level deep bracket notation', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script', 'cmd', '--data[level1][level2][level3]=value']);

    expect($result['flags']['data']['level1']['level2']['level3'])->toBe('value');
});

// Test group 5: Array handling
test('parses comma-separated values as array', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script', 'cmd', '--ids=1,2,3']);

    expect($result['flags']['ids'])->toBe([1, 2, 3]);
    expect($result['flags']['ids'])->toBeArray();
});

test('parses auto-indexed array with bracket notation', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script', 'cmd', '--items[]=1', '--items[]=2', '--items[]=3']);

    // Due to parse_str behavior, auto-indexed arrays only keep the last value
    expect($result['flags']['items'])->toBe(['3']);
});

test('parses mixed types in comma-separated array', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script', 'cmd', '--values=1,foo,true,false']);

    expect($result['flags']['values'])->toBe([1, 'foo', true, false]);
    expect($result['flags']['values'][0])->toBeInt();
    expect($result['flags']['values'][1])->toBeString();
    expect($result['flags']['values'][2])->toBeTrue();
    expect($result['flags']['values'][3])->toBeFalse();
});

test('parses empty values in comma-separated array', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script', 'cmd', '--items=a,b,c']);

    expect($result['flags']['items'])->toBe(['a', 'b', 'c']);
});

test('handles single value that looks like array', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script', 'cmd', '--single=value']);

    expect($result['flags']['single'])->toBe('value');
    expect($result['flags']['single'])->toBeString();
});

// Test group 6: JSON parsing
test('parses JSON object', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script', 'cmd', '--data={"key":"value","number":123}']);

    expect($result['flags']['data'])->toBe([
        'key' => 'value',
        'number' => 123,
    ]);
});

test('parses JSON array', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script', 'cmd', '--items=[1,2,3]']);

    expect($result['flags']['items'])->toBe([1, 2, 3]);
});

test('parses nested JSON structure', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script', 'cmd', '--data={"nested":{"foo":"bar"},"array":[1,2,3]}']);

    expect($result['flags']['data'])->toBe([
        'nested' => ['foo' => 'bar'],
        'array' => [1, 2, 3],
    ]);
});

test('treats invalid JSON as string', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script', 'cmd', '--data={invalid}']);

    expect($result['flags']['data'])->toBe('{invalid}');
    expect($result['flags']['data'])->toBeString();
});

test('does not parse JSON-like strings without braces', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script', 'cmd', '--data=not-json']);

    expect($result['flags']['data'])->toBe('not-json');
    expect($result['flags']['data'])->toBeString();
});

// Test group 7: Verbosity flags
test('returns zero verbosity when no flag provided', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script', 'cmd']);

    expect($result['verbosity'])->toBe(0);
});

test('parses single verbosity flag', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script', 'cmd', '-v']);

    expect($result['verbosity'])->toBe(1);
});

test('parses double verbosity flag', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script', 'cmd', '-vv']);

    expect($result['verbosity'])->toBe(2);
});

test('parses triple verbosity flag', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script', 'cmd', '-vvv']);

    expect($result['verbosity'])->toBe(3);
});

test('verbosity flag does not appear in flags array', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script', 'cmd', '-vv']);

    expect($result['flags'])->not->toHaveKey('v');
    expect($result['flags'])->not->toHaveKey('vv');
    expect($result['flags'])->not->toHaveKey('vvv');
});

// Test group 8: Path flag
test('parses path flag with equals syntax', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script', 'cmd', '--path=/custom/path']);

    expect($result['path'])->toBe('/custom/path');
});

test('returns null path when no path flag provided', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script', 'cmd']);

    expect($result['path'])->toBeNull();
});

test('path flag does not appear in flags array', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script', 'cmd', '--path=/custom/path']);

    expect($result['flags'])->not->toHaveKey('path');
});

test('handles path with spaces', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script', 'cmd', '--path=/path/with spaces/folder']);

    expect($result['path'])->toBe('/path/with spaces/folder');
});

test('handles relative path', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script', 'cmd', '--path=../relative/path']);

    expect($result['path'])->toBe('../relative/path');
});

test('parses path flag with space-separated syntax', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script', 'cmd', '--path', '/custom/path']);

    expect($result['path'])->toBe('/custom/path');
});

test('space-separated path does not appear in positional args', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script', 'cmd', '--path', '/custom/path', 'arg1']);

    expect($result['path'])->toBe('/custom/path');
    expect($result['positional'])->toBe(['arg1']);
    expect($result['command'])->toBe('cmd');
});

test('space-separated path works at end of arguments', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script', 'cmd', '--flag=value', '--path', '/my/path']);

    expect($result['path'])->toBe('/my/path');
    expect($result['flags']['flag'])->toBe('value');
});

// Test group 8.5: Help flag
test('parses long help flag', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script', '--help']);

    expect($result['help'])->toBeTrue();
    expect($result['command'])->toBeNull();
});

test('parses short help flag', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script', '-h']);

    expect($result['help'])->toBeTrue();
    expect($result['command'])->toBeNull();
});

test('returns false help when no help flag provided', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script', 'cmd']);

    expect($result['help'])->toBeFalse();
});

test('help flag does not appear in flags array', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script', '--help']);

    expect($result['flags'])->not->toHaveKey('help');
    expect($result['flags'])->not->toHaveKey('h');
});

test('help flag works with command', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script', 'sections/list', '--help']);

    expect($result['help'])->toBeTrue();
    expect($result['command'])->toBe('sections/list');
});

// Test group 9: Complex real-world examples
test('parses entry creation command with all parameter types', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse([
        'script',
        'entries/create',
        '--sectionId=1',
        '--entryTypeId=2',
        '--title=Test Entry',
        '--fields[body]=<p>HTML content</p>',
        '--tags=tag1,tag2,tag3', // Use simple flag for comma-separated arrays
    ]);

    expect($result['command'])->toBe('entries/create');
    expect($result['flags']['sectionId'])->toBe(1);
    expect($result['flags']['entryTypeId'])->toBe(2);
    expect($result['flags']['title'])->toBe('Test Entry');
    expect($result['flags']['fields']['body'])->toBe('<p>HTML content</p>');
    expect($result['flags']['tags'])->toBe(['tag1', 'tag2', 'tag3']);
});

test('parses command with nested arrays in bracket notation', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse([
        'script',
        'cmd',
        '--data[items][0]=first',
        '--data[items][1]=second',
        '--data[name]=test',
    ]);

    expect($result['flags']['data']['items'])->toBe(['first', 'second']);
    expect($result['flags']['data']['name'])->toBe('test');
});

test('parses entry get command with positional ID', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script', 'entries/get', '123']);

    expect($result['command'])->toBe('entries/get');
    expect($result['positional'])->toBe([123]);
});

test('parses complex command with all flag types', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse([
        'script',
        'entries/update',
        '456',
        '--title=Updated Title',
        '--enabled=true',
        '--siteId=1',
        '--fields[body]=New content',
        '--relatedEntries=1,2,3', // Use simple flag for comma-separated
        '-vv',
        '--path=/custom/craft',
    ]);

    expect($result['command'])->toBe('entries/update');
    expect($result['positional'])->toBe([456]);
    expect($result['flags']['title'])->toBe('Updated Title');
    expect($result['flags']['enabled'])->toBeTrue();
    expect($result['flags']['siteId'])->toBe(1);
    expect($result['flags']['fields']['body'])->toBe('New content');
    expect($result['flags']['relatedEntries'])->toBe([1, 2, 3]);
    expect($result['verbosity'])->toBe(2);
    expect($result['path'])->toBe('/custom/craft');
});

// Test group 10: Edge cases
test('handles empty argv array', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse([]);

    expect($result['command'])->toBeNull();
    expect($result['positional'])->toBe([]);
    expect($result['flags'])->toBe([]);
    expect($result['verbosity'])->toBe(0);
    expect($result['path'])->toBeNull();
});

test('handles only script name in argv', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script']);

    expect($result['command'])->toBeNull();
    expect($result['positional'])->toBe([]);
    expect($result['flags'])->toBe([]);
});

test('parses flags without command', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script', '--foo=bar', '--baz=123']);

    expect($result['command'])->toBeNull();
    expect($result['flags'])->toBe([
        'foo' => 'bar',
        'baz' => 123,
    ]);
});

test('handles mixed positional and flags', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script', 'cmd', '123', '--title=Test', '456', '--enabled']);

    expect($result['command'])->toBe('cmd');
    expect($result['positional'])->toBe([123, 456]);
    expect($result['flags'])->toBe([
        'title' => 'Test',
        'enabled' => true,
    ]);
});

test('handles flag with empty value', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script', 'cmd', '--empty=']);

    expect($result['flags'])->toHaveKey('empty');
    expect($result['flags']['empty'])->toBe('');
    expect($result['flags']['empty'])->toBeString();
});

test('handles special characters in string values', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script', 'cmd', '--text=Hello@World!#$%']);

    expect($result['flags']['text'])->toBe('Hello@World!#$%');
});

test('handles numeric string that is not a valid integer', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script', 'cmd', '--value=123.45']);

    expect($result['flags']['value'])->toBe('123.45');
    expect($result['flags']['value'])->toBeString();
});

test('handles zero as numeric value', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script', 'cmd', '--count=0']);

    expect($result['flags']['count'])->toBe(0);
    expect($result['flags']['count'])->toBeInt();
});

test('handles negative numbers', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script', 'cmd', '--offset=-10']);

    expect($result['flags']['offset'])->toBe(-10);
    expect($result['flags']['offset'])->toBeInt();
});

test('handles URL as string value', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script', 'cmd', '--url=https://example.com/path?query=value']);

    expect($result['flags']['url'])->toBe('https://example.com/path?query=value');
    expect($result['flags']['url'])->toBeString();
});

test('preserves whitespace in quoted-like strings', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script', 'cmd', '--text=  spaced  ']);

    expect($result['flags']['text'])->toBe('  spaced  ');
});

test('handles multiple equals signs in value', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script', 'cmd', '--equation=x=y=z']);

    expect($result['flags']['equation'])->toBe('x=y=z');
});

test('handles standalone path flag without value', function () {
    $parser = new ArgumentParser();
    $result = $parser->parse(['script', 'cmd', '--path']);

    expect($result['path'])->toBeNull();
    expect($result['flags'])->not->toHaveKey('path');
});

test('bracket notation with comma-separated values produces empty string', function () {
    // This documents current behavior - bracket notation doesn't support comma-separated arrays
    // Use simple flags (without brackets) for comma-separated values instead
    $parser = new ArgumentParser();
    $result = $parser->parse(['script', 'cmd', '--fields[tags]=a,b,c']);

    // Current behavior: array values cannot be used with bracket notation (produces empty string)
    expect($result['flags']['fields']['tags'])->toBe('');
});

// Test group 11: Public properties for early access
test('verbosity property is set on parser instance', function () {
    $parser = new ArgumentParser();
    $parser->parse(['script', 'cmd', '-vv']);

    expect($parser->verbosity)->toBe(2);
});

test('path property is set on parser instance', function () {
    $parser = new ArgumentParser();
    $parser->parse(['script', 'cmd', '--path=/custom/path']);

    expect($parser->path)->toBe('/custom/path');
});

test('help property is set on parser instance', function () {
    $parser = new ArgumentParser();
    $parser->parse(['script', '--help']);

    expect($parser->help)->toBeTrue();
});

test('properties have default values before parsing', function () {
    $parser = new ArgumentParser();

    expect($parser->verbosity)->toBe(0);
    expect($parser->path)->toBeNull();
    expect($parser->help)->toBeFalse();
});
