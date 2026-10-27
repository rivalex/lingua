<?php

declare(strict_types=1);

use Rivalex\Lingua\Contracts\BaseTranslationSource;
use Rivalex\Lingua\Database\DatabaseRepository;
use Rivalex\Lingua\Enums\LinguaType;
use Rivalex\Lingua\Models\Translation;
use Rivalex\Lingua\Transfer\Enums\TransferFilter;
use Rivalex\Lingua\Transfer\Enums\TransferScope;
use Rivalex\Lingua\Transfer\ExportService;
use Rivalex\Lingua\Transfer\Format\CsvWriter;
use Rivalex\Lingua\Transfer\Format\FormatRegistry;
use Rivalex\Lingua\Transfer\ImportCommitService;
use Rivalex\Lingua\Transfer\ImportDiffService;
use Rivalex\Lingua\Transfer\RowMapper;
use Rivalex\Lingua\Transfer\SpreadsheetSupport;
use Rivalex\Lingua\Transfer\TransferSchema;

function fidelityExport(string $format, TransferScope $scope, ?string $target): string
{
    $service = new ExportService(
        new DatabaseRepository(app(BaseTranslationSource::class)),
        new FormatRegistry,
        new RowMapper,
    );

    $response = $service->export($scope, TransferFilter::all, $format, 'en', $target, true, ['en', 'it']);

    ob_start();
    $response->sendContent();
    $content = (string) ob_get_clean();

    $path = tempnam(sys_get_temp_dir(), 'lingua_fidelity_');
    file_put_contents($path, $content);

    return $path;
}

function fidelityCommit(string $path, string $format): ImportDiffService
{
    $repo = new DatabaseRepository(app(BaseTranslationSource::class));
    $registry = new FormatRegistry;
    $mapper = new RowMapper;
    $diffService = new ImportDiffService($repo, $registry, $mapper);

    (new ImportCommitService($repo, $registry, $mapper, $diffService))->commit($path, $format, 'it', false);

    return $diffService;
}

function fidelityCsv(array $rows): string
{
    $headers = [
        TransferSchema::KEY,
        TransferSchema::TYPE,
        TransferSchema::sourceHeader('en'),
        TransferSchema::targetHeader('it'),
        TransferSchema::VENDOR,
    ];

    $path = tempnam(sys_get_temp_dir(), 'lingua_fidelity_csv_');
    (new CsvWriter)->write($path, $headers, array_map(fn (array $row): array => [
        TransferSchema::KEY => $row[0],
        TransferSchema::TYPE => 'text',
        TransferSchema::sourceHeader('en') => 'Source',
        TransferSchema::targetHeader('it') => $row[1],
        TransferSchema::VENDOR => '',
    ], $rows));

    return $path;
}

it('keeps values that start with a formula character byte for byte through export and import', function (string $format): void {
    if (in_array($format, ['xlsx', 'ods'], true) && ! SpreadsheetSupport::available()) {
        $this->markTestSkipped('OpenSpout not installed');
    }

    $values = [
        'list' => '- list item',
        'phone' => '+39 02 1234',
        'formula' => '=SUM(A1)',
        'quoted' => "'=x",
    ];

    foreach ($values as $key => $value) {
        Translation::factory()->core()->create(['group' => 'fidelity', 'key' => $key, 'type' => LinguaType::text, 'text' => ['en' => 'Source', 'it' => $value]]);
    }

    $path = fidelityExport($format, TransferScope::bilingual, 'it');

    foreach (array_keys($values) as $key) {
        Translation::where('group_key', "fidelity.{$key}")->first()->setTranslation('it', 'changed');
    }

    fidelityCommit($path, $format);
    @unlink($path);

    foreach ($values as $key => $value) {
        expect(Translation::where('group_key', "fidelity.{$key}")->first()->text['it'])->toBe($value);
    }
})->with(['csv', 'xlsx', 'ods']);

