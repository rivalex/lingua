<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Rivalex\Lingua\Contracts\BaseTranslationSource;
use Rivalex\Lingua\Database\DatabaseRepository;
use Rivalex\Lingua\Enums\LinguaType;
use Rivalex\Lingua\Livewire\Language\Row;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\Translation;
use Rivalex\Lingua\Storage\FileRepository;
use Rivalex\Lingua\Support\AtomicFileWriter;
use Rivalex\Lingua\Support\RequestMemo;
use Rivalex\Lingua\Support\TranslationFileReader;

it('stores the sanitized values of every locale when a row is switched to html', function (): void {
    $repo = new DatabaseRepository(app(BaseTranslationSource::class));

    $row = Translation::factory()->core()->create([
        'group' => 'legacy',
        'key' => 'bold',
        'type' => LinguaType::text,
        'text' => ['en' => 'plain'],
    ]);

    // A legacy row: the value was stored as text, bypassing every write path.
    DB::table('language_lines')->where('id', $row->id)->update([
        'text' => json_encode(['en' => 'plain', 'it' => '<b onclick=x>y</b>']),
    ]);

    $line = $repo->find('legacy', 'bold', false, null);
    $updated = $repo->updateMeta($line, 'legacy', 'bold', LinguaType::html);

    expect($updated->type)->toBe(LinguaType::html)
        ->and($updated->text['it'])->toBe('<b>y</b>')
        ->and($updated->text['en'])->toBe('plain')
        ->and(Translation::find($row->id)->text['it'])->toBe('<b>y</b>');
});

it('keeps both locales when two writes start from the same stale line', function (): void {
    $repo = new DatabaseRepository(app(BaseTranslationSource::class));

    Translation::factory()->core()->create([
        'group' => 'concurrent',
        'key' => 'greeting',
        'type' => LinguaType::text,
        'text' => ['en' => 'Hello'],
    ]);

    $stale = $repo->find('concurrent', 'greeting', false, null);

    $repo->setValue($stale, 'en', 'Hi');
    $repo->setValue($stale, 'it', 'Ciao');

    $stored = Translation::where('group_key', 'concurrent.greeting')->first()->text;

    expect($stored)->toBe(['en' => 'Hi', 'it' => 'Ciao']);
});

it('runs the counts scan once per request however many rows ask for it', function (): void {
    Language::factory()->create(['code' => 'it', 'is_default' => false]);
    Language::factory()->create(['code' => 'fr', 'is_default' => false]);

    $scans = function (): int {
        return collect(DB::getQueryLog())
            ->filter(fn (array $query) => str_starts_with($query['query'], 'select "text" from "language_lines"'))
            ->count();
    };

    DB::enableQueryLog();
    Livewire::test(Row::class, ['languageId' => Language::where('code', 'it')->value('id')]);
    $single = $scans();

    // Start the two-row measurement from a clean memo and an empty log.
    app(RequestMemo::class)->forget('lingua.translation-counts');
    DB::flushQueryLog();

    Livewire::test(Row::class, ['languageId' => Language::where('code', 'it')->value('id')]);
    Livewire::test(Row::class, ['languageId' => Language::where('code', 'fr')->value('id')]);
    $double = $scans();
    DB::disableQueryLog();

    expect($single)->toBeGreaterThan(0)
        ->and($double)->toBe($single);
});

it('counts again after a translation is written', function (): void {
    $before = Translation::translationCounts()['total'];

    Translation::factory()->core()->create(['group' => 'memo', 'key' => 'fresh', 'type' => 'text', 'text' => ['en' => 'x']]);

    expect(Translation::translationCounts()['total'])->toBe($before + 1);
});

it('lists the groups of every locale in file mode', function (): void {
    $dir = sys_get_temp_dir().'/repo_groups_'.uniqid();
    mkdir($dir.'/en', 0755, true);
    mkdir($dir.'/it', 0755, true);
    file_put_contents($dir.'/en/messages.php', '<?php return ["hi" => "Hi"];');
    file_put_contents($dir.'/it/onlyit.php', '<?php return ["ciao" => "Ciao"];');

    try {
        $repo = new FileRepository(
            writer: new AtomicFileWriter,
            reader: new TranslationFileReader,
            langPath: $dir,
            bundled: app(BaseTranslationSource::class),
        );

        expect($repo->groups())->toBe(['messages', 'onlyit']);
    } finally {
        unlink($dir.'/en/messages.php');
        unlink($dir.'/it/onlyit.php');
        rmdir($dir.'/en');
        rmdir($dir.'/it');
        rmdir($dir);
    }
});
