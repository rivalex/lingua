<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

// The Tailwind step of lingua:install is exercised against a temporary base path,
// so no file is written inside the repository or the Testbench skeleton.

const LINGUA_SOURCE_LINE = "@source '../../vendor/rivalex/lingua/resources/views';";

beforeEach(function () {
    $this->originalBasePath = $this->app->basePath();
    $this->tmpBase = sys_get_temp_dir().'/lingua-tw-'.uniqid();
    File::ensureDirectoryExists($this->tmpBase.'/resources/css');
    $this->cssPath = $this->tmpBase.'/resources/css/app.css';
    $this->app->setBasePath($this->tmpBase);
});

afterEach(function () {
    $this->app->setBasePath($this->originalBasePath);
    File::deleteDirectory($this->tmpBase);
});

function runInstallerWithMigrationsDeclined(object $test)
{
    return $test->artisan('lingua:install')
        ->expectsQuestion('Translation storage driver?', 'database')
        ->expectsConfirmation('Would you like to run the migrations now?', 'no');
}

it('inserts the source line after the last @source', function () {
    file_put_contents($this->cssPath, "@import 'tailwindcss';\n@source '../views';\n@source '../js';\n\n@theme {}\n");

    runInstallerWithMigrationsDeclined($this)
        ->expectsConfirmation('Add the Lingua @source line to resources/css/app.css?', 'yes')
        ->expectsOutputToContain('npm run build')
        ->assertSuccessful();

    expect(file_get_contents($this->cssPath))
        ->toBe("@import 'tailwindcss';\n@source '../views';\n@source '../js';\n".LINGUA_SOURCE_LINE."\n\n@theme {}\n");
});

it('inserts the source line after the last @import when there is no @source', function () {
    file_put_contents($this->cssPath, "@import 'tailwindcss';\n@import '../../vendor/livewire/flux/dist/flux.css';\n\n@theme {}\n");

    runInstallerWithMigrationsDeclined($this)
        ->expectsConfirmation('Add the Lingua @source line to resources/css/app.css?', 'yes')
        ->assertSuccessful();

    expect(file_get_contents($this->cssPath))
        ->toBe("@import 'tailwindcss';\n@import '../../vendor/livewire/flux/dist/flux.css';\n".LINGUA_SOURCE_LINE."\n\n@theme {}\n");
});

it('inserts the source line at the top when there is no @source and no @import', function () {
    file_put_contents($this->cssPath, "@theme {}\n\nbody { margin: 0; }\n");

    runInstallerWithMigrationsDeclined($this)
        ->expectsConfirmation('Add the Lingua @source line to resources/css/app.css?', 'yes')
        ->assertSuccessful();

    expect(file_get_contents($this->cssPath))
        ->toBe(LINGUA_SOURCE_LINE."\n@theme {}\n\nbody { margin: 0; }\n");
});

it('is idempotent on a second run', function () {
    file_put_contents($this->cssPath, "@import 'tailwindcss';\n");

    runInstallerWithMigrationsDeclined($this)
        ->expectsConfirmation('Add the Lingua @source line to resources/css/app.css?', 'yes')
        ->assertSuccessful();

    $afterFirst = file_get_contents($this->cssPath);

    // No confirmation is expected on the second run: a question would fail the test.
    runInstallerWithMigrationsDeclined($this)
        ->expectsOutputToContain('already scans the Lingua views')
        ->assertSuccessful();

    expect(file_get_contents($this->cssPath))->toBe($afterFirst)
        ->and(substr_count($afterFirst, 'vendor/rivalex/lingua/resources/views'))->toBe(1);
});

it('leaves the file untouched and prints the instructions with --no-interaction', function () {
    $original = "@import 'tailwindcss';\n";
    file_put_contents($this->cssPath, $original);

    $this->artisan('lingua:install', ['--no-interaction' => true])
        ->expectsQuestion('Translation storage driver?', 'database')
        ->expectsConfirmation('Would you like to run the migrations now?', 'no')
        ->expectsOutputToContain(LINGUA_SOURCE_LINE)
        ->expectsOutputToContain('Styling')
        ->expectsOutputToContain('npm run build')
        ->assertSuccessful();

    expect(file_get_contents($this->cssPath))->toBe($original);
});

it('prints the instructions and creates nothing when the stylesheet is missing', function () {
    runInstallerWithMigrationsDeclined($this)
        ->expectsOutputToContain(LINGUA_SOURCE_LINE)
        ->expectsOutputToContain('Styling')
        ->expectsOutputToContain('npm run build')
        ->assertSuccessful();

    expect(file_exists($this->cssPath))->toBeFalse();
});

it('leaves the file byte-identical when the confirmation is declined', function () {
    $original = "@import 'tailwindcss';\r\n@source '../views';\r\n";
    file_put_contents($this->cssPath, $original);

    runInstallerWithMigrationsDeclined($this)
        ->expectsConfirmation('Add the Lingua @source line to resources/css/app.css?', 'no')
        ->expectsOutputToContain(LINGUA_SOURCE_LINE)
        ->assertSuccessful();

    expect(file_get_contents($this->cssPath))->toBe($original);
});
