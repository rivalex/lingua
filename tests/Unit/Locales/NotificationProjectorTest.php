<?php

declare(strict_types=1);

use Rivalex\Lingua\Locales\NotificationProjector;

// ── Helpers ────────────────────────────────────────────────────────────────────

function makeProjector(string $notificationsPath, string $langPath): NotificationProjector
{
    return new NotificationProjector(
        notificationsPath: $notificationsPath,
        langPath: $langPath,
    );
}

function makeTempDirs(): array
{
    $base = sys_get_temp_dir().'/lingua_projector_test_'.uniqid();
    $notificationsPath = $base.'/notifications';
    $langPath = $base.'/lang';
    mkdir($notificationsPath, 0755, true);
    mkdir($langPath, 0755, true);

    return [$notificationsPath, $langPath, $base];
}

function writeNotificationFile(string $dir, string $locale, array $data): void
{
    $export = "<?php\n\nreturn ".var_export($data, true).";\n";
    file_put_contents($dir.'/'.$locale.'.php', $export);
}

function readJson(string $path): array
{
    if (! file_exists($path)) {
        return [];
    }

    return json_decode(file_get_contents($path), true) ?? [];
}

function cleanDir(string $path): void
{
    if (! is_dir($path)) {
        return;
    }
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );
    foreach ($files as $f) {
        $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
    }
    rmdir($path);
}

// ── Tests ──────────────────────────────────────────────────────────────────────

test('project creates lang/{locale}.json with source EN keys translated', function (): void {
    [$notificationsPath, $langPath, $base] = makeTempDirs();

    writeNotificationFile($notificationsPath, 'it', [
        'Reset Password' => 'Ripristina Password',
        'Verify Email Address' => 'Verifica Indirizzo Email',
    ]);

    makeProjector($notificationsPath, $langPath)->project('it');

    $json = readJson($langPath.'/it.json');

    expect($json)
        ->toHaveKey('Reset Password', 'Ripristina Password')
        ->toHaveKey('Verify Email Address', 'Verifica Indirizzo Email');

    cleanDir($base);
});

test('project is idempotent — running twice produces identical file', function (): void {
    [$notificationsPath, $langPath, $base] = makeTempDirs();

    writeNotificationFile($notificationsPath, 'it', [
        'Reset Password' => 'Ripristina Password',
    ]);

    $projector = makeProjector($notificationsPath, $langPath);
    $projector->project('it');
    $after1 = readJson($langPath.'/it.json');

    $projector->project('it');
    $after2 = readJson($langPath.'/it.json');

    expect($after1)->toBe($after2);

    cleanDir($base);
});

test('project is non-destructive — existing user key is never overwritten', function (): void {
    [$notificationsPath, $langPath, $base] = makeTempDirs();

    // Pre-existing user translation for 'Reset Password'
    file_put_contents($langPath.'/it.json', json_encode([
        'Reset Password' => 'Utente personalizzato',
        'My Custom Key' => 'Valore utente',
    ]));

    writeNotificationFile($notificationsPath, 'it', [
        'Reset Password' => 'Ripristina Password',
        'Verify Email Address' => 'Verifica Indirizzo Email',
    ]);

    makeProjector($notificationsPath, $langPath)->project('it');

    $json = readJson($langPath.'/it.json');

    // User key preserved
    expect($json['Reset Password'])->toBe('Utente personalizzato');
    // New key added
    expect($json['Verify Email Address'])->toBe('Verifica Indirizzo Email');
    // Unrelated user key preserved
    expect($json['My Custom Key'])->toBe('Valore utente');

    cleanDir($base);
});

test('project updates .lingua-managed.json sidecar with projected keys', function (): void {
    [$notificationsPath, $langPath, $base] = makeTempDirs();

    writeNotificationFile($notificationsPath, 'it', [
        'Reset Password' => 'Ripristina Password',
        'Verify Email Address' => 'Verifica Indirizzo Email',
    ]);

    makeProjector($notificationsPath, $langPath)->project('it');

    $manifest = readJson($langPath.'/.lingua-managed.json');

    expect($manifest)->toHaveKey('it');
    expect($manifest['it'])
        ->toContain('Reset Password')
        ->toContain('Verify Email Address');

    cleanDir($base);
});

test('unproject removes only lingua-managed keys, preserves user keys', function (): void {
    [$notificationsPath, $langPath, $base] = makeTempDirs();

    file_put_contents($langPath.'/it.json', json_encode([
        'Reset Password' => 'Ripristina Password',
        'Verify Email Address' => 'Verifica Indirizzo Email',
        'My Custom Key' => 'Valore utente',
    ]));

    // Manually seed the sidecar manifest
    file_put_contents($langPath.'/.lingua-managed.json', json_encode([
        'it' => ['Reset Password', 'Verify Email Address'],
    ]));

    makeProjector($notificationsPath, $langPath)->unproject('it');

    $json = readJson($langPath.'/it.json');

    expect($json)->not->toHaveKey('Reset Password');
    expect($json)->not->toHaveKey('Verify Email Address');
    expect($json)->toHaveKey('My Custom Key', 'Valore utente');

    cleanDir($base);
});

test('unproject removes locale from .lingua-managed.json', function (): void {
    [$notificationsPath, $langPath, $base] = makeTempDirs();

    file_put_contents($langPath.'/it.json', json_encode([
        'Reset Password' => 'Ripristina Password',
    ]));
    file_put_contents($langPath.'/.lingua-managed.json', json_encode([
        'it' => ['Reset Password'],
        'es' => ['Reset Password'],
    ]));

    makeProjector($notificationsPath, $langPath)->unproject('it');

    $manifest = readJson($langPath.'/.lingua-managed.json');

    expect($manifest)->not->toHaveKey('it');
    expect($manifest)->toHaveKey('es'); // other locales untouched

    cleanDir($base);
});

test('unproject is no-op when no sidecar manifest exists', function (): void {
    [$notificationsPath, $langPath, $base] = makeTempDirs();

    file_put_contents($langPath.'/it.json', json_encode(['My Key' => 'Valore']));

    makeProjector($notificationsPath, $langPath)->unproject('it');

    // File untouched
    $json = readJson($langPath.'/it.json');
    expect($json)->toHaveKey('My Key', 'Valore');

    cleanDir($base);
});

test('project is no-op when no notifications file exists for locale', function (): void {
    [$notificationsPath, $langPath, $base] = makeTempDirs();

    makeProjector($notificationsPath, $langPath)->project('xx');

    expect(file_exists($langPath.'/xx.json'))->toBeFalse();

    cleanDir($base);
});

test('project throws on unsafe locale path segment', function (string $unsafe): void {
    [$notificationsPath, $langPath, $base] = makeTempDirs();

    expect(fn () => makeProjector($notificationsPath, $langPath)->project($unsafe))
        ->toThrow(InvalidArgumentException::class);

    cleanDir($base);
})->with(['../etc', '/etc/passwd', "it\0evil", '']);
