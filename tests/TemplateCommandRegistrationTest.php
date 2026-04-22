<?php

use CuyZ\Valinor\MapperBuilder;
use happycog\craftmcp\base\CommandMap;
use happycog\craftmcp\cli\CommandRouter;
use happycog\craftmcp\tools\GetTemplate;
use happycog\craftmcp\tools\ListTemplates;
use happycog\craftmcp\tools\SearchTemplates;
use yii\helpers\FileHelper;

beforeEach(function () {
    $this->originalTemplatesAlias = Craft::getAlias('@templates', false);
    $this->templatesPath = Craft::$app->getPath()->getTempPath() . '/template-router-' . uniqid();

    FileHelper::createDirectory($this->templatesPath . '/partials');
    file_put_contents($this->templatesPath . '/partials/example.twig', "{{ entry.title }}\n");

    Craft::setAlias('@templates', $this->templatesPath);

    $this->router = new CommandRouter(
        (new MapperBuilder())
            ->allowPermissiveTypes()
            ->allowScalarValueCasting()
            ->argumentsMapper(),
    );
});

afterEach(function () {
    if ($this->originalTemplatesAlias === false) {
        Craft::setAlias('@templates', null);
    } else {
        Craft::setAlias('@templates', $this->originalTemplatesAlias);
    }

    FileHelper::removeDirectory($this->templatesPath);
});

test('registers template commands in command map', function () {
    expect(CommandMap::getToolClass('templates/list'))->toBe(ListTemplates::class);
    expect(CommandMap::getToolClass('templates/get'))->toBe(GetTemplate::class);
    expect(CommandMap::getToolClass('templates/search'))->toBe(SearchTemplates::class);
});

test('routes templates/get command', function () {
    $result = $this->router->route('templates/get', [], ['filename' => 'partials/example.twig']);

    expect($result['filename'])->toBe('partials/example.twig');
    expect($result['contents'])->toContain('{{ entry.title }}');
});

test('help output includes template commands', function () {
    $generator = new \happycog\craftmcp\cli\HelpGenerator();
    $output = $generator->generate();

    expect($output)->toContain('templates/list');
    expect($output)->toContain('templates/get');
    expect($output)->toContain('templates/search');
});
