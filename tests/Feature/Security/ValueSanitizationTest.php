<?php

declare(strict_types=1);

use Illuminate\Pagination\LengthAwarePaginator;
use Rivalex\Lingua\Contracts\BaseTranslationSource;
use Rivalex\Lingua\Database\DatabaseRepository;
use Rivalex\Lingua\Enums\LinguaType;
use Rivalex\Lingua\Exceptions\UnsafeTranslationValue;
use Rivalex\Lingua\Models\Translation;
use Rivalex\Lingua\Storage\FileRepository;
use Rivalex\Lingua\Support\AtomicFileWriter;
use Rivalex\Lingua\Support\TranslationFileReader;
use Rivalex\Lingua\Transfer\Format\CsvWriter;
use Rivalex\Lingua\Transfer\Format\FormatRegistry;
use Rivalex\Lingua\Transfer\ImportCommitService;
use Rivalex\Lingua\Transfer\ImportDiffService;
use Rivalex\Lingua\Transfer\RowMapper;
use Rivalex\Lingua\Transfer\TransferSchema;

it('rejects an unsafe pagination value and renders no event handler (database mode)', function (): void {
    $repo = new DatabaseRepository(app(BaseTranslationSource::class));
    $model = Translation::where('group', 'pagination')->where('key', 'previous')->firstOrFail();

    expect(fn () => $repo->setValue($repo->toLine($model), 'en', '<img src=x onerror=alert(1)>'))
        ->toThrow(UnsafeTranslationValue::class);

    expect($model->fresh()->text['en'])->not->toContain('onerror');

    $html = (new LengthAwarePaginator(range(1, 30), 30, 10, 2))->links('pagination::tailwind')->toHtml();

    expect($html)->not->toContain('onerror');
});

it('rejects an unsafe value in file mode', function (): void {
    $dir = sys_get_temp_dir().'/lingua_sanitize_'.uniqid();
    mkdir($dir.'/en', 0755, true);
    file_put_contents($dir.'/en/pagination.php', "<?php\n\nreturn ['previous' => 'Previous'];\n");

    $repo = new FileRepository(new AtomicFileWriter, new TranslationFileReader, $dir, app(BaseTranslationSource::class));
    $line = $repo->find('pagination', 'previous', false, null);

    expect(fn () => $repo->setValue($line, 'en', '<img src=x onerror=alert(1)>'))
        ->toThrow(UnsafeTranslationValue::class);
    expect(fn () => $repo->create('pagination', 'next', LinguaType::text, 'en', '<IMG SRC=x OnError=1>'))
        ->toThrow(UnsafeTranslationValue::class);

    $contents = file_get_contents($dir.'/en/pagination.php');

    expect(strtolower($contents))->not->toContain('onerror')
        ->and($contents)->toContain('previous');

    array_map('unlink', glob($dir.'/en/*'));
    rmdir($dir.'/en');
    rmdir($dir);
});

dataset('unchanged values', [
    'entity' => ['&laquo; Previous'],
    'less than' => ['a < b'],
    'placeholder comparison' => [':attribute <= :max'],
    'allowed markup with placeholder' => ['Hello <strong>:name</strong>'],
    'lt followed by a space' => ['< img src=x onerror=1>'],
]);

it('leaves a value without markup byte for byte unchanged', function (string $value): void {
    $repo = new DatabaseRepository(app(BaseTranslationSource::class));
    $model = Translation::factory()->core()->create(['group' => 'app', 'key' => 'plain', 'type' => LinguaType::text, 'text' => ['en' => 'x']]);

    $repo->setValue($repo->toLine($model), 'en', $value);

    expect($model->fresh()->text['en'])->toBe($value);

    $created = $repo->create('app', 'plain_created', LinguaType::markdown, 'en', $value);

    expect($created->text['en'])->toBe($value);
})->with('unchanged values');

