<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Rivalex\Lingua\Facades\Lingua;
use Rivalex\Lingua\Models\Language;

// §8.11 — Facade in file-mode: reads/writes via FileRepository, languages() stays DB

function facadeFileDir(): string
{
    $dir = sys_get_temp_dir().'/facade_file_'.uniqid();
    mkdir($dir.'/en', 0755, true);
    file_put_contents($dir.'/en/messages.php', '<?php return ["hello" => "Hello", "world" => "World"];');

    return $dir;
}

function facadeFileClean(): void
{
    foreach (glob(sys_get_temp_dir().'/facade_file_*') as $d) {
        foreach (glob($d.'/*/*.php') as $f) {
            unlink($f);
        }
        foreach (glob($d.'/*') as $sub) {
            if (is_dir($sub)) {
                rmdir($sub);
            }
        }
        rmdir($d);
    }
}

test('Lingua::getTranslation reads from file in file-mode', function (): void {
    $dir = facadeFileDir();
    config(['lingua.storage.driver' => 'file', 'lingua.lang_dir' => $dir]);

    expect(Lingua::getTranslation('hello', 'en'))->toBe('Hello');
    expect(Lingua::getTranslation('nonexistent_key_xyz', 'en'))->toBe('');
})->after(fn () => facadeFileClean());

test('Lingua::getTranslations returns locale map from file in file-mode', function (): void {
    $dir = facadeFileDir();
    config(['lingua.storage.driver' => 'file', 'lingua.lang_dir' => $dir]);

    $result = Lingua::getTranslations('hello');
    expect($result)->toHaveKey('en');
    expect($result['en'])->toBe('Hello');
})->after(fn () => facadeFileClean());

test('Lingua::getTranslationByGroup returns file-mode rows', function (): void {
    $dir = facadeFileDir();
    config(['lingua.storage.driver' => 'file', 'lingua.lang_dir' => $dir]);

    $rows = Lingua::getTranslationByGroup('messages');
    expect($rows)->toHaveCount(2);
})->after(fn () => facadeFileClean());

test('Lingua::setTranslation round-trip in file-mode', function (): void {
    $dir = facadeFileDir();
    config(['lingua.storage.driver' => 'file', 'lingua.lang_dir' => $dir]);

    Lingua::setTranslation('hello', 'Hi World', 'en');

    expect(Lingua::getTranslation('hello', 'en'))->toBe('Hi World');
})->after(fn () => facadeFileClean());

test('Lingua::languages uses DB regardless of file-mode driver', function (): void {
    $dir = facadeFileDir();
    config(['lingua.storage.driver' => 'file', 'lingua.lang_dir' => $dir]);

    expect(Lingua::languages())->toBeInstanceOf(Collection::class);
    expect(Language::count())->toBeGreaterThan(0);
})->after(fn () => facadeFileClean());
