<?php

declare(strict_types=1);

use Grazulex\LaravelSnapshot\Snapshot;
use Grazulex\LaravelSnapshot\Storage\ArrayStorage;
use Grazulex\LaravelSnapshot\Traits\HasSnapshots;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PartialRestoreItem extends Model
{
    use HasSnapshots;

    protected $table = 'partial_restore_items';

    protected $fillable = ['name', 'email', 'status'];
}

class PartialRestoreMaskedItem extends PartialRestoreItem
{
    protected $snapshotMasked = ['email'];
}

beforeEach(function () {
    if (! Schema::hasTable('partial_restore_items')) {
        Schema::create('partial_restore_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('status')->nullable();
            $table->string('internal_note')->nullable();
            $table->timestamps();
        });
    }

    ArrayStorage::clearAll();
    Snapshot::setStorage(new ArrayStorage());
});

function createRestoreItem(string $class = PartialRestoreItem::class): PartialRestoreItem
{
    $item = new $class();
    $item->forceFill([
        'name' => 'Original',
        'email' => 'original@example.test',
        'status' => 'draft',
        'internal_note' => 'note-a',
    ])->save();

    return $item;
}

function changeRestoreItem(PartialRestoreItem $item): void
{
    $item->forceFill([
        'name' => 'Changed',
        'email' => 'changed@example.test',
        'status' => 'published',
        'internal_note' => 'note-b',
    ])->save();
}

it('still restores every fillable attribute without options', function () {
    $item = createRestoreItem();
    $item->snapshot('before');
    changeRestoreItem($item);

    expect($item->restoreFromSnapshot('before'))->toBeTrue();

    $item->refresh();

    expect($item->name)->toBe('Original')
        ->and($item->email)->toBe('original@example.test')
        ->and($item->status)->toBe('draft')
        ->and($item->internal_note)->toBe('note-b');
});

it('restores only the listed attributes', function () {
    $item = createRestoreItem();
    $item->snapshot('before');
    changeRestoreItem($item);

    expect($item->restoreFromSnapshot('before', ['only' => ['name']]))->toBeTrue();

    $item->refresh();

    expect($item->name)->toBe('Original')
        ->and($item->email)->toBe('changed@example.test')
        ->and($item->status)->toBe('published');

    $report = $item->getLastSnapshotRestoreReport();

    expect($report['restored'])->toBe(['name'])
        ->and($report['ignored']['email'])->toBe('filtered')
        ->and($report['ignored']['status'])->toBe('filtered')
        ->and($report['mode'])->toBe('update')
        ->and($report['dry_run'])->toBeFalse();
});

it('restores every attribute except the listed ones', function () {
    $item = createRestoreItem();
    $item->snapshot('before');
    changeRestoreItem($item);

    $item->restoreFromSnapshot('before', ['except' => ['email']]);

    $item->refresh();

    expect($item->name)->toBe('Original')
        ->and($item->status)->toBe('draft')
        ->and($item->email)->toBe('changed@example.test')
        ->and($item->getLastSnapshotRestoreReport()['ignored']['email'])->toBe('filtered');
});

it('reports attributes that are not fillable', function () {
    $item = createRestoreItem();
    $item->snapshot('before');
    changeRestoreItem($item);

    $item->restoreFromSnapshot('before');

    $report = $item->getLastSnapshotRestoreReport();

    expect($report['ignored']['internal_note'])->toBe('not_fillable')
        ->and($report['ignored']['id'])->toBe('not_fillable')
        ->and($report['restored'])->toEqualCanonicalizing(['name', 'email', 'status']);
});

it('reports masked attributes as ignored', function () {
    $item = createRestoreItem(PartialRestoreMaskedItem::class);
    $item->snapshot('before');
    changeRestoreItem($item);

    $item->restoreFromSnapshot('before');

    $item->refresh();

    expect($item->email)->toBe('changed@example.test')
        ->and($item->getLastSnapshotRestoreReport()['ignored']['email'])->toBe('masked');
});

it('does not change the model on a dry run', function () {
    $item = createRestoreItem();
    $item->snapshot('before');
    changeRestoreItem($item);

    expect($item->restoreFromSnapshot('before', ['dry_run' => true, 'only' => ['name', 'status']]))->toBeTrue();

    expect($item->name)->toBe('Changed')
        ->and($item->isDirty())->toBeFalse()
        ->and($item->fresh()->name)->toBe('Changed');

    $report = $item->getLastSnapshotRestoreReport();

    expect($report['dry_run'])->toBeTrue()
        ->and($report['restored'])->toBe(['name', 'status']);
});

it('restores a snapshot as a new model', function () {
    $item = createRestoreItem();
    $item->snapshot('before');
    changeRestoreItem($item);

    $copy = $item->restoreAsNew('before');

    expect($copy)->toBeInstanceOf(PartialRestoreItem::class)
        ->and($copy->exists)->toBeTrue()
        ->and($copy->getKey())->not->toBe($item->getKey())
        ->and($copy->name)->toBe('Original')
        ->and($copy->email)->toBe('original@example.test')
        ->and($copy->status)->toBe('draft')
        ->and($copy->internal_note)->toBeNull()
        ->and(PartialRestoreItem::query()->count())->toBe(2)
        ->and($item->fresh()->name)->toBe('Changed');

    $report = $copy->getLastSnapshotRestoreReport();

    expect($report['mode'])->toBe('copy')
        ->and($report['ignored']['id'])->toBe('primary_key')
        ->and($report['ignored']['internal_note'])->toBe('not_fillable')
        ->and($item->getLastSnapshotRestoreReport())->toBe($report);
});

it('restores a partial copy with only', function () {
    $item = createRestoreItem();
    $item->snapshot('before');

    $copy = $item->restoreAsNew('before', ['only' => ['name', 'email']]);

    expect($copy->exists)->toBeTrue()
        ->and($copy->name)->toBe('Original')
        ->and($copy->fresh()->status)->toBeNull();
});

it('returns an unsaved copy on a dry run', function () {
    $item = createRestoreItem();
    $item->snapshot('before');

    $copy = $item->restoreAsNew('before', ['dry_run' => true]);

    expect($copy->exists)->toBeFalse()
        ->and($copy->name)->toBe('Original')
        ->and(PartialRestoreItem::query()->count())->toBe(1)
        ->and($copy->getLastSnapshotRestoreReport()['dry_run'])->toBeTrue();
});

it('throws on an unknown restore option', function () {
    $item = createRestoreItem();
    $item->snapshot('before');

    $item->restoreFromSnapshot('before', ['onyl' => ['name']]);
})->throws(InvalidArgumentException::class, 'Unknown restore option(s): onyl');

it('throws when the option only is not an array', function () {
    $item = createRestoreItem();
    $item->snapshot('before');

    $item->restoreFromSnapshot('before', ['only' => 'name']);
})->throws(InvalidArgumentException::class);

it('throws when restoring a missing snapshot as a copy', function () {
    $item = createRestoreItem();

    $item->restoreAsNew('missing');
})->throws(InvalidArgumentException::class, 'not found');

it('has no restore report before any restore', function () {
    expect(createRestoreItem()->getLastSnapshotRestoreReport())->toBeNull();
});
