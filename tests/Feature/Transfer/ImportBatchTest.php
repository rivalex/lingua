<?php

declare(strict_types=1);

use Rivalex\Lingua\Contracts\BaseTranslationSource;
use Rivalex\Lingua\Contracts\TranslationRepository;
use Rivalex\Lingua\Contracts\WritesTranslationBatches;
use Rivalex\Lingua\Database\DatabaseRepository;
use Rivalex\Lingua\Enums\LinguaType;
use Rivalex\Lingua\Exceptions\UnsafeTranslationValue;
use Rivalex\Lingua\Models\Translation;
use Rivalex\Lingua\Storage\FileRepository;
use Rivalex\Lingua\Support\AtomicFileWriter;
use Rivalex\Lingua\Support\TranslationFileReader;
use Rivalex\Lingua\Support\TranslationLine;
use Rivalex\Lingua\Transfer\Format\CsvWriter;
use Rivalex\Lingua\Transfer\Format\FormatRegistry;
use Rivalex\Lingua\Transfer\ImportCommitService;
use Rivalex\Lingua\Transfer\ImportDiffService;
use Rivalex\Lingua\Transfer\RowMapper;
use Rivalex\Lingua\Transfer\TransferSchema;

function batchLine(string $group, string $key, array $text = []): TranslationLine
{
    return new TranslationLine(
        group: $group,
        key: $key,
        groupKey: "{$group}.{$key}",
        type: LinguaType::text,
        text: $text,
        isVendor: false,
        vendor: null,
    );
}

it('writes the updates of an import through one setValues call', function (): void {
    for ($i = 0; $i < 300; $i++) {
        // The factory draws a unique word for every definition: reset the generator, the key is overridden anyway.
        fake()->unique(true);
        Translation::factory()->core()->create(['group' => 'batch', 'key' => 'key'.$i, 'type' => LinguaType::text, 'text' => ['en' => 'Source']]);
    }

    $headers = [TransferSchema::KEY, TransferSchema::TYPE, TransferSchema::sourceHeader('en'), TransferSchema::targetHeader('it'), TransferSchema::VENDOR];
    $rows = [];
    for ($i = 0; $i < 300; $i++) {
        $rows[] = [
            TransferSchema::KEY => 'batch.key'.$i,
            TransferSchema::TYPE => 'text',
            TransferSchema::sourceHeader('en') => 'Source',
            TransferSchema::targetHeader('it') => 'Valore '.$i,
            TransferSchema::VENDOR => '',
        ];
    }
    $path = tempnam(sys_get_temp_dir(), 'lingua_batch_');
    (new CsvWriter)->write($path, $headers, $rows);

    $lines = (new DatabaseRepository(app(BaseTranslationSource::class)))->all();

    $repo = Mockery::mock(TranslationRepository::class, WritesTranslationBatches::class);
    $repo->shouldReceive('all')->andReturn($lines);
    $repo->shouldReceive('setValues')->once()->with(Mockery::on(
        fn (array $changes): bool => count($changes) === 300
            && $changes[0]['locale'] === 'it'
            && $changes[0]['value'] === 'Valore 0'
            && $changes[0]['line'] instanceof TranslationLine
    ));
    $repo->shouldNotReceive('setValue');

    $registry = new FormatRegistry;
    $mapper = new RowMapper;
    $diffService = new ImportDiffService($repo, $registry, $mapper);

    $diff = (new ImportCommitService($repo, $registry, $mapper, $diffService))->commit($path, 'csv', 'it', false);
    @unlink($path);

    expect($diff->updateCount)->toBe(300)
        ->and($diff->errorCount)->toBe(0);
});

it('writes 300 changes of one group to its lang file and rejects the whole batch on an unsafe value (file mode)', function (): void {
    $dir = sys_get_temp_dir().'/lingua_batch_'.uniqid();
    mkdir($dir.'/it', 0755, true);

    $repo = new FileRepository(new AtomicFileWriter, new TranslationFileReader, $dir, app(BaseTranslationSource::class));

    $changes = [];
    for ($i = 0; $i < 300; $i++) {
        $changes[] = ['line' => batchLine('bulk', 'key'.$i), 'locale' => 'it', 'value' => 'Valore '.$i];
    }

    $repo->setValues($changes);

    $written = include $dir.'/it/bulk.php';
    expect($written)->toHaveCount(300)
        ->and($written['key0'])->toBe('Valore 0')
        ->and($written['key299'])->toBe('Valore 299');

    $before = file_get_contents($dir.'/it/bulk.php');

    $rejected = [
        ['line' => batchLine('other', 'fine'), 'locale' => 'it', 'value' => 'Fine'],
        ['line' => batchLine('bulk', 'key0'), 'locale' => 'it', 'value' => 'Changed'],
        ['line' => batchLine('bulk', 'key1'), 'locale' => 'it', 'value' => '<script>alert(1)</script>'],
    ];

    expect(fn () => $repo->setValues($rejected))->toThrow(UnsafeTranslationValue::class)
        ->and(file_get_contents($dir.'/it/bulk.php'))->toBe($before)
        ->and(file_exists($dir.'/it/other.php'))->toBeFalse();
});

it('rolls back the first change of a batch when the second is rejected (database mode)', function (): void {
    $first = Translation::factory()->core()->create(['group' => 'batch', 'key' => 'first', 'type' => LinguaType::text, 'text' => ['en' => 'One']]);
    $second = Translation::factory()->core()->create(['group' => 'batch', 'key' => 'second', 'type' => LinguaType::text, 'text' => ['en' => 'Two']]);

    $repo = new DatabaseRepository(app(BaseTranslationSource::class));

    $changes = [
        ['line' => $repo->toLine($first), 'locale' => 'it', 'value' => 'Uno'],
        ['line' => $repo->toLine($second), 'locale' => 'it', 'value' => '<script>alert(1)</script>'],
    ];

    expect(fn () => $repo->setValues($changes))->toThrow(UnsafeTranslationValue::class)
        ->and(Translation::find($first->id)->text)->toBe(['en' => 'One'])
        ->and(Translation::find($second->id)->text)->toBe(['en' => 'Two']);

    $repo->setValues([['line' => $repo->toLine($first), 'locale' => 'it', 'value' => 'Uno']]);

    expect(Translation::find($first->id)->text)->toBe(['en' => 'One', 'it' => 'Uno']);
});
