<?php

declare(strict_types=1);

use Grazulex\LaravelSnapshot\Models\ModelSnapshot;
use Grazulex\LaravelSnapshot\Snapshot;
use Grazulex\LaravelSnapshot\Storage\ArrayStorage;
use Illuminate\Contracts\Encryption\DecryptException;
use Tests\TestModels\TestUser;

class SnapshotSensitiveUser extends TestUser
{
    protected $snapshotEncrypted = ['email'];

    protected $snapshotMasked = ['name'];
}

class SnapshotOverlappingUser extends TestUser
{
    protected $snapshotEncrypted = ['email', 'name'];

    protected $snapshotMasked = ['name'];
}

beforeEach(function () {
    config()->set('app.key', 'base64:'.base64_encode(str_repeat('k', 32)));
    app()->forgetInstance('encrypter');

    ArrayStorage::clearAll();
    Snapshot::setStorage(new ArrayStorage());
    $this->freezeTime();
});

function createSensitiveUser(string $class = SnapshotSensitiveUser::class, array $attributes = []): TestUser
{
    return $class::create(array_merge([
        'name' => 'Alice',
        'email' => 'alice@example.test',
        'password' => 'secret-hash',
    ], $attributes));
}

function latestStoredSnapshot(TestUser $user): ModelSnapshot
{
    return ModelSnapshot::query()
        ->where('model_type', get_class($user))
        ->where('model_id', $user->getKey())
        ->orderBy('id', 'desc')
        ->firstOrFail();
}

it('stores encrypted fields as ciphertext and lists them', function () {
    $user = createSensitiveUser();

    Snapshot::save($user, 'encrypted');

    $data = latestStoredSnapshot($user)->data;

    expect($data['encrypted'])->toBe(['email'])
        ->and($data['attributes']['email'])->toBeString()
        ->and($data['attributes']['email'])->not->toBe('alice@example.test')
        ->and($data['original']['email'])->not->toBe('alice@example.test')
        ->and(json_encode($data))->not->toContain('alice@example.test');

    $decrypted = Snapshot::decryptSnapshot($data);

    expect($decrypted['attributes']['email'])->toBe('alice@example.test')
        ->and($decrypted['original']['email'])->toBe('alice@example.test');
});

it('replaces masked fields with the mask value', function () {
    $user = createSensitiveUser();

    Snapshot::save($user, 'masked');

    $data = latestStoredSnapshot($user)->data;

    expect($data['masked'])->toBe(['name'])
        ->and($data['attributes']['name'])->toBe('********')
        ->and($data['original']['name'])->toBe('********');
});

it('uses the configured mask value', function () {
    config()->set('snapshot.sensitive.mask', '[redacted]');
    $user = createSensitiveUser();

    Snapshot::save($user, 'custom-mask');

    expect(latestStoredSnapshot($user)->data['attributes']['name'])->toBe('[redacted]');
});

it('reads sensitive fields from the configuration', function () {
    config()->set('snapshot.sensitive.models', [
        TestUser::class => [
            'encrypt' => ['email'],
            'mask' => ['name'],
        ],
    ]);

    $user = createSensitiveUser(TestUser::class);

    Snapshot::save($user, 'configured');

    $data = latestStoredSnapshot($user)->data;

    expect($data['encrypted'])->toBe(['email'])
        ->and($data['masked'])->toBe(['name'])
        ->and($data['attributes']['name'])->toBe('********')
        ->and(Snapshot::decryptSnapshot($data)['attributes']['email'])->toBe('alice@example.test');
});

it('masks a field declared both as encrypted and masked', function () {
    $user = createSensitiveUser(SnapshotOverlappingUser::class);

    Snapshot::save($user, 'overlap');

    $data = latestStoredSnapshot($user)->data;

    expect($data['encrypted'])->toBe(['email'])
        ->and($data['masked'])->toBe(['name'])
        ->and($data['attributes']['name'])->toBe('********');
});

