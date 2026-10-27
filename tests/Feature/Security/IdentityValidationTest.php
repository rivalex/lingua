<?php

declare(strict_types=1);

use Livewire\Livewire;
use Rivalex\Lingua\Contracts\BaseTranslationSource;
use Rivalex\Lingua\Database\DatabaseRepository;
use Rivalex\Lingua\Enums\LinguaType;
use Rivalex\Lingua\Livewire\Translation\Create;
use Rivalex\Lingua\Livewire\Translation\Update;
use Rivalex\Lingua\Models\Translation;
use Rivalex\Lingua\Support\TranslationIdentityRules;
use Rivalex\Lingua\Transfer\Format\CsvWriter;
use Rivalex\Lingua\Transfer\Format\FormatRegistry;
use Rivalex\Lingua\Transfer\ImportCommitService;
use Rivalex\Lingua\Transfer\ImportDiffService;
use Rivalex\Lingua\Transfer\RowMapper;
use Rivalex\Lingua\Transfer\TransferSchema;

it('Create rejects a wildcard key and leaves the language file unchanged (file mode)', function (): void {
    config(['lingua.storage.driver' => 'file']);

    $path = lang_path('en/auth.php');
    if (! is_dir(dirname($path))) {
        mkdir(dirname($path), 0777, true);
    }
    file_put_contents($path, "<?php\n\nreturn ['failed' => 'Nope'];\n");
    $before = file_get_contents($path);

    Livewire::test(Create::class)
        ->set('group', 'auth')
        ->set('key', '*')
        ->set('textValue', 'pwned')
        ->call('addNewTranslation')
        ->assertHasErrors(['key']);

    expect(file_get_contents($path))->toBe($before);
});

it('Create rejects the single wildcard key and leaves lang/en.json unchanged (file mode)', function (): void {
    config(['lingua.storage.driver' => 'file']);

    $path = lang_path('en.json');
    $original = file_exists($path) ? file_get_contents($path) : null;

    try {
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        file_put_contents($path, "{\n    \"Welcome\": \"Welcome\"\n}\n");
        $before = file_get_contents($path);

        Livewire::test(Create::class)
            ->set('group', 'single')
            ->set('key', '*')
            ->set('textValue', 'pwned')
            ->call('addNewTranslation')
            ->assertHasErrors(['key']);

        expect(file_get_contents($path))->toBe($before);
    } finally {
        $original === null ? @unlink($path) : file_put_contents($path, $original);
    }
});

it('Create rejects a single key with a wildcard segment that passes the length rule (file mode)', function (): void {
    config(['lingua.storage.driver' => 'file']);

    $path = lang_path('en.json');
    $original = file_exists($path) ? file_get_contents($path) : null;

    try {
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        file_put_contents($path, "{\n    \"Welcome\": \"Welcome\"\n}\n");
        $before = file_get_contents($path);

        Livewire::test(Create::class)
            ->set('group', 'single')
            ->set('key', 'a.*')
            ->set('textValue', 'pwned')
            ->call('addNewTranslation')
            ->assertHasErrors(['key']);

        expect(file_get_contents($path))->toBe($before);
    } finally {
        $original === null ? @unlink($path) : file_put_contents($path, $original);
    }
});

it('Create rejects a wildcard key and leaves language_lines unchanged (database mode)', function (): void {
    config(['lingua.storage.driver' => 'database']);

    Translation::create(['group' => 'idv', 'key' => 'failed', 'type' => LinguaType::text, 'text' => ['en' => 'Nope'], 'is_vendor' => false, 'vendor' => null]);
    $before = Translation::query()->orderBy('id')->get()->toArray();

    Livewire::test(Create::class)
        ->set('group', 'idv')
        ->set('key', '*')
        ->set('translationType', 'text')
        ->set('textValue', 'pwned')
        ->call('addNewTranslation')
        ->assertHasErrors(['key']);

    expect(Translation::query()->orderBy('id')->get()->toArray())->toBe($before);
});

it('Create rejects a wildcard segment that passes the length rule', function (): void {
    config(['lingua.storage.driver' => 'database']);

    Livewire::test(Create::class)
        ->set('group', 'idv')
        ->set('key', 'parent.*')
        ->set('translationType', 'text')
        ->set('textValue', 'pwned')
        ->call('addNewTranslation')
        ->assertHasErrors(['key']);

    expect(Translation::where('group', 'idv')->exists())->toBeFalse();
});

it('Create rejects a malformed group', function (): void {
    config(['lingua.storage.driver' => 'database']);

    Livewire::test(Create::class)
        ->set('group', '../x')
        ->set('key', 'valid')
        ->set('translationType', 'text')
        ->set('textValue', 'v')
        ->call('addNewTranslation')
        ->assertHasErrors(['group']);
});

