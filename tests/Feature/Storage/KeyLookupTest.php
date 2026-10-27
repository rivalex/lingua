<?php

declare(strict_types=1);

use Rivalex\Lingua\Facades\Lingua;
use Rivalex\Lingua\Models\Translation;

/**
 * Seed the given [group => [key => value]] map in the driver under test and return a cleanup callback.
 *
 * @param  array<string, array<string, string>>  $groups
 */
function keyLookupSeed(string $driver, array $groups): Closure
{
    config(['lingua.storage.driver' => $driver]);

    if ($driver === 'database') {
        foreach ($groups as $group => $entries) {
            foreach ($entries as $key => $value) {
                Translation::factory()->core()->create([
                    'group' => $group,
                    'key' => $key,
                    'type' => 'text',
                    'text' => ['en' => $value],
                ]);
            }
        }

        return fn () => null;
    }

    $dir = sys_get_temp_dir().'/key_lookup_'.uniqid();
    mkdir($dir.'/en', 0755, true);
    config(['lingua.lang_dir' => $dir]);

    foreach ($groups as $group => $entries) {
        if ($group === 'single') {
            file_put_contents($dir.'/en.json', json_encode($entries));

            continue;
        }

        file_put_contents($dir.'/en/'.$group.'.php', '<?php return '.var_export($entries, true).';');
    }

    return function () use ($dir): void {
        foreach (glob($dir.'/*/*.php') ?: [] as $file) {
            unlink($file);
        }
        foreach (glob($dir.'/*.json') ?: [] as $file) {
            unlink($file);
        }
        foreach (glob($dir.'/*', GLOB_ONLYDIR) ?: [] as $sub) {
            rmdir($sub);
        }
        rmdir($dir);
    };
}

dataset('lookup drivers', ['database', 'file']);

it('resolves a full key and refuses an ambiguous bare key', function (string $driver): void {
    $cleanup = keyLookupSeed($driver, [
        'pages' => ['title' => 'Pages title'],
        'emails' => ['title' => 'Emails title'],
    ]);

    try {
        expect(Lingua::getTranslation('pages.title', 'en'))->toBe('Pages title')
            ->and(Lingua::getTranslation('emails.title', 'en'))->toBe('Emails title')
            ->and(Lingua::getTranslation('title', 'en'))->toBe('')
            ->and(Lingua::getTranslations('title'))->toBe([]);

        Lingua::setTranslation('title', 'Overwritten', 'en');
        Lingua::forgetTranslation('title', 'en');

        expect(Lingua::getTranslation('pages.title', 'en'))->toBe('Pages title')
            ->and(Lingua::getTranslation('emails.title', 'en'))->toBe('Emails title');
    } finally {
        $cleanup();
    }
})->with('lookup drivers');

it('accepts a bare key when exactly one line has it', function (string $driver): void {
    $cleanup = keyLookupSeed($driver, [
        'shop' => ['title' => 'Shop title'],
    ]);

    try {
        expect(Lingua::getTranslation('title', 'en'))->toBe('Shop title')
            ->and(Lingua::getTranslation('shop.title', 'en'))->toBe('Shop title');
    } finally {
        $cleanup();
    }
})->with('lookup drivers');

it('finds a single sentence by its key', function (string $driver): void {
    $cleanup = keyLookupSeed($driver, [
        'single' => ['Hello. Bye.' => 'Ciao. Addio.'],
    ]);

    try {
        expect(Lingua::getTranslation('single.Hello. Bye.', 'en'))->toBe('Ciao. Addio.')
            ->and(Lingua::getTranslation('Hello. Bye.', 'en'))->toBe('Ciao. Addio.');
    } finally {
        $cleanup();
    }
})->with('lookup drivers');
