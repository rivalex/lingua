<?php

declare(strict_types=1);

use Rivalex\Lingua\Support\AtomicFileWriter;

function afw_tempDir(): string
{
    $dir = sys_get_temp_dir().'/afw_test_'.uniqid();
    mkdir($dir, 0755, true);

    return $dir;
}

function afw_cleanDir(string $path): void
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

// ── put ────────────────────────────────────────────────────────────────────────

test('put creates file with correct contents', function (): void {
    $dir = afw_tempDir();
    $path = $dir.'/hello.txt';

    (new AtomicFileWriter)->put($path, 'hello world');

    expect(file_get_contents($path))->toBe('hello world');

    afw_cleanDir($dir);
});

test('put creates parent directory if missing', function (): void {
    $dir = afw_tempDir();
    $nested = $dir.'/a/b/c';
    $path = $nested.'/file.txt';

    (new AtomicFileWriter)->put($path, 'nested');

    expect(is_dir($nested))->toBeTrue()
        ->and(file_get_contents($path))->toBe('nested');

    afw_cleanDir($dir);
});

test('put leaves no temp file on success', function (): void {
    $dir = afw_tempDir();
    $path = $dir.'/out.txt';

    (new AtomicFileWriter)->put($path, 'data');

    $temps = glob($dir.'/*.tmp.*') ?: [];
    expect($temps)->toBeEmpty();

    afw_cleanDir($dir);
});

// ── putJson ────────────────────────────────────────────────────────────────────

test('putJson writes valid JSON file', function (): void {
    $dir = afw_tempDir();
    $path = $dir.'/out.json';

    (new AtomicFileWriter)->putJson($path, ['key' => 'vàlue'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    $decoded = json_decode(file_get_contents($path), true);
    expect($decoded)->toBe(['key' => 'vàlue']);

    afw_cleanDir($dir);
});

test('putJson with invalid UTF-8 throws RuntimeException and does NOT write file', function (): void {
    $dir = afw_tempDir();
    $path = $dir.'/should-not-exist.json';

    // \xB1\x31 is invalid UTF-8
    expect(fn () => (new AtomicFileWriter)->putJson($path, ['bad' => "\xB1\x31"], 0))
        ->toThrow(RuntimeException::class);

    expect(file_exists($path))->toBeFalse();

    afw_cleanDir($dir);
});

test('putJson with invalid UTF-8 does NOT overwrite existing file', function (): void {
    $dir = afw_tempDir();
    $path = $dir.'/existing.json';
    file_put_contents($path, '{"original":"value"}');

    expect(fn () => (new AtomicFileWriter)->putJson($path, ['bad' => "\xB1\x31"], 0))
        ->toThrow(RuntimeException::class);

    // Original file untouched
    expect(file_get_contents($path))->toBe('{"original":"value"}');

    afw_cleanDir($dir);
});

// ── putPhp ─────────────────────────────────────────────────────────────────────

test('putPhp writes PHP file and content is syntactically valid', function (): void {
    $dir = afw_tempDir();
    $path = $dir.'/out.php';
    $content = "<?php\n\nreturn ['a' => 1];\n";

    (new AtomicFileWriter)->putPhp($path, $content);

    expect(file_get_contents($path))->toBe($content);

    // Confirm PHP syntax is valid
    $output = null;
    exec('php -l '.escapeshellarg($path).' 2>&1', $output, $code);
    expect($code)->toBe(0);

    afw_cleanDir($dir);
});

// ── ensureDir ─────────────────────────────────────────────────────────────────

test('ensureDir creates nested directories', function (): void {
    $dir = afw_tempDir();
    $target = $dir.'/x/y/z';

    (new AtomicFileWriter)->ensureDir($target);

    expect(is_dir($target))->toBeTrue();

    afw_cleanDir($dir);
});

test('ensureDir is a no-op when directory already exists', function (): void {
    $dir = afw_tempDir();

    // Should not throw
    (new AtomicFileWriter)->ensureDir($dir);

    expect(is_dir($dir))->toBeTrue();

    afw_cleanDir($dir);
});