it('DatabaseRepository::create throws on a traversal group', function (): void {
    $repo = new DatabaseRepository(app(BaseTranslationSource::class));

    expect(fn () => $repo->create('../x', 'k', LinguaType::text, 'en', 'v'))
        ->toThrow(InvalidArgumentException::class);
    expect(Translation::where('key', 'k')->exists())->toBeFalse();
});

it('DatabaseRepository::updateMeta throws when moving a row to a namespaced group', function (): void {
    $repo = new DatabaseRepository(app(BaseTranslationSource::class));
    $line = $repo->create('app', 'move_me', LinguaType::text, 'en', 'v');

    expect(fn () => $repo->updateMeta($line, 'acme::messages', 'move_me', LinguaType::text))
        ->toThrow(InvalidArgumentException::class);
    expect(Translation::where('group', 'app')->where('key', 'move_me')->exists())->toBeTrue();
});

it('DatabaseRepository keeps a legacy row editable and validates only the locale on setValue', function (): void {
    $repo = new DatabaseRepository(app(BaseTranslationSource::class));
    $model = Translation::create(['group' => 'legacy group', 'key' => 'a b', 'type' => LinguaType::text, 'text' => ['en' => 'v'], 'is_vendor' => false, 'vendor' => null]);
    $line = $repo->toLine($model);

    $updated = $repo->setValue($line, 'it', 'ciao');
    expect($updated->text['it'])->toBe('ciao');

    $repo->updateMeta($updated, 'legacy group', 'a b', LinguaType::markdown);
    expect($model->fresh()->type)->toBe(LinguaType::markdown);

    expect(fn () => $repo->setValue($line, '../en', 'x'))->toThrow(InvalidArgumentException::class);
});

it('Update rejects renaming only the key of a legacy group row and leaves the row unchanged (database mode)', function (): void {
    config(['lingua.storage.driver' => 'database']);

    $model = Translation::create(['group' => 'legacy group', 'key' => 'a b', 'type' => LinguaType::text, 'text' => ['en' => 'v'], 'is_vendor' => false, 'vendor' => null]);

    Livewire::test(Update::class, ['translationIdentity' => 'legacy group|a b|0|', 'currentLocale' => 'en'])
        ->set('key', 'ab')
        ->call('updateTranslation')
        ->assertHasErrors(['group']);

    expect($model->fresh()->key)->toBe('a b')
        ->and($model->fresh()->group)->toBe('legacy group');
});

it('Update rejects moving a single-group row with a free-form key to another group (database mode)', function (): void {
    config(['lingua.storage.driver' => 'database']);

    $model = Translation::create(['group' => 'single', 'key' => '5 * 3', 'type' => LinguaType::text, 'text' => ['en' => 'v'], 'is_vendor' => false, 'vendor' => null]);

    Livewire::test(Update::class, ['translationIdentity' => 'single|5 * 3|0|', 'currentLocale' => 'en'])
        ->set('group', 'app')
        ->call('updateTranslation')
        ->assertHasErrors(['key']);

    expect($model->fresh()->group)->toBe('single')
        ->and($model->fresh()->key)->toBe('5 * 3');
});

it('import preview reports an invalid identity as one error and commit writes the valid rows', function (): void {
    $repo = new DatabaseRepository(app(BaseTranslationSource::class));
    $registry = new FormatRegistry;
    $mapper = new RowMapper;
    $diffService = new ImportDiffService($repo, $registry, $mapper);
    $commitService = new ImportCommitService($repo, $registry, $mapper, $diffService);

    $headers = [
        TransferSchema::KEY,
        TransferSchema::TYPE,
        TransferSchema::sourceHeader('en'),
        TransferSchema::targetHeader('it'),
        TransferSchema::VENDOR,
    ];
    $row = fn (string $key, string $value): array => [
        TransferSchema::KEY => $key,
        TransferSchema::TYPE => 'text',
        TransferSchema::sourceHeader('en') => $value,
        TransferSchema::targetHeader('it') => $value,
        TransferSchema::VENDOR => '',
    ];

    $path = tempnam(sys_get_temp_dir(), 'lingua_identity_');
    (new CsvWriter)->write($path, $headers, [
        $row('app.one', 'Uno'),
        $row('.k', 'Bad'),
        $row('app.two', 'Due'),
    ]);

    $preview = $diffService->diff($path, 'csv', 'it', false);
    expect($preview->errorCount)->toBe(1)
        ->and($preview->createCount)->toBe(2)
        ->and($preview->errors[0]['key'])->toBe('.k');

    $commitService->commit($path, 'csv', 'it', false);
    @unlink($path);

    expect(Translation::where('group', 'app')->pluck('key')->sort()->values()->all())->toBe(['one', 'two'])
        ->and(Translation::where('key', 'k')->exists())->toBeFalse();
});

