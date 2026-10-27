<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\ServiceProvider;
use Rivalex\Lingua\LinguaServiceProvider;
use Rivalex\Lingua\Support\MigrationPublisher;

// The publish tags and their destinations are a contract with every host that
// already published Lingua's resources: they must not change.

it('publishes the config under lingua-config', function (): void {
    expect(array_values(ServiceProvider::pathsToPublish(LinguaServiceProvider::class, 'lingua-config')))
        ->toBe([config_path('lingua.php')]);
});

it('publishes the translations under lingua-translations', function (): void {
    expect(array_values(ServiceProvider::pathsToPublish(LinguaServiceProvider::class, 'lingua-translations')))
        ->toBe([lang_path('vendor/lingua')]);
});

it('publishes the views under lingua-views', function (): void {
    expect(array_values(ServiceProvider::pathsToPublish(LinguaServiceProvider::class, 'lingua-views')))
        ->toBe([base_path('resources/views/vendor/lingua')]);
});

it('publishes the three migrations under lingua-migrations with timestamped names', function (): void {
    $targets = array_map('basename', array_values(ServiceProvider::pathsToPublish(LinguaServiceProvider::class, 'lingua-migrations')));

    expect($targets)->toHaveCount(3);

    foreach (['create_language_lines_table', 'create_languages_table', 'create_lingua_settings_table'] as $i => $basename) {
        expect($targets[$i])->toMatch('/^\d{4}_\d{2}_\d{2}_\d{6}_'.$basename.'\.php$/');
    }
});

it('reuses an already published migration as the publish target', function (): void {
    $dir = sys_get_temp_dir().'/lingua-publish-'.uniqid();
    mkdir($dir);
    touch($dir.'/2024_01_01_000000_create_languages_table.php');

    try {
        expect(MigrationPublisher::targetPath('create_languages_table', 0, $dir))
            ->toBe($dir.DIRECTORY_SEPARATOR.'2024_01_01_000000_create_languages_table.php')
            ->and(basename(MigrationPublisher::targetPath('create_language_lines_table', 0, $dir)))
            ->toMatch('/^\d{4}_\d{2}_\d{2}_\d{6}_create_language_lines_table\.php$/');
    } finally {
        array_map('unlink', glob($dir.'/*'));
        rmdir($dir);
    }
});

it('registers the lingua:install command', function (): void {
    expect(Artisan::all())->toHaveKey('lingua:install');
});
