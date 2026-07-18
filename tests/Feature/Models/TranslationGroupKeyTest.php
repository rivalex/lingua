<?php

declare(strict_types=1);

use Rivalex\Lingua\Facades\Lingua;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\Translation;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

// Regression guard for the PostgreSQL "group_key NOT NULL" seed crash:
// group_key must be populated on every save, event-independently. A previous
// fix relied on a saving() event hook — that silently never registers when
// the Eloquent event dispatcher isn't bound yet (observed under
// `artisan migrate:fresh --seed`), leaving group_key out of the INSERT.
// The withoutEvents() cases below reproduce that class of failure
// deterministically: they fail against a hook-only fix and pass only once
// Translation::save() itself guarantees the value.

test('group_key is populated on create for a core translation', function (): void {
    $key = 'failed_'.uniqid();

    $model = Translation::create([
        'group' => 'gk_core_test',
        'key' => $key,
        'text' => ['en' => 'These credentials do not match.'],
        'is_vendor' => false,
        'vendor' => null,
    ]);

    expect($model->group_key)->toBe("gk_core_test.{$key}");

    assertDatabaseHas('language_lines', [
        'group' => 'gk_core_test',
        'key' => $key,
        'group_key' => "gk_core_test.{$key}",
    ]);
});

test('group_key is populated on create for a vendor translation', function (): void {
    $key = 'save_'.uniqid();

    $model = Translation::create([
        'group' => 'gk_vendor_test',
        'key' => $key,
        'text' => ['en' => 'Save'],
        'is_vendor' => true,
        'vendor' => 'flux',
    ]);

    expect($model->group_key)->toBe("flux::gk_vendor_test.{$key}");

    assertDatabaseHas('language_lines', [
        'group' => 'gk_vendor_test',
        'key' => $key,
        'group_key' => "flux::gk_vendor_test.{$key}",
    ]);
});

test('group_key is recomputed when group or key changes on update', function (): void {
    $key = 'required_'.uniqid();

    $model = Translation::create([
        'group' => 'gk_update_test',
        'key' => $key,
        'text' => ['en' => 'This field is required.'],
        'is_vendor' => false,
        'vendor' => null,
    ]);

    expect($model->group_key)->toBe("gk_update_test.{$key}");

    $renamedKey = $key.'_renamed';
    $model->update(['key' => $renamedKey]);

    expect($model->fresh()->group_key)->toBe("gk_update_test.{$renamedKey}");

    assertDatabaseMissing('language_lines', ['group_key' => "gk_update_test.{$key}"]);
    assertDatabaseHas('language_lines', ['group_key' => "gk_update_test.{$renamedKey}"]);
});

test('group_key is populated on create even when model events are disabled', function (): void {
    $key = 'failed_'.uniqid();

    $model = Translation::withoutEvents(fn () => Translation::create([
        'group' => 'gk_no_events_test',
        'key' => $key,
        'text' => ['it' => 'x'],
        'is_vendor' => false,
        'vendor' => null,
    ]));

    expect($model->group_key)->toBe("gk_no_events_test.{$key}");

    assertDatabaseHas('language_lines', [
        'id' => $model->id,
        'group_key' => "gk_no_events_test.{$key}",
    ]);
});

test('group_key is populated on updateOrCreate even when model events are disabled', function (): void {
    $key = 'failed_'.uniqid();

    Translation::withoutEvents(fn () => Translation::updateOrCreate(
        ['group' => 'gk_no_events_uoc', 'key' => $key, 'is_vendor' => false, 'vendor' => null],
        ['type' => 'text', 'text' => ['it' => 'x']],
    ));

    assertDatabaseHas('language_lines', [
        'group' => 'gk_no_events_uoc',
        'key' => $key,
        'group_key' => "gk_no_events_uoc.{$key}",
    ]);
});

// ─── Full host scenario: bundled sync + addLanguage() end-to-end ───────────

describe('full addLanguage sync path', function () {
    beforeEach(function () {
        $suffix = str_replace('.', '_', uniqid('', true));
        $this->syncDir = sys_get_temp_dir().'/lingua_gk_sync_'.$suffix;
        $this->bundleDir = sys_get_temp_dir().'/lingua_gk_bundle_'.$suffix;
        mkdir($this->syncDir, 0777, true);
        mkdir($this->bundleDir, 0777, true);
        config(['lingua.lang_dir' => $this->syncDir]);
        config(['lingua.base_translations_path' => $this->bundleDir]);
        Translation::query()->delete();
    });

    afterEach(function () {
        foreach ([$this->syncDir, $this->bundleDir] as $dir) {
            if (! is_dir($dir)) {
                continue;
            }
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($iterator as $item) {
                $item->isDir() ? rmdir($item->getRealPath()) : unlink($item->getRealPath());
            }
            rmdir($dir);
        }
    });

    test('Lingua::addLanguage never leaves a null group_key row', function (): void {
        if (! is_dir($this->bundleDir.'/en')) {
            mkdir($this->bundleDir.'/en', 0777, true);
        }
        file_put_contents(
            $this->bundleDir.'/en/auth.php',
            '<?php return '.var_export(['failed' => 'These credentials do not match.'], true).';'
        );

        if (! is_dir($this->bundleDir.'/it')) {
            mkdir($this->bundleDir.'/it', 0777, true);
        }
        file_put_contents(
            $this->bundleDir.'/it/auth.php',
            '<?php return '.var_export(['failed' => 'Le credenziali non corrispondono.'], true).';'
        );

        Lingua::addLanguage('it');

        assertDatabaseHas('language_lines', [
            'group' => 'auth',
            'key' => 'failed',
            'group_key' => 'auth.failed',
        ]);

        expect(Language::where('code', 'it')->exists())->toBeTrue()
            ->and(Translation::whereNull('group_key')->exists())->toBeFalse()
            ->and(Translation::where('group_key', '')->exists())->toBeFalse();
    });

    test('Lingua::addLanguage leaves no null group_key row even when Translation events are disabled', function (): void {
        // Mirrors the host's exact failure mode: under `artisan migrate:fresh --seed`
        // the Eloquent event dispatcher is unbound when Translation first boots, so
        // any saving()-hook-only fix never populates group_key. withoutEvents()
        // reproduces that class of failure deterministically inside the test suite.
        if (! is_dir($this->bundleDir.'/en')) {
            mkdir($this->bundleDir.'/en', 0777, true);
        }
        file_put_contents(
            $this->bundleDir.'/en/auth.php',
            '<?php return '.var_export(['failed' => 'These credentials do not match.'], true).';'
        );

        if (! is_dir($this->bundleDir.'/it')) {
            mkdir($this->bundleDir.'/it', 0777, true);
        }
        file_put_contents(
            $this->bundleDir.'/it/auth.php',
            '<?php return '.var_export(['failed' => 'Le credenziali non corrispondono.'], true).';'
        );

        Translation::withoutEvents(fn () => Lingua::addLanguage('it'));

        assertDatabaseHas('language_lines', [
            'group' => 'auth',
            'key' => 'failed',
            'group_key' => 'auth.failed',
        ]);

        expect(Translation::whereNull('group_key')->exists())->toBeFalse()
            ->and(Translation::where('group_key', '')->exists())->toBeFalse();
    });
});