it('Create rejects a key that extends an existing key of the group and leaves language_lines unchanged (database mode)', function (): void {
    config(['lingua.storage.driver' => 'database']);

    expect(Translation::where('group', 'auth')->where('key', 'failed')->exists())->toBeTrue();
    $before = Translation::query()->orderBy('id')->get()->toArray();

    Livewire::test(Create::class)
        ->set('group', 'auth')
        ->set('key', 'failed.x')
        ->set('translationType', 'text')
        ->set('textValue', 'Nested')
        ->call('addNewTranslation')
        ->assertHasErrors(['key']);

    expect(Translation::query()->orderBy('id')->get()->toArray())->toBe($before)
        ->and(__('auth.failed'))->toBeString();
});

it('Create rejects a new group that differs from an existing one only by case (database mode)', function (): void {
    config(['lingua.storage.driver' => 'database']);

    expect(Translation::where('group', 'auth')->exists())->toBeTrue();

    Livewire::test(Create::class)
        ->set('group', 'Auth')
        ->set('key', 'other')
        ->set('translationType', 'text')
        ->set('textValue', 'v')
        ->call('addNewTranslation')
        ->assertHasErrors(['group']);

    expect(Translation::where('group', 'Auth')->exists())->toBeFalse();
});

it('Create rejects a key containing a pipe (database mode)', function (): void {
    config(['lingua.storage.driver' => 'database']);

    Livewire::test(Create::class)
        ->set('group', 'idv')
        ->set('key', 'a|b')
        ->set('translationType', 'text')
        ->set('textValue', 'v')
        ->call('addNewTranslation')
        ->assertHasErrors(['key']);

    expect(Translation::where('group', 'idv')->exists())->toBeFalse();
});

it('Update rejects renaming a key under an existing key of the group and keeps the row unchanged (database mode)', function (): void {
    config(['lingua.storage.driver' => 'database']);

    Translation::create(['group' => 'idv', 'key' => 'failed', 'type' => LinguaType::text, 'text' => ['en' => 'Nope'], 'is_vendor' => false, 'vendor' => null]);
    $model = Translation::create(['group' => 'idv', 'key' => 'other', 'type' => LinguaType::text, 'text' => ['en' => 'v'], 'is_vendor' => false, 'vendor' => null]);

    Livewire::test(Update::class, ['translationIdentity' => 'idv|other|0|', 'currentLocale' => 'en'])
        ->set('key', 'failed.x')
        ->call('updateTranslation')
        ->assertHasErrors(['key']);

    expect($model->fresh()->key)->toBe('other');
});

it('DatabaseRepository::create and updateMeta throw on a prefix collision or a case-only group', function (): void {
    $repo = new DatabaseRepository(app(BaseTranslationSource::class));
    expect(Translation::where('group', 'auth')->where('key', 'failed')->exists())->toBeTrue();
    $line = $repo->create('auth', 'other', LinguaType::text, 'en', 'v');

    expect(fn () => $repo->create('auth', 'failed.x', LinguaType::text, 'en', 'v'))->toThrow(InvalidArgumentException::class)
        ->and(fn () => $repo->create('Auth', 'k', LinguaType::text, 'en', 'v'))->toThrow(InvalidArgumentException::class)
        ->and(fn () => $repo->updateMeta($line, 'auth', 'failed.x', LinguaType::text))->toThrow(InvalidArgumentException::class)
        ->and(fn () => $repo->updateMeta($line, 'Auth', 'other', LinguaType::text))->toThrow(InvalidArgumentException::class);

    // The row's own current key is not a conflict, even when the new key is its prefix.
    $leaf = $repo->create('tree', 'trunk.leaf', LinguaType::text, 'en', 'v');
    expect($repo->updateMeta($leaf, 'tree', 'trunk', LinguaType::text)->key)->toBe('trunk');

    $renamed = $repo->updateMeta($line, 'auth', 'other', LinguaType::markdown);
    expect($renamed->type)->toBe(LinguaType::markdown)
        ->and(TranslationIdentityRules::existingKeys($repo, 'auth', false, null))->toContain('failed', 'other');
});

