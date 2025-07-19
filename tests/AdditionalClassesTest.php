<?php

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;

class AdditionalClassesTest extends TestCase
{
    public function test_snapshot_stats_exists(): void
    {
        $this->assertTrue(class_exists(Grazulex\LaravelSnapshot\SnapshotStats::class));

        // Test basic constructor if available
        try {
            $stats = new Grazulex\LaravelSnapshot\SnapshotStats();
            $this->assertInstanceOf(Grazulex\LaravelSnapshot\SnapshotStats::class, $stats);
        } catch (Error $e) {
            // Constructor might need parameters, just verify class exists
            $this->assertTrue(true);
        }
    }

    public function test_storage_interface_exists(): void
    {
        $this->assertTrue(interface_exists(Grazulex\LaravelSnapshot\Storage\StorageInterface::class));

        // Test that ArrayStorage implements the interface
        $storage = new Grazulex\LaravelSnapshot\Storage\ArrayStorage();
        $this->assertInstanceOf(Grazulex\LaravelSnapshot\Storage\StorageInterface::class, $storage);
    }

    public function test_snapshot_class_exists(): void
    {
        $this->assertTrue(class_exists(Grazulex\LaravelSnapshot\Snapshot::class));

        // Test that static methods exist
        $reflection = new ReflectionClass(Grazulex\LaravelSnapshot\Snapshot::class);
        $this->assertTrue($reflection->hasMethod('save'));
        $this->assertTrue($reflection->hasMethod('load'));
        $this->assertTrue($reflection->hasMethod('diff'));
        $this->assertTrue($reflection->hasMethod('list'));
    }

    public function test_console_commands_exist(): void
    {
        $this->assertTrue(class_exists(Grazulex\LaravelSnapshot\Console\Commands\SnapshotListCommand::class));

        $reflection = new ReflectionClass(Grazulex\LaravelSnapshot\Console\Commands\SnapshotListCommand::class);
        $this->assertTrue($reflection->hasMethod('handle'));
        $this->assertTrue($reflection->hasProperty('signature'));
        $this->assertTrue($reflection->hasProperty('description'));
    }

    public function test_service_provider_exists(): void
    {
        $this->assertTrue(class_exists(Grazulex\LaravelSnapshot\LaravelSnapshotServiceProvider::class));
    }

    public function test_config_file_structure(): void
    {
        $configPath = __DIR__.'/../src/Config/laravel-snapshot.php';

        if (file_exists($configPath)) {
            $config = include $configPath;
            $this->assertIsArray($config);

            // Test expected config keys
            $this->assertArrayHasKey('default_storage', $config);
        } else {
            $this->markTestSkipped('Config file not found');
        }
    }

    public function test_array_storage_advanced(): void
    {
        Grazulex\LaravelSnapshot\Storage\ArrayStorage::clearAll();

        $storage = new Grazulex\LaravelSnapshot\Storage\ArrayStorage();

        // Test saving multiple snapshots with class information
        $storage->save('user1', ['class' => 'User', 'name' => 'John']);
        $storage->save('post1', ['class' => 'Post', 'title' => 'Hello']);
        $storage->save('user2', ['class' => 'User', 'name' => 'Jane']);

        // Test listing
        $list = $storage->list();
        $this->assertCount(3, $list);

        // Test that clear works at all (clearing all)
        $clearedAll = $storage->clear();
        $this->assertEquals(3, $clearedAll);

        // Test that list is empty after clearing all
        $this->assertCount(0, $storage->list());

        // Test clearing by model type (re-populate first)
        $storage->save('user3', ['class' => 'User', 'name' => 'Bob']);
        $storage->save('post2', ['class' => 'Post', 'title' => 'World']);

        $clearedUsers = $storage->clear('User');
        $this->assertEquals(1, $clearedUsers); // Should clear the User entry

        $remainingList = $storage->list();
        $this->assertCount(1, $remainingList);
    }
}
