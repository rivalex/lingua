<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Rivalex\Lingua\Models\Translation;

/**
 * Re-resolve the loader and translator against the given language directory.
 */
function runtimeLoadingUseLangDir(string $dir): void
{
    config(['lingua.lang_dir' => $dir]);
    app()->forgetInstance('translation.loader');
    app()->forgetInstance('translator');
}

it('serves JSON strings from the single rows in database mode and keeps dotted keys whole', function (): void {
    Translation::factory()->core()->create([
        'group' => 'single',
        'key' => 'Audit sentence',
        'type' => 'text',
        'text' => ['en' => 'Audit value from DB'],
    ]);
    Translation::factory()->core()->create([
        'group' => 'single',
        'key' => 'Audit sentence. With a dot.',
        'type' => 'text',
        'text' => ['en' => 'Dotted value from DB'],
    ]);

    app()->forgetInstance('translator');

    expect(__('Audit sentence'))->toBe('Audit value from DB')
        ->and(__('Audit sentence. With a dot.'))->toBe('Dotted value from DB');
});

it('never reads the translations table in file mode', function (): void {
    $dir = sys_get_temp_dir().'/lingua-runtime-'.uniqid();
    File::ensureDirectoryExists($dir.'/en');
    File::put($dir.'/en/auditfm.php', "<?php\n\nreturn ['hello' => 'Hello from file'];\n");

    try {
        config(['lingua.storage.driver' => 'file']);
        runtimeLoadingUseLangDir($dir);

        Translation::factory()->core()->create([
            'group' => 'auditfm',
            'key' => 'hello',
            'type' => 'text',
            'text' => ['en' => 'Stale DB value'],
        ]);

        DB::enableQueryLog();
        $value = __('auditfm.hello');
        $queries = collect(DB::getQueryLog())->pluck('query')->filter(fn (string $q) => str_contains($q, 'language_lines'));
        DB::disableQueryLog();

        expect($value)->toBe('Hello from file')
            ->and($queries)->toBeEmpty();
    } finally {
        File::deleteDirectory($dir);
    }
});

it('probes a missing translations table at most once per request', function (): void {
    Schema::dropIfExists('language_lines');

    DB::enableQueryLog();
    $loader = app('translation.loader');
    $loader->load('en', 'auth');
    $loader->load('en', 'auth');
    $loader->load('en', 'auth');
    $probes = collect(DB::getQueryLog())->pluck('query')->filter(fn (string $q) => str_contains($q, 'sqlite_master'));
    DB::disableQueryLog();

    expect($probes)->toHaveCount(1);
});
