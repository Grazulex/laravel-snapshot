<?php

declare(strict_types=1);

namespace Grazulex\LaravelSnapshot\Support;

use InvalidArgumentException;

class TemplateRenderer
{
    private string $stubsPath;

    public function __construct()
    {
        $this->stubsPath = __DIR__.'/../Console/Commands/stubs';
    }

    /**
     * Render a template with replacements.
     */
    public function render(string $templateName, array $replacements = []): string
    {
        $templatePath = $this->getTemplatePath($templateName);

        if (! file_exists($templatePath)) {
            throw new InvalidArgumentException("Template '{$templateName}' not found at {$templatePath}");
        }

        $content = file_get_contents($templatePath);

        return $this->replaceTokens($content, $replacements);
    }

    /**
     * Render multiple templates and combine them.
     */
    public function renderMultiple(array $mainTemplate, array $subTemplates = []): string
    {
        $mainContent = $this->render($mainTemplate['name'], $mainTemplate['replacements'] ?? []);

        foreach ($subTemplates as $placeholder => $templateData) {
            if (is_array($templateData)) {
                $subContent = '';
                foreach ($templateData as $item) {
                    $subContent .= $this->render($item['name'], $item['replacements'] ?? []);
                }
                $mainContent = str_replace("{{$placeholder}}", $subContent, $mainContent);
            }
        }

        return $mainContent;
    }

    /**
     * Get available templates.
     */
    public function getAvailableTemplates(): array
    {
        $templates = [];
        $files = glob($this->stubsPath.'/*.stub');

        foreach ($files as $file) {
            $templates[] = basename($file, '.stub');
        }

        return $templates;
    }

    /**
     * Check if a template exists.
     */
    public function templateExists(string $templateName): bool
    {
        return file_exists($this->getTemplatePath($templateName));
    }

    /**
     * Get the full path to a template file.
     */
    private function getTemplatePath(string $templateName): string
    {
        if (! str_ends_with($templateName, '.stub')) {
            $templateName .= '.stub';
        }

        return $this->stubsPath.'/'.$templateName;
    }

    /**
     * Replace all tokens in content with their values.
     */
    private function replaceTokens(string $content, array $replacements): string
    {
        foreach ($replacements as $token => $value) {
            $token = $this->normalizeToken($token);
            $content = str_replace("{{$token}}", (string) $value, $content);
        }

        return $content;
    }

    /**
     * Normalize token format.
     */
    private function normalizeToken(string $token): string
    {
        return mb_strtoupper(mb_trim($token, '{}'));
    }
}
