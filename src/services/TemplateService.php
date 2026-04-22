<?php

namespace happycog\craftmcp\services;

use Craft;
use craft\helpers\FileHelper;

class TemplateService
{
    /**
     * @return array<int, string>
     */
    public function listTemplates(): array
    {
        $templatesPath = $this->getTemplatesPath();

        /** @var array<int, string> $files */
        $files = FileHelper::findFiles($templatesPath);

        $relativePaths = array_map(fn(string $path): string => $this->relativeTemplatePath($path, $templatesPath), $files);
        sort($relativePaths);

        return array_values($relativePaths);
    }

    public function getTemplateContents(string $filename): string
    {
        $path = $this->resolveTemplatePath($filename);
        $contents = file_get_contents($path);

        throw_unless($contents !== false, \RuntimeException::class, "Unable to read template {$filename}.");

        return $contents;
    }

    /**
     * @return array<int, array{filename: string, lineNumber: int, line: string}>
     */
    public function searchTemplates(string $needle): array
    {
        throw_unless($needle !== '', \InvalidArgumentException::class, 'needle is required.');

        $results = [];

        foreach ($this->listTemplates() as $filename) {
            $lines = file($this->resolveTemplatePath($filename), FILE_IGNORE_NEW_LINES);

            throw_unless(is_array($lines), \RuntimeException::class, "Unable to read template {$filename}.");

            foreach ($lines as $index => $line) {
                if (!str_contains($line, $needle)) {
                    continue;
                }

                $results[] = [
                    'filename' => $filename,
                    'lineNumber' => $index + 1,
                    'line' => $line,
                ];
            }
        }

        return $results;
    }

    public function getTemplatesPath(): string
    {
        $templatesPath = FileHelper::normalizePath(Craft::$app->getPath()->getSiteTemplatesPath());

        throw_unless(is_dir($templatesPath), \RuntimeException::class, "Templates directory not found: {$templatesPath}");

        return $templatesPath;
    }

    private function resolveTemplatePath(string $filename): string
    {
        $filename = ltrim(str_replace('\\', '/', $filename), '/');

        throw_unless($filename !== '', \InvalidArgumentException::class, 'filename is required.');

        $templatesPath = $this->getTemplatesPath();
        $path = FileHelper::normalizePath($templatesPath . DIRECTORY_SEPARATOR . $filename);
        $templatesPrefix = $templatesPath . DIRECTORY_SEPARATOR;

        throw_unless(
            $path === $templatesPath || str_starts_with($path, $templatesPrefix),
            \InvalidArgumentException::class,
            'Template filename must be within the templates directory.'
        );

        throw_unless(is_file($path), \InvalidArgumentException::class, "Template {$filename} not found.");

        return $path;
    }

    private function relativeTemplatePath(string $path, string $templatesPath): string
    {
        $relativePath = substr($path, strlen($templatesPath) + 1);

        return str_replace('\\', '/', $relativePath);
    }
}
