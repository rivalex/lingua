<?php

declare(strict_types=1);

use Livewire\Livewire;
use Rivalex\Lingua\Contracts\TranslationRepository;
use Rivalex\Lingua\Enums\LinguaType;
use Rivalex\Lingua\Livewire\Translation\Create;
use Rivalex\Lingua\Livewire\Translation\Update;

/**
 * Run $callback with lang/en.json holding $contents, then restore the original file.
 */
function dottedKeyWithJson(array $contents, Closure $callback): void
{
    config(['lingua.storage.driver' => 'file']);

    $path = lang_path('en.json');
    $original = file_exists($path) ? file_get_contents($path) : null;

    try {
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        file_put_contents($path, json_encode($contents, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $callback($path);
    } finally {
        $original === null ? @unlink($path) : file_put_contents($path, $original);
    }
}

it('stores a dotted single key as a literal key and leaves the shorter key a string', function (): void {
    dottedKeyWithJson(['Login' => 'Log in'], function (string $path): void {
        Livewire::test(Create::class)
            ->set('group', 'single')
            ->set('key', 'Login.x')
            ->set('textValue', 'Log in here')
            ->call('addNewTranslation')
            ->assertHasNoErrors();

        $stored = json_decode((string) file_get_contents($path), true);
        expect($stored)->toBe(['Login' => 'Log in', 'Login.x' => 'Log in here']);

        app('translator')->setLoaded([]);
        expect(__('Login'))->toBe('Log in');
    });
});

it('edits only the entry of a sentence key that ends with a dot', function (): void {
    dottedKeyWithJson(['Welcome back. Please log in.' => 'Bentornato.', 'Welcome back' => 'Ciao'], function (string $path): void {
        Livewire::test(Update::class, ['translationIdentity' => 'single|Welcome back. Please log in.|0|', 'currentLocale' => 'en'])
            ->set('textValue', 'Bentornato, accedi.')
            ->call('updateTranslation')
            ->assertHasNoErrors();

        expect(json_decode((string) file_get_contents($path), true))
            ->toBe(['Welcome back. Please log in.' => 'Bentornato, accedi.', 'Welcome back' => 'Ciao']);
    });
});

it('deletes a sentence key that contains a dot without touching its neighbours', function (): void {
    dottedKeyWithJson(['Hello. Bye.' => 'Ciao.', 'Hello' => 'Salve'], function (string $path): void {
        $repo = app(TranslationRepository::class);
        $line = $repo->find('single', 'Hello. Bye.', false, null);

        expect($line)->not->toBeNull();

        $repo->deleteKey($line);

        expect(json_decode((string) file_get_contents($path), true))->toBe(['Hello' => 'Salve']);
    });
});

it('creates a dotted single key even when a shorter key exists, through the repository', function (): void {
    dottedKeyWithJson(['Login' => 'Log in'], function (string $path): void {
        app(TranslationRepository::class)->create('single', 'Login.x', LinguaType::text, 'en', 'Here');

        expect(json_decode((string) file_get_contents($path), true))->toBe(['Login' => 'Log in', 'Login.x' => 'Here']);
    });
});

it('keeps lang/en.json an object when the single keys are 0 and 1', function (): void {
    dottedKeyWithJson([], function (string $path): void {
        $repo = app(TranslationRepository::class);
        $repo->create('single', '0', LinguaType::text, 'en', 'Zero');
        $repo->create('single', '1', LinguaType::text, 'en', 'One');

        $raw = (string) file_get_contents($path);

        expect($raw)->toStartWith('{')
            ->and(json_decode($raw, true))->toBe(['0' => 'Zero', '1' => 'One']);
    });
});
