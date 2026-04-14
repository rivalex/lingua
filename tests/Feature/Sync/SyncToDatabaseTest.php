<?php

declare(strict_types=1);

use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\Translation;

// ─── Per-test isolated lang directory ──────────────────────────────────────

beforeEach(function () {
    $this->syncDir = sys_get_temp_dir().'/lingua_sync_'.str_replace('.', '_', uniqid('', true));
    mkdir($this->syncDir, 0777, true);
    config(['lingua.lang_dir' => $this->syncDir]);
    Translation::query()->delete();
});

afterEach(function () {
    if (! is_dir($this->syncDir)) {
        return;
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($this->syncDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iterator as $item) {
        $item->isDir() ? rmdir($item->getRealPath()) : unlink($item->getRealPath());
    }
    rmdir($this->syncDir);
});

// ─── Default locale ─────────────────────────────────────────────────────────

it('imports all keys from default locale PHP group files', function () {
    mkdir($this->syncDir.'/en', 0777, true);
    file_put_contents(
        $this->syncDir.'/en/greetings.php',
        '<?php return ["hello" => "Hello", "bye" => "Goodbye"];'
    );

    Translation::syncToDatabase();

    $hello = Translation::where('group', 'greetings')->where('key', 'hello')->first();
    $bye = Translation::where('group', 'greetings')->where('key', 'bye')->first();

    expect($hello)->not->toBeNull()
        ->and($hello->text['en'])->toBe('Hello')
        ->and($bye)->not->toBeNull()
        ->and($bye->text['en'])->toBe('Goodbye');
});

it('imports all keys from default locale JSON file', function () {
    file_put_contents(
        $this->syncDir.'/en.json',
        json_encode(['Confirm' => 'Confirm', 'Cancel' => 'Cancel'])
    );

    Translation::syncToDatabase();

    $confirm = Translation::where('group', 'single')->where('key', 'Confirm')->first();

    expect($confirm)->not->toBeNull()
        ->and($confirm->text['en'])->toBe('Confirm');
});

it('imports empty string values for default locale keys', function () {
    mkdir($this->syncDir.'/en', 0777, true);
    file_put_contents(
        $this->syncDir.'/en/ui.php',
        '<?php return ["empty_key" => ""];'
    );

    Translation::syncToDatabase();

    $row = Translation::where('group', 'ui')->where('key', 'empty_key')->first();

    expect($row)->not->toBeNull()
        ->and($row->text)->toHaveKey('en')
        ->and($row->text['en'])->toBe('');
});

it('does not delete existing DB records when their file is removed', function () {
    Translation::create([
        'group' => 'preserved',
        'key' => 'still_here',
        'type' => 'text',
        'text' => ['en' => 'Preserved value'],
        'is_vendor' => false,
        'vendor' => null,
    ]);

    // syncDir has no files — existing DB record must survive.
    Translation::syncToDatabase();

    $row = Translation::where('group', 'preserved')->where('key', 'still_here')->first();

    expect($row)->not->toBeNull()
        ->and($row->text['en'])->toBe('Preserved value');
});

// ─── Non-default locale key filtering ───────────────────────────────────────

it('skips orphan keys in non-default locales that are not present in the default locale', function () {
    mkdir($this->syncDir.'/en', 0777, true);
    mkdir($this->syncDir.'/it', 0777, true);
    file_put_contents($this->syncDir.'/en/ui.php', '<?php return ["submit" => "Submit"];');
    file_put_contents($this->syncDir.'/it/ui.php', '<?php return ["submit" => "Invia", "orphan" => "Solo italiano"];');

    Language::factory()->create([
        'code' => 'it',
        'regional' => 'it_IT',
        'type' => 'locale',
        'name' => 'Italian',
        'native' => 'Italiano',
        'direction' => 'ltr',
        'is_default' => false,
        'sort' => 2,
    ]);

    Translation::syncToDatabase();

    $orphan = Translation::where('group', 'ui')->where('key', 'orphan')->first();

    expect($orphan)->toBeNull();
});

it('imports non-default locale keys that exist in the default locale', function () {
    mkdir($this->syncDir.'/en', 0777, true);
    mkdir($this->syncDir.'/it', 0777, true);
    file_put_contents($this->syncDir.'/en/ui.php', '<?php return ["submit" => "Submit"];');
    file_put_contents($this->syncDir.'/it/ui.php', '<?php return ["submit" => "Invia"];');

    Language::factory()->create([
        'code' => 'it',
        'regional' => 'it_IT',
        'type' => 'locale',
        'name' => 'Italian',
        'native' => 'Italiano',
        'direction' => 'ltr',
        'is_default' => false,
        'sort' => 2,
    ]);

    Translation::syncToDatabase();

    $submit = Translation::where('group', 'ui')->where('key', 'submit')->first();

    expect($submit)->not->toBeNull()
        ->and($submit->text)->toHaveKeys(['en', 'it'])
        ->and($submit->text['en'])->toBe('Submit')
        ->and($submit->text['it'])->toBe('Invia');
});

it('imports empty string values from non-default locales for keys that exist in the default locale', function () {
    mkdir($this->syncDir.'/en', 0777, true);
    mkdir($this->syncDir.'/it', 0777, true);
    file_put_contents($this->syncDir.'/en/ui.php', '<?php return ["submit" => "Submit"];');
    file_put_contents($this->syncDir.'/it/ui.php', '<?php return ["submit" => ""];');

    Language::factory()->create([
        'code' => 'it',
        'regional' => 'it_IT',
        'type' => 'locale',
        'name' => 'Italian',
        'native' => 'Italiano',
        'direction' => 'ltr',
        'is_default' => false,
        'sort' => 2,
    ]);

    Translation::syncToDatabase();

    $submit = Translation::where('group', 'ui')->where('key', 'submit')->first();

    expect($submit)->not->toBeNull()
        ->and($submit->text)->toHaveKey('it')
        ->and($submit->text['it'])->toBe('');
});

it('merges non-default locale value into the existing default locale row', function () {
    mkdir($this->syncDir.'/en', 0777, true);
    mkdir($this->syncDir.'/it', 0777, true);
    file_put_contents($this->syncDir.'/en/ui.php', '<?php return ["title" => "Title"];');
    file_put_contents($this->syncDir.'/it/ui.php', '<?php return ["title" => "Titolo"];');

    Translation::syncToDatabase();

    $row = Translation::where('group', 'ui')->where('key', 'title')->first();

    // Single DB row holds both locales in the text JSON column.
    expect(Translation::where('group', 'ui')->where('key', 'title')->count())->toBe(1)
        ->and($row->text)->toHaveKeys(['en', 'it'])
        ->and($row->text['it'])->toBe('Titolo');
});

// ─── Vendor key filtering ────────────────────────────────────────────────────

it('skips vendor keys when no Language record exists for that locale', function () {
    mkdir($this->syncDir.'/en', 0777, true);
    mkdir($this->syncDir.'/vendor/my-pkg/it', 0777, true);
    file_put_contents($this->syncDir.'/en/ui.php', '<?php return ["key" => "Value"];');
    file_put_contents($this->syncDir.'/vendor/my-pkg/it/pkg.php', '<?php return ["vendor_key" => "Valore vendor"];');

    // No Language record for 'it' — vendor keys must be silently skipped.
    Translation::syncToDatabase();

    $vendorRow = Translation::where('group', 'pkg')
        ->where('key', 'vendor_key')
        ->where('is_vendor', true)
        ->first();

    expect($vendorRow)->toBeNull();
});

it('imports vendor keys when a Language record exists in DB before sync', function () {
    mkdir($this->syncDir.'/en', 0777, true);
    mkdir($this->syncDir.'/vendor/my-pkg/it', 0777, true);
    file_put_contents($this->syncDir.'/en/ui.php', '<?php return ["key" => "Value"];');
    file_put_contents($this->syncDir.'/vendor/my-pkg/it/pkg.php', '<?php return ["vendor_key" => "Valore vendor"];');

    Language::factory()->create([
        'code' => 'it',
        'regional' => 'it_IT',
        'type' => 'locale',
        'name' => 'Italian',
        'native' => 'Italiano',
        'direction' => 'ltr',
        'is_default' => false,
        'sort' => 2,
    ]);

    Translation::syncToDatabase();

    $vendorRow = Translation::where('group', 'pkg')
        ->where('key', 'vendor_key')
        ->where('is_vendor', true)
        ->first();

    expect($vendorRow)->not->toBeNull()
        ->and($vendorRow->text)->toHaveKey('it')
        ->and($vendorRow->text['it'])->toBe('Valore vendor');
});

it('imports vendor keys when the Language record was created during the same sync (sub-pass A)', function () {
    // 'it' has both non-vendor keys (qualifying it for sub-pass A) and vendor keys.
    // Sub-pass A creates the Language record; sub-pass B then finds it in $installedCodes.
    mkdir($this->syncDir.'/en', 0777, true);
    mkdir($this->syncDir.'/it', 0777, true);
    mkdir($this->syncDir.'/vendor/my-pkg/it', 0777, true);
    file_put_contents($this->syncDir.'/en/ui.php', '<?php return ["key" => "Value"];');
    file_put_contents($this->syncDir.'/it/ui.php', '<?php return ["key" => "Valore"];');
    file_put_contents($this->syncDir.'/vendor/my-pkg/it/pkg.php', '<?php return ["vendor_key" => "Valore vendor"];');

    // No pre-existing Language record for 'it'.
    Translation::syncToDatabase();

    $vendorRow = Translation::where('group', 'pkg')
        ->where('key', 'vendor_key')
        ->where('is_vendor', true)
        ->first();

    expect($vendorRow)->not->toBeNull()
        ->and($vendorRow->text['it'])->toBe('Valore vendor');
});

// ─── Two-pass ordering ────────────────────────────────────────────────────────

it('treats non-default locale keys as orphans when no default locale files exist', function () {
    // Only 'it' files exist. Without default locale keys, the $defaultKeys set is
    // empty and all 'it' keys are considered orphans — verifying Pass 1 runs first.
    mkdir($this->syncDir.'/it', 0777, true);
    file_put_contents($this->syncDir.'/it/ui.php', '<?php return ["hello" => "Ciao"];');

    Translation::syncToDatabase();

    $row = Translation::where('group', 'ui')->where('key', 'hello')->first();

    expect($row)->toBeNull();
});

it('correctly imports both locales when default and non-default files are present', function () {
    mkdir($this->syncDir.'/en', 0777, true);
    mkdir($this->syncDir.'/it', 0777, true);
    file_put_contents($this->syncDir.'/en/ui.php', '<?php return ["greeting" => "Hello"];');
    file_put_contents($this->syncDir.'/it/ui.php', '<?php return ["greeting" => "Ciao"];');

    Translation::syncToDatabase();

    $row = Translation::where('group', 'ui')->where('key', 'greeting')->first();

    expect($row)->not->toBeNull()
        ->and($row->text)->toHaveKeys(['en', 'it']);
});

it('creates a Language record for default locale during sync', function () {
    mkdir($this->syncDir.'/en', 0777, true);
    file_put_contents($this->syncDir.'/en/ui.php', '<?php return ["key" => "Value"];');

    // Remove the en Language record to verify sync recreates it.
    Language::where('code', 'en')->delete();

    Translation::syncToDatabase();

    expect(Language::where('code', 'en')->exists())->toBeTrue();
});

// ─── Edge cases ───────────────────────────────────────────────────────────────

it('completes without throwing when the lang directory is empty', function () {
    expect(fn () => Translation::syncToDatabase())->not->toThrow(Throwable::class);
});

it('completes without throwing when a JSON file is malformed', function () {
    file_put_contents($this->syncDir.'/en.json', '{invalid json');

    expect(fn () => Translation::syncToDatabase())->not->toThrow(Throwable::class);
});

it('completes without throwing when a PHP group file returns a non-array', function () {
    mkdir($this->syncDir.'/en', 0777, true);
    file_put_contents($this->syncDir.'/en/broken.php', '<?php return "not an array";');

    expect(fn () => Translation::syncToDatabase())->not->toThrow(Throwable::class);
});

it('re-syncing the same files does not duplicate Translation rows', function () {
    mkdir($this->syncDir.'/en', 0777, true);
    file_put_contents($this->syncDir.'/en/ui.php', '<?php return ["submit" => "Submit"];');

    Translation::syncToDatabase();
    $countAfterFirst = Translation::where('group', 'ui')->where('key', 'submit')->count();

    Translation::syncToDatabase();
    $countAfterSecond = Translation::where('group', 'ui')->where('key', 'submit')->count();

    expect($countAfterFirst)->toBe(1)
        ->and($countAfterSecond)->toBe(1);
});