it('leaves a value without markup byte for byte unchanged in file mode', function (string $value): void {
    $dir = sys_get_temp_dir().'/lingua_unchanged_'.uniqid();
    mkdir($dir.'/en', 0755, true);

    try {
        file_put_contents($dir.'/en/app.php', "<?php\n\nreturn ['title' => 'Title'];\n");

        $repo = new FileRepository(new AtomicFileWriter, new TranslationFileReader, $dir, app(BaseTranslationSource::class));
        $line = $repo->find('app', 'title', false, null);

        $updated = $repo->setValue($line, 'en', $value);
        $created = $repo->create('app', 'body', LinguaType::markdown, 'en', $value);

        expect($updated->text['en'])->toBe($value)
            ->and($created->text['en'])->toBe($value)
            ->and($repo->find('app', 'title', false, null)->text['en'])->toBe($value)
            ->and($repo->find('app', 'body', false, null)->text['en'])->toBe($value);
    } finally {
        array_map('unlink', glob($dir.'/en/*') ?: []);
        @rmdir($dir.'/en');
        @rmdir($dir);
    }
})->with('unchanged values');

it('rejects a mixed-case tag in a text value', function (): void {
    $repo = new DatabaseRepository(app(BaseTranslationSource::class));
    $model = Translation::factory()->core()->create(['group' => 'app', 'key' => 'mixed', 'type' => LinguaType::text, 'text' => ['en' => 'x']]);

    expect(fn () => $repo->setValue($repo->toLine($model), 'en', '<IMG SRC=x OnError=1>'))
        ->toThrow(UnsafeTranslationValue::class);

    expect(strtolower($model->fresh()->text['en']))->not->toContain('onerror');
});

it('sanitizes an html value and keeps the allowed markup', function (): void {
    $repo = new DatabaseRepository(app(BaseTranslationSource::class));

    $created = $repo->create('app', 'html_key', LinguaType::html, 'en', '<p>hi</p><img src=x onerror=alert(1)>');

    expect($created->text['en'])->not->toContain('onerror')
        ->and($created->text['en'])->toContain('<p>hi</p>');
});

it('skips an unsafe value in the import', function (): void {
    $repo = new DatabaseRepository(app(BaseTranslationSource::class));
    $registry = new FormatRegistry;
    $mapper = new RowMapper;
    $diffService = new ImportDiffService($repo, $registry, $mapper);
    $commitService = new ImportCommitService($repo, $registry, $mapper, $diffService);

    $payload = '<img src=x onerror=alert(1)>';
    $headers = [
        TransferSchema::KEY,
        TransferSchema::TYPE,
        TransferSchema::sourceHeader('en'),
        TransferSchema::targetHeader('it'),
        TransferSchema::VENDOR,
    ];

    $path = tempnam(sys_get_temp_dir(), 'lingua_sanitize_');
    (new CsvWriter)->write($path, $headers, [[
        TransferSchema::KEY => 'app.imported',
        TransferSchema::TYPE => 'text',
        TransferSchema::sourceHeader('en') => $payload,
        TransferSchema::targetHeader('it') => $payload,
        TransferSchema::VENDOR => '',
    ]]);

    $commitService->commit($path, 'csv', 'it', false);
    @unlink($path);

    expect(Translation::where('group', 'app')->where('key', 'imported')->first())->toBeNull();
});

it('rejects an unsafe value edited on a lingua vendor row', function (): void {
    $repo = new DatabaseRepository(app(BaseTranslationSource::class));
    $model = Translation::factory()->vendor('lingua')->create([
        'group' => 'messages',
        'key' => 'title',
        'type' => LinguaType::text,
        'text' => ['en' => 'Title'],
    ]);

    expect(fn () => $repo->setValue($repo->toLine($model), 'en', '<img src=x onerror=1>'))
        ->toThrow(UnsafeTranslationValue::class);

    expect($model->fresh()->text['en'])->not->toContain('onerror');
});
