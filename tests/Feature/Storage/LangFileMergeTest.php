<?php

declare(strict_types=1);

use Rivalex\Lingua\Enums\LinguaType;
use Rivalex\Lingua\Locales\BundledTranslationSource;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\Translation;
use Rivalex\Lingua\Storage\FileRepository;
use Rivalex\Lingua\Support\AtomicFileWriter;
use Rivalex\Lingua\Support\TranslationFileReader;

beforeEach(function (): void {
    $this->mergeDir = sys_get_temp_dir().'/lingua_merge_'.str_replace('.', '_', uniqid('', true));
    mkdir($this->mergeDir, 0777, true);
    config(['lingua.lang_dir' => $this->mergeDir]);
    Translation::query()->delete();
});

afterEach(function (): void {
    if (! is_dir($this->mergeDir)) {
        return;
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($this->mergeDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iterator as $item) {
        $item->isDir() ? rmdir($item->getRealPath()) : unlink($item->getRealPath());
    }
    rmdir($this->mergeDir);
});

function mergeRepo(string $langDir): FileRepository
{
    return new FileRepository(
        writer: new AtomicFileWriter,
        reader: new TranslationFileReader,
        langPath: $langDir,
        bundled: new BundledTranslationSource(basePath: '/dev/null/no-bundled'),
    );
}

function mergeInstallItalian(): void
{
    Language::factory()->create([
        'code' => 'it',
        'regional' => 'it_IT',
        'type' => 'Latn',
        'name' => 'Italian',
        'native' => 'Italiano',
        'direction' => 'ltr',
        'is_default' => false,
    ]);
}

it('keeps keys that exist only in the file when syncing the database to lang/', function (): void {
    file_put_contents($this->mergeDir.'/it.json', json_encode(['Only in Italian file' => 'Solo', 'Hello' => 'Ciao']));
    file_put_contents($this->mergeDir.'/en.json', json_encode(['Hello' => 'Hello']));
    mergeInstallItalian();

    Translation::syncToDatabase();

    $row = Translation::where('group', 'single')->where('key', 'Hello')->firstOrFail();
    $row->text = [...$row->text, 'it' => 'Salve'];
    $row->save();

    Translation::syncToLocal();

    expect(json_decode((string) file_get_contents($this->mergeDir.'/it.json'), true))
        ->toBe(['Only in Italian file' => 'Solo', 'Hello' => 'Salve']);
});

it('keeps the shape of a PHP key with dots when syncing the database to lang/', function (): void {
    mkdir($this->mergeDir.'/en', 0777, true);
    file_put_contents(
        $this->mergeDir.'/en/validation.php',
        '<?php return ["custom" => ["items.*.name" => ["required" => "FILE"]]];'
    );

    Translation::syncToDatabase();

    $row = Translation::where('group', 'validation')->where('key', 'custom.items.*.name.required')->firstOrFail();
    expect($row->text['en'])->toBe('FILE');

    $row->text = ['en' => 'DB'];
    $row->save();

    Translation::syncToLocal();

    $written = include $this->mergeDir.'/en/validation.php';

    expect($written)->toBe(['custom' => ['items.*.name' => ['required' => 'DB']]]);
});

it('keeps the shape of a PHP key with dots when a value is set in file mode', function (): void {
    mkdir($this->mergeDir.'/en', 0777, true);
    file_put_contents(
        $this->mergeDir.'/en/validation.php',
        '<?php return ["custom" => ["items.*.name" => ["required" => "FILE"]]];'
    );

    $repo = mergeRepo($this->mergeDir);
    $line = $repo->find('validation', 'custom.items.*.name.required', false, null);
    expect($line)->not->toBeNull();

    $repo->setValue($line, 'en', 'EDITED');

    $written = include $this->mergeDir.'/en/validation.php';

    expect($written)->toBe(['custom' => ['items.*.name' => ['required' => 'EDITED']]]);
});

it('keeps a value already present in the locale files when installing the locale in file mode', function (): void {
    mkdir($this->mergeDir.'/en', 0777, true);
    mkdir($this->mergeDir.'/it', 0777, true);
    file_put_contents($this->mergeDir.'/en/auth.php', '<?php return ["failed" => "Failed", "throttle" => "Slow down"];');
    file_put_contents($this->mergeDir.'/en.json', json_encode(['Hello' => 'Hello']));
    file_put_contents($this->mergeDir.'/it/auth.php', '<?php return ["failed" => "Personale"];');
    file_put_contents($this->mergeDir.'/it.json', json_encode(['Hello' => 'Ciao']));

    mergeRepo($this->mergeDir)->installLocale('it');

    $auth = include $this->mergeDir.'/it/auth.php';

    expect($auth)->toBe(['failed' => 'Personale', 'throttle' => ''])
        ->and(json_decode((string) file_get_contents($this->mergeDir.'/it.json'), true))->toBe(['Hello' => 'Ciao']);
});

it('runs a nested write inside the lang lock and leaves the lock file behind', function (): void {
    $repo = mergeRepo($this->mergeDir);
    $method = new ReflectionMethod($repo, 'withLangLock');

    $result = $method->invoke($repo, function () use ($repo): string {
        $line = $repo->create('messages', 'hi', LinguaType::text, 'en', 'Hello');
        $repo->setValue($line, 'en', 'Hi there');

        return 'done';
    });

    expect($result)->toBe('done')
        ->and(file_exists(storage_path('framework/lingua-lang.lock')))->toBeTrue()
        ->and((include $this->mergeDir.'/en/messages.php'))->toBe(['hi' => 'Hi there']);
});
