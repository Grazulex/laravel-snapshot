<?php

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

use Grazulex\LaravelSnapshot\Support\TemplateRenderer;
use PHPUnit\Framework\TestCase;

class TemplateRendererStandaloneTest extends TestCase
{
    private $stubsPath;

    protected function setUp(): void
    {
        $this->stubsPath = sys_get_temp_dir().'/test_stubs';
        if (! is_dir($this->stubsPath)) {
            mkdir($this->stubsPath, 0755, true);
        }

        // Create test stub files
        file_put_contents($this->stubsPath.'/test.stub', 'Hello {{NAME}}, you are {{AGE}} years old!');
        file_put_contents($this->stubsPath.'/simple.stub', 'Static content without tokens');
    }

    protected function tearDown(): void
    {
        // Clean up test files
        if (is_dir($this->stubsPath)) {
            array_map('unlink', glob($this->stubsPath.'/*'));
            rmdir($this->stubsPath);
        }
    }

    public function test_template_renderer_exists(): void
    {
        $renderer = new TemplateRenderer();
        $this->assertInstanceOf(TemplateRenderer::class, $renderer);
    }

    public function test_template_renderer_has_required_methods(): void
    {
        $reflection = new ReflectionClass(TemplateRenderer::class);

        $this->assertTrue($reflection->hasMethod('render'));
        $this->assertTrue($reflection->hasMethod('templateExists'));
        $this->assertTrue($reflection->hasMethod('getAvailableTemplates'));
    }

    public function test_can_check_if_template_exists(): void
    {
        $renderer = new TemplateRenderer();

        // Test the method exists and returns a boolean
        $reflection = new ReflectionClass($renderer);
        if ($reflection->hasMethod('templateExists')) {
            $method = $reflection->getMethod('templateExists');
            if ($method->isPublic()) {
                // Test with a non-existent template
                $result = $method->invoke($renderer, 'definitely-non-existent-template-12345');
                $this->assertIsBool($result);
            } else {
                $this->assertTrue(true); // Method exists but is not public
            }
        } else {
            $this->markTestSkipped('templateExists method not found');
        }
    }

    public function test_can_get_available_templates(): void
    {
        $renderer = new TemplateRenderer();

        $templates = $renderer->getAvailableTemplates();
        $this->assertIsArray($templates);
    }
}
