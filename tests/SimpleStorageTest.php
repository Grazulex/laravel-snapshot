<?php

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

use Grazulex\LaravelSnapshot\SnapshotManager;
use Grazulex\LaravelSnapshot\Storage\ArrayStorage;
use PHPUnit\Framework\TestCase;

class SimpleStorageTest extends TestCase
{
    protected function setUp(): void
    {
        ArrayStorage::clearAll();
    }

    public function test_array_storage_basic_functionality(): void
    {
        // Clear static data
        ArrayStorage::clearAll();

        $storage = new ArrayStorage();
        $data = ['test' => 'value', 'number' => 42];

        // Test save
        $saved = $storage->save('test-label', $data);
        $this->assertEquals('value', $saved['test']);
        $this->assertEquals(42, $saved['number']);
        $this->assertArrayHasKey('label', $saved);
        $this->assertArrayHasKey('created_at', $saved);

        // Test load
        $loaded = $storage->load('test-label');
        $this->assertEquals($saved, $loaded);

        // Test non-existent
        $this->assertNull($storage->load('non-existent'));

        // Test list
        $storage->save('test2', ['data' => 2]);
        $list = $storage->list();
        $this->assertCount(2, $list);

        // Test delete
        $deleted = $storage->delete('test-label');
        $this->assertTrue($deleted);
        $this->assertNull($storage->load('test-label'));

        // Test clear
        $cleared = $storage->clear();
        $this->assertEquals(1, $cleared);
        $this->assertCount(0, $storage->list());
    }

    public function test_snapshot_manager_basics(): void
    {
        $manager = new SnapshotManager();

        $this->assertInstanceOf(SnapshotManager::class, $manager);
        $this->assertInstanceOf(ArrayStorage::class, $manager->getStorage());

        $data = ['test' => 'manager_test'];
        $saved = $manager->save('manager-test', $data);
        $loaded = $manager->load('manager-test');

        $this->assertEquals($saved, $loaded);
        $this->assertEquals('manager_test', $loaded['test']);
    }

    public function test_snapshot_class(): void
    {
        // Test diff calculation helper method
        $data1 = ['name' => 'John', 'age' => 30];
        $data2 = ['name' => 'John', 'age' => 31]; // age changed

        // Create an instance to access non-static methods if they exist
        // or call a helper method if available
        $reflection = new ReflectionClass(Grazulex\LaravelSnapshot\Snapshot::class);

        // Test that the class exists and has expected methods
        $this->assertTrue($reflection->hasMethod('diff'));
        $this->assertTrue($reflection->hasMethod('load'));
        $this->assertTrue($reflection->hasMethod('save'));
        $this->assertTrue($reflection->hasMethod('list'));
    }

    public function test_file_storage_interface(): void
    {
        // Test interface compliance without Laravel dependencies
        $interfaces = class_implements(Grazulex\LaravelSnapshot\Storage\FileStorage::class);
        $this->assertContains(Grazulex\LaravelSnapshot\Storage\StorageInterface::class, $interfaces);

        // Test ArrayStorage which doesn't need Laravel
        $storage = new ArrayStorage();
        $this->assertInstanceOf(Grazulex\LaravelSnapshot\Storage\StorageInterface::class, $storage);

        $result = $storage->save('test', ['test' => 'data']);
        $this->assertEquals('data', $result['test']);

        $loaded = $storage->load('test');
        $this->assertEquals('data', $loaded['test']);

        $this->assertTrue($storage->delete('test'));
        $this->assertEquals([], $storage->list());
        $this->assertEquals(0, $storage->clear());
    }

    public function test_snapshot_with_custom_storage(): void
    {
        $customStorage = new ArrayStorage();
        $manager = new SnapshotManager($customStorage);

        $data = ['custom' => 'test'];
        $manager->save('custom-test', $data);
        $loaded = $manager->load('custom-test');

        $this->assertEquals('test', $loaded['custom']);
    }
}
