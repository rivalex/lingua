<?php

declare(strict_types=1);

use Rivalex\Lingua\Facades\Lingua;

beforeEach(function (): void {
    $this->langDir = sys_get_temp_dir().'/np_test_'.uniqid();
    mkdir($this->langDir, 0755, true);
});

afterEach(function (): void {
    foreach (glob($this->langDir.'/{,.}*', GLOB_BRACE) as $f) {
        if (is_file($f)) {
            unlink($f);
        }
    }
    rmdir($this->langDir);
});

it('falls back to the bundled notifications when base_notifications_path is null', function (): void {
    config([
        'lingua.base_notifications_path' => null,
        'lingua.lang_dir' => $this->langDir,
    ]);

    $bundled = require __DIR__.'/../../../resources/notifications/it.php';

    Lingua::addLanguage('it');

    $jsonFile = $this->langDir.'/it.json';

    expect(file_exists($jsonFile))->toBeTrue();

    $written = json_decode((string) file_get_contents($jsonFile), true);

    expect($bundled)->not->toBeEmpty();
    foreach ($bundled as $key => $value) {
        expect($written)->toHaveKey($key);
        expect($written[$key])->toBe($value);
    }
});