it('import preview and commit reject a key that collides with a key created earlier in the same file', function (): void {
    $repo = new DatabaseRepository(app(BaseTranslationSource::class));
    $registry = new FormatRegistry;
    $mapper = new RowMapper;
    $diffService = new ImportDiffService($repo, $registry, $mapper);
    $commitService = new ImportCommitService($repo, $registry, $mapper, $diffService);

    expect(Translation::where('group', 'auth')->where('key', 'failed')->exists())->toBeTrue();

    $headers = [
        TransferSchema::KEY,
        TransferSchema::TYPE,
        TransferSchema::sourceHeader('en'),
        TransferSchema::targetHeader('it'),
        TransferSchema::VENDOR,
    ];
    $row = fn (string $key, string $value): array => [
        TransferSchema::KEY => $key,
        TransferSchema::TYPE => 'text',
        TransferSchema::sourceHeader('en') => $value,
        TransferSchema::targetHeader('it') => $value,
        TransferSchema::VENDOR => '',
    ];

    $path = tempnam(sys_get_temp_dir(), 'lingua_identity_');
    (new CsvWriter)->write($path, $headers, [
        $row('imp.parent', 'Uno'),
        $row('imp.parent.child', 'Due'),
        $row('auth.failed.x', 'Tre'),
        $row('Auth.other', 'Quattro'),
        $row('imp.sibling', 'Cinque'),
    ]);

    $preview = $diffService->diff($path, 'csv', 'it', false);
    expect($preview->errorCount)->toBe(3)
        ->and($preview->createCount)->toBe(2);

    $commitService->commit($path, 'csv', 'it', false);
    @unlink($path);

    expect(Translation::where('group', 'imp')->pluck('key')->sort()->values()->all())->toBe(['parent', 'sibling'])
        ->and(Translation::where('group', 'auth')->where('key', 'like', 'failed.%')->exists())->toBeFalse()
        ->and(Translation::where('group', 'Auth')->exists())->toBeFalse();
});

it('import reports row 3 when it extends the key created by row 2 and the commit writes rows 1-2 without throwing', function (): void {
    $repo = new DatabaseRepository(app(BaseTranslationSource::class));
    $registry = new FormatRegistry;
    $mapper = new RowMapper;
    $diffService = new ImportDiffService($repo, $registry, $mapper);
    $commitService = new ImportCommitService($repo, $registry, $mapper, $diffService);

    $headers = [
        TransferSchema::KEY,
        TransferSchema::TYPE,
        TransferSchema::sourceHeader('en'),
        TransferSchema::targetHeader('it'),
        TransferSchema::VENDOR,
    ];
    $row = fn (string $key, string $value): array => [
        TransferSchema::KEY => $key,
        TransferSchema::TYPE => 'text',
        TransferSchema::sourceHeader('en') => $value,
        TransferSchema::targetHeader('it') => $value,
        TransferSchema::VENDOR => '',
    ];

    $path = tempnam(sys_get_temp_dir(), 'lingua_identity_');
    (new CsvWriter)->write($path, $headers, [
        $row('msg.first', 'Uno'),
        $row('msg.failed', 'Due'),
        $row('msg.failed.x', 'Tre'),
    ]);

    $preview = $diffService->diff($path, 'csv', 'it', false);
    expect($preview->errorCount)->toBe(1)
        ->and($preview->createCount)->toBe(2)
        ->and($preview->errors[0]['key'])->toBe('msg.failed.x');

    $commitService->commit($path, 'csv', 'it', false);
    @unlink($path);

    expect(Translation::where('group', 'msg')->pluck('key')->sort()->values()->all())->toBe(['failed', 'first']);
});

it('import reports a repeated new key as a row error and commits it once', function (): void {
    $repo = new DatabaseRepository(app(BaseTranslationSource::class));
    $registry = new FormatRegistry;
    $mapper = new RowMapper;
    $diffService = new ImportDiffService($repo, $registry, $mapper);
    $commitService = new ImportCommitService($repo, $registry, $mapper, $diffService);

    $headers = [
        TransferSchema::KEY,
        TransferSchema::TYPE,
        TransferSchema::sourceHeader('en'),
        TransferSchema::targetHeader('it'),
        TransferSchema::VENDOR,
    ];
    $row = fn (string $key, string $value): array => [
        TransferSchema::KEY => $key,
        TransferSchema::TYPE => 'text',
        TransferSchema::sourceHeader('en') => $value,
        TransferSchema::targetHeader('it') => $value,
        TransferSchema::VENDOR => '',
    ];

    $path = tempnam(sys_get_temp_dir(), 'lingua_identity_');
    (new CsvWriter)->write($path, $headers, [
        $row('msg.same', 'Uno'),
        $row('msg.same', 'Due'),
        $row('msg.same', 'Tre'),
    ]);

    $preview = $diffService->diff($path, 'csv', 'it', false);
    expect($preview->errorCount)->toBe(2)
        ->and($preview->createCount)->toBe(1)
        ->and($preview->errors[0]['reason'])->toBe('duplicate key in this file');

    $commitService->commit($path, 'csv', 'it', false);
    @unlink($path);

    expect(Translation::where('group', 'msg')->where('key', 'same')->count())->toBe(1);
});