it('imports a json-native export into a fresh database without skips or errors and restores the values', function (): void {
    // Only the lines created here are exported: the seeded ones have no value for the target locale.
    Translation::query()->delete();

    Translation::factory()->core()->create(['group' => 'auth', 'key' => 'failed', 'type' => LinguaType::text, 'text' => ['en' => 'Failed', 'it' => 'Fallito']]);
    Translation::factory()->core()->create(['group' => 'messages', 'key' => 'nested.title', 'type' => LinguaType::html, 'text' => ['en' => '<b>Title</b>', 'it' => '<b>Titolo</b>']]);
    Translation::factory()->core()->create(['group' => 'single', 'key' => 'Welcome back. Please log in.', 'type' => LinguaType::text, 'text' => ['en' => 'Welcome back', 'it' => 'Bentornato']]);

    $path = fidelityExport('json', TransferScope::jsonNative, null);

    Translation::query()->delete();

    $repo = new DatabaseRepository(app(BaseTranslationSource::class));
    $registry = new FormatRegistry;
    $mapper = new RowMapper;
    $diffService = new ImportDiffService($repo, $registry, $mapper);
    $commitService = new ImportCommitService($repo, $registry, $mapper, $diffService);

    $diff = $commitService->commit($path, 'json', 'it', false);
    @unlink($path);

    expect($diff->skipCount)->toBe(0)
        ->and($diff->errorCount)->toBe(0)
        ->and($diff->createCount)->toBe(3)
        ->and(Translation::where('group_key', 'auth.failed')->first()->text['it'])->toBe('Fallito')
        ->and(Translation::where('group_key', 'messages.nested.title')->first()->type)->toBe(LinguaType::html)
        ->and(Translation::where('group_key', 'messages.nested.title')->first()->text['it'])->toBe('<b>Titolo</b>')
        ->and(Translation::where('group_key', 'single.Welcome back. Please log in.')->first()->text['it'])->toBe('Bentornato');
});

it('creates a single key from a csv row whose text before the first dot has whitespace', function (): void {
    $path = fidelityCsv([['Welcome back. Please log in.', 'Bentornato. Accedi.']]);

    fidelityCommit($path, 'csv');
    @unlink($path);

    $line = Translation::where('group', 'single')->where('key', 'Welcome back. Please log in.')->first();

    expect($line)->not->toBeNull()
        ->and($line->text['it'])->toBe('Bentornato. Accedi.')
        ->and(Translation::where('group', 'Welcome back')->exists())->toBeFalse();
});

it('reports a wildcard row with its key and writes nothing', function (): void {
    $path = fidelityCsv([['auth.*', 'Boom']]);

    $repo = new DatabaseRepository(app(BaseTranslationSource::class));
    $diffService = new ImportDiffService($repo, new FormatRegistry, new RowMapper);

    $diff = $diffService->diff($path, 'csv', 'it', false);
    @unlink($path);

    expect($diff->errorCount)->toBe(1)
        ->and($diff->errors[0]['key'])->toBe('auth.*')
        ->and($diff->errors[0]['reason'])->toBe('The key contains a "*" segment or a NUL character.')
        ->and($diff->createCount)->toBe(0);
});

it('treats a tab-only cell as empty once the guard quote is removed, in the preview and in the commit', function (): void {
    Translation::factory()->core()->create(['group' => 'fidelity', 'key' => 'blank', 'type' => LinguaType::text, 'text' => ['en' => 'Source', 'it' => 'Vecchio']]);

    $path = fidelityCsv([['fidelity.blank', "\t"]]);

    $repo = new DatabaseRepository(app(BaseTranslationSource::class));
    $diff = (new ImportDiffService($repo, new FormatRegistry, new RowMapper))->diff($path, 'csv', 'it', false);

    fidelityCommit($path, 'csv');
    @unlink($path);

    expect($diff->skipCount)->toBe(1)
        ->and($diff->skipped[0]['reason'])->toBe('empty target value')
        ->and(Translation::where('group_key', 'fidelity.blank')->first()->text['it'])->toBe('Vecchio');
});
