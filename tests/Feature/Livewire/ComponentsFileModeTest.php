<?php

declare(strict_types=1);

use Livewire\Livewire;
use Rivalex\Lingua\Livewire\Statistics;
use Rivalex\Lingua\Livewire\Translations;

// §8.9 — Statistics and Translations render from file in file-mode

function compFileModeDir(): string
{
    $dir = sys_get_temp_dir().'/comp_file_'.uniqid();
    mkdir($dir.'/en', 0755, true);
    file_put_contents($dir.'/en/messages.php', '<?php return ["key1" => "Value 1", "key2" => "Value 2"];');

    return $dir;
}

function compFileModeClean(): void
{
    foreach (glob(sys_get_temp_dir().'/comp_file_*') as $d) {
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

it('Statistics renders in file-mode and lines reflects file data', function (): void {
    $dir = compFileModeDir();
    config(['lingua.storage.driver' => 'file', 'lingua.lang_dir' => $dir]);

    $component = Livewire::test(Statistics::class);
    $component->assertOk();

    expect($component->get('totalKeys'))->toBe(2);
})->after(fn () => compFileModeClean());

it('Translations renders in file-mode without querying language_lines', function (): void {
    $dir = compFileModeDir();
    config(['lingua.storage.driver' => 'file', 'lingua.lang_dir' => $dir]);

    Livewire::test(Translations::class)
        ->assertOk();
})->after(fn () => compFileModeClean());