it('keeps the snapshot structure unchanged without sensitive fields', function () {
    $user = createSensitiveUser(TestUser::class);

    Snapshot::save($user, 'plain');

    $data = latestStoredSnapshot($user)->data;

    expect($data)->not->toHaveKey('encrypted')
        ->and($data)->not->toHaveKey('masked')
        ->and($data['attributes']['email'])->toBe('alice@example.test');
});

it('decrypts encrypted fields and skips masked fields on restore', function () {
    $user = createSensitiveUser();
    $user->snapshot('before-change');

    $user->update(['name' => 'Bob', 'email' => 'bob@example.test']);

    expect($user->restoreFromSnapshot('before-change'))->toBeTrue();

    $user->refresh();

    expect($user->email)->toBe('alice@example.test')
        ->and($user->name)->toBe('Bob');
});

it('returns restorable attributes without masked fields', function () {
    $user = createSensitiveUser();

    $attributes = Snapshot::restorableAttributes(Snapshot::serializeModel($user));

    expect($attributes)->not->toHaveKey('name')
        ->and($attributes['email'])->toBe('alice@example.test');
});

it('throws when an encrypted field cannot be decrypted on restore', function () {
    $data = [
        'attributes' => ['email' => 'not-a-valid-payload'],
        'encrypted' => ['email'],
    ];

    Snapshot::restorableAttributes($data);
})->throws(DecryptException::class);

it('leaves undecryptable values untouched when reading a snapshot', function () {
    $data = [
        'attributes' => ['email' => 'not-a-valid-payload'],
        'encrypted' => ['email'],
    ];

    expect(Snapshot::decryptSnapshot($data)['attributes']['email'])->toBe('not-a-valid-payload');
});

it('does not report encrypted fields as changed when their value is the same', function () {
    $user = createSensitiveUser();

    Snapshot::save($user, 'first');
    Snapshot::save($user, 'second');

    expect(latestStoredSnapshot($user)->metadata['changed_fields'])->toBe([]);

    $user->update(['email' => 'new@example.test']);
    Snapshot::save($user, 'third');

    expect(latestStoredSnapshot($user)->metadata['changed_fields'])->toBe(['email']);
});

it('compares decrypted values in diffs', function () {
    $user = createSensitiveUser();
    Snapshot::save($user, 'diff-a');

    $user->update(['email' => 'changed@example.test']);
    Snapshot::save($user, 'diff-b');

    $diff = Snapshot::diff('diff-a', 'diff-b');

    expect($diff['modified']['email'])->toBe([
        'from' => 'alice@example.test',
        'to' => 'changed@example.test',
    ])->and($diff['modified'])->not->toHaveKey('name');
});

it('compares decrypted values with compareWithSnapshot', function () {
    $user = createSensitiveUser();
    $user->snapshot('compare');

    expect($user->compareWithSnapshot('compare'))->toBe([]);
});

it('records the ip address and user agent by default', function () {
    $user = createSensitiveUser(TestUser::class);

    Snapshot::save($user, 'with-request-data');

    $metadata = latestStoredSnapshot($user)->metadata;

    expect($metadata['ip_address'])->toBe(request()->ip())
        ->and($metadata['user_agent'])->toBe(request()->userAgent());
});

it('can disable ip address and user agent capture', function () {
    config()->set('snapshot.metadata.capture_ip_address', false);
    config()->set('snapshot.metadata.capture_user_agent', false);

    $user = createSensitiveUser(TestUser::class);

    Snapshot::save($user, 'without-request-data');

    $metadata = latestStoredSnapshot($user)->metadata;

    expect($metadata)->toHaveKeys(['ip_address', 'user_agent'])
        ->and($metadata['ip_address'])->toBeNull()
        ->and($metadata['user_agent'])->toBeNull();
});

it('includes hidden attributes when include_hidden is enabled', function () {
    config()->set('snapshot.automatic.exclude_fields', []);
    $user = createSensitiveUser(TestUser::class);

    expect(Snapshot::serializeModel($user)['attributes'])->not->toHaveKey('password');

    config()->set('snapshot.serialization.include_hidden', true);

    expect(Snapshot::serializeModel($user)['attributes']['password'])->toBe('secret-hash')
        ->and($user->toArray())->not->toHaveKey('password');
});
