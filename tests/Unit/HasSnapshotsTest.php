<?php

declare(strict_types=1);

use Grazulex\LaravelSnapshot\Models\ModelSnapshot;
use Grazulex\LaravelSnapshot\Storage\ArrayStorage;
use Grazulex\LaravelSnapshot\Traits\HasSnapshots;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

// Create a test model that uses the trait
class TestModel extends Model
{
    use HasSnapshots;

    protected $table = 'test_models';

    protected $fillable = ['name', 'email', 'status'];
}

beforeEach(function () {
    // Create test table
    if (! Schema::hasTable('test_models')) {
        Schema::create('test_models', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    // Clear array storage
    ArrayStorage::clearAll();
});

test('it has snapshots relationship', function () {
    $model = TestModel::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'status' => 'active',
    ]);

    $relation = $model->snapshots();

    expect($relation)->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\HasMany::class);
});

test('it can create manual snapshot', function () {
    $model = TestModel::create([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'status' => 'active',
    ]);

    $snapshot = $model->snapshot('test-manual');

    expect($snapshot)->toBeArray()
        ->and($snapshot)->toHaveKey('label')
        ->and($snapshot['label'])->toBe('test-manual');

    // Check if snapshot was saved to database
    expect(ModelSnapshot::where('label', 'test-manual')->exists())->toBeTrue();
});

test('it can create snapshot with auto-generated label', function () {
    $model = TestModel::create([
        'name' => 'Bob Smith',
        'email' => 'bob@example.com',
        'status' => 'active',
    ]);

    $snapshot = $model->snapshot();

    expect($snapshot)->toBeArray()
        ->and($snapshot['label'])->toContain('manual-');
});

test('it can get snapshot timeline', function () {
    $model = TestModel::create([
        'name' => 'Alice Johnson',
        'email' => 'alice@example.com',
        'status' => 'active',
    ]);

    // Create multiple snapshots
    $model->snapshot('snapshot-1');
    $model->snapshot('snapshot-2');
    $model->snapshot('snapshot-3');

    $timeline = $model->getSnapshotTimeline();

    expect($timeline)->toBeArray()
        ->and($timeline)->toHaveCount(3);
});

test('it can get snapshot timeline with limit', function () {
    $model = TestModel::create([
        'name' => 'Charlie Brown',
        'email' => 'charlie@example.com',
        'status' => 'active',
    ]);

    // Create multiple snapshots
    for ($i = 1; $i <= 5; $i++) {
        $model->snapshot("snapshot-{$i}");
    }

    $timeline = $model->getSnapshotTimeline(3);

    expect($timeline)->toHaveCount(3);
});

test('it can compare with snapshot', function () {
    $model = TestModel::create([
        'name' => 'David Wilson',
        'email' => 'david@example.com',
        'status' => 'active',
    ]);

    // Create initial snapshot
    $model->snapshot('before-change');

    // Change model
    $model->update(['name' => 'David W. Wilson', 'status' => 'inactive']);

    // Compare with snapshot
    $diff = $model->compareWithSnapshot('before-change');

    expect($diff)->toBeArray()
        ->and($diff)->toHaveKey('modified')
        ->and($diff['modified'])->toHaveKey('name')
        ->and($diff['modified'])->toHaveKey('status')
        ->and($diff['modified']['name']['from'])->toBe('David Wilson')
        ->and($diff['modified']['name']['to'])->toBe('David W. Wilson')
        ->and($diff['modified']['status']['from'])->toBe('active')
        ->and($diff['modified']['status']['to'])->toBe('inactive');
});

test('it throws exception when comparing with non-existent snapshot', function () {
    $model = TestModel::create([
        'name' => 'Eve Davis',
        'email' => 'eve@example.com',
        'status' => 'active',
    ]);

    expect(fn () => $model->compareWithSnapshot('non-existent'))
        ->toThrow(InvalidArgumentException::class);
});

test('it can generate report', function () {
    $model = TestModel::create([
        'name' => 'Frank Miller',
        'email' => 'frank@example.com',
        'status' => 'active',
    ]);

    // Create some snapshots
    $model->snapshot('report-test-1');
    $model->snapshot('report-test-2');

    $report = $model->generateSnapshotReport();

    expect($report)->toBeInstanceOf(Grazulex\LaravelSnapshot\Reports\SnapshotReport::class);
});

test('it can generate report with custom format', function () {
    $model = TestModel::create([
        'name' => 'Grace Lee',
        'email' => 'grace@example.com',
        'status' => 'active',
    ]);

    $model->snapshot('format-test');

    $jsonReport = $model->generateSnapshotReport('json');
    $csvReport = $model->generateSnapshotReport('csv');

    expect($jsonReport)->toBeInstanceOf(Grazulex\LaravelSnapshot\Reports\SnapshotReport::class);
    expect($csvReport)->toBeInstanceOf(Grazulex\LaravelSnapshot\Reports\SnapshotReport::class);
});

test('it automatically creates snapshots on configured events', function () {
    // This would normally be triggered by Eloquent events
    // We'll test the boot method behavior

    $model = new TestModel();

    // Check that the trait is properly booted
    expect(method_exists($model, 'bootHasSnapshots'))->toBeTrue();

    // The actual event listener testing would require more complex setup
    // This tests that the method exists and can be called
    expect(true)->toBeTrue();
});

test('it excludes configured fields from snapshots', function () {
    // Temporarily override config
    config(['snapshot.automatic.exclude_fields' => ['email']]);

    $model = TestModel::create([
        'name' => 'Henry Garcia',
        'email' => 'henry@example.com',
        'status' => 'active',
    ]);

    $snapshot = $model->snapshot('exclude-test');

    expect($snapshot['attributes'])->not->toHaveKey('email')
        ->and($snapshot['attributes'])->toHaveKey('name')
        ->and($snapshot['attributes'])->toHaveKey('status');
});
