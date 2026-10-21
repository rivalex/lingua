<?php

declare(strict_types=1);

use Rivalex\Lingua\Contracts\TranslationRepository;
use Rivalex\Lingua\Enums\LinguaType;
use Rivalex\Lingua\Storage\FileRepository;

// §8.4 — path alignment: binding resolves FileRepository at config lang_dir
test('driver=file resolves TranslationRepository to FileRepository at configured lang_dir', function (): void {
    $dir = sys_get_temp_dir().'/pa_test_'.uniqid();
    mkdir($dir, 0755, true);

    config(['lingua.storage.driver' => 'file', 'lingua.lang_dir' => $dir]);

    $repo = app(TranslationRepository::class);
    expect($repo)->toBeInstanceOf(FileRepository::class);

    $line = $repo->create('messages', 'hello', LinguaType::text, 'en', 'Hello World');

    expect(file_exists($dir.'/en/messages.php'))->toBeTrue();

    $found = $repo->findByKey('hello');
    expect($found)->not->toBeNull();
    expect($found->value('en'))->toBe('Hello World');
})->afterEach(function (): void {
    $dir = sys_get_temp_dir();
    foreach (glob($dir.'/pa_test_*') as $d) {
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
});
