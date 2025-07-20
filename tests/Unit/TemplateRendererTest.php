<?php

declare(strict_types=1);

use Grazulex\LaravelSnapshot\Support\TemplateRenderer;

test('it can get available templates', function () {
    $renderer = new TemplateRenderer();

    $templates = $renderer->getAvailableTemplates();

    expect($templates)->toBeArray();
});

test('it can check if template exists', function () {
    $renderer = new TemplateRenderer();

    // Create a test stub file
    $stubsPath = __DIR__.'/../../src/Console/Commands/stubs';
    $testStubPath = $stubsPath.'/test-template.stub';

    if (! is_dir($stubsPath)) {
        mkdir($stubsPath, 0755, true);
    }

    file_put_contents($testStubPath, 'Hello {{NAME}}!');

    expect($renderer->templateExists('test-template'))->toBeTrue();
    expect($renderer->templateExists('non-existent'))->toBeFalse();

    // Clean up
    unlink($testStubPath);
});

test('it can render template with replacements', function () {
    $renderer = new TemplateRenderer();
    $stubsPath = __DIR__.'/../../src/Console/Commands/stubs';
    $testStubPath = $stubsPath.'/render-test.stub';

    if (! is_dir($stubsPath)) {
        mkdir($stubsPath, 0755, true);
    }

    file_put_contents($testStubPath, 'Hello {{NAME}}, you are {{AGE}} years old!');

    $result = $renderer->render('render-test', [
        'NAME' => 'John',
        'AGE' => '30',
    ]);

    expect($result)->toBe('Hello John, you are 30 years old!');

    // Clean up
    unlink($testStubPath);
});

test('it throws exception for non-existent template', function () {
    $renderer = new TemplateRenderer();

    expect(fn () => $renderer->render('non-existent-template', []))
        ->toThrow(InvalidArgumentException::class);
});

test('it handles templates with subtemplates', function () {
    $renderer = new TemplateRenderer();
    $stubsPath = __DIR__.'/../../src/Console/Commands/stubs';

    if (! is_dir($stubsPath)) {
        mkdir($stubsPath, 0755, true);
    }

    // Create main template
    $mainStubPath = $stubsPath.'/main-template.stub';
    file_put_contents($mainStubPath, 'Title: {{TITLE}} {{ITEMS}}');

    // Create sub template
    $subStubPath = $stubsPath.'/item-template.stub';
    file_put_contents($subStubPath, 'Item: {{ITEM_NAME}} ');

    $result = $renderer->renderMultiple([
        'name' => 'main-template',
        'replacements' => ['TITLE' => 'My List'],
    ], [
        'ITEMS' => [
            ['name' => 'item-template', 'replacements' => ['ITEM_NAME' => 'First']],
            ['name' => 'item-template', 'replacements' => ['ITEM_NAME' => 'Second']],
        ],
    ]);

    expect($result)->toContain('My List');
    expect($result)->toContain('Item: First');
    expect($result)->toContain('Item: Second');

    // Clean up
    unlink($mainStubPath);
    unlink($subStubPath);
});

test('it handles empty replacements', function () {
    $renderer = new TemplateRenderer();
    $stubsPath = __DIR__.'/../../src/Console/Commands/stubs';
    $testStubPath = $stubsPath.'/empty-test.stub';

    if (! is_dir($stubsPath)) {
        mkdir($stubsPath, 0755, true);
    }

    file_put_contents($testStubPath, 'Static content without tokens');

    $result = $renderer->render('empty-test', []);

    expect($result)->toBe('Static content without tokens');

    // Clean up
    unlink($testStubPath);
});

test('it normalizes token format', function () {
    $renderer = new TemplateRenderer();
    $stubsPath = __DIR__.'/../../src/Console/Commands/stubs';
    $testStubPath = $stubsPath.'/normalize-test.stub';

    if (! is_dir($stubsPath)) {
        mkdir($stubsPath, 0755, true);
    }

    file_put_contents($testStubPath, 'Value: {{TEST_VALUE}}');

    // Test that lowercase key gets converted to uppercase for token matching
    $result = $renderer->render('normalize-test', [
        'TEST_VALUE' => 'Success',  // Already uppercase
    ]);

    expect($result)->toBe('Value: Success');

    // Clean up
    unlink($testStubPath);
});
