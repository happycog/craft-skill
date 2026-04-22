<?php

use happycog\craftmcp\tools\GetTemplate;
use happycog\craftmcp\tools\ListTemplates;
use happycog\craftmcp\tools\SearchTemplates;
use yii\helpers\FileHelper;

beforeEach(function () {
    $this->originalTemplatesAlias = Craft::getAlias('@templates', false);
    $this->templatesPath = Craft::$app->getPath()->getTempPath() . '/template-tools-' . uniqid();

    FileHelper::createDirectory($this->templatesPath . '/emails');

    file_put_contents($this->templatesPath . '/index.twig', "<h1>{{ siteName }}</h1>\n");
    file_put_contents($this->templatesPath . '/emails/welcome.twig', "Hello {{ user.name }}\nNeedle line\n");

    Craft::setAlias('@templates', $this->templatesPath);

    $this->listTemplates = Craft::$container->get(ListTemplates::class);
    $this->getTemplate = Craft::$container->get(GetTemplate::class);
    $this->searchTemplates = Craft::$container->get(SearchTemplates::class);
});

afterEach(function () {
    if ($this->originalTemplatesAlias === false) {
        Craft::setAlias('@templates', null);
    } else {
        Craft::setAlias('@templates', $this->originalTemplatesAlias);
    }

    FileHelper::removeDirectory($this->templatesPath);
});

test('lists templates by relative filename', function () {
    $result = $this->listTemplates->__invoke();

    expect($result)->toBe([
        'emails/welcome.twig',
        'index.twig',
    ]);
});

test('gets template contents by filename', function () {
    $result = $this->getTemplate->__invoke('emails/welcome.twig');

    expect($result)->toBeArray();
    expect($result['filename'])->toBe('emails/welcome.twig');
    expect($result['contents'])->toContain('Hello {{ user.name }}');
    expect($result['contents'])->toContain('Needle line');
});

test('rejects template paths outside templates directory', function () {
    expect(fn() => $this->getTemplate->__invoke('../.env'))
        ->toThrow(\InvalidArgumentException::class, 'Template filename must be within the templates directory.');
});

test('searches template contents by needle', function () {
    $result = $this->searchTemplates->__invoke('Needle');

    expect($result)->toBeArray();
    expect($result['_notes'])->toBe('Found 1 template match(es) for needle "Needle".');
    expect($result['results'])->toBe([
        [
            'filename' => 'emails/welcome.twig',
            'lineNumber' => 2,
            'line' => 'Needle line',
        ],
    ]);
});

test('search returns empty results when no template matches', function () {
    $result = $this->searchTemplates->__invoke('missing-needle');

    expect($result['_notes'])->toBe('No template matches found for needle "missing-needle".');
    expect($result['results'])->toBe([]);
});
