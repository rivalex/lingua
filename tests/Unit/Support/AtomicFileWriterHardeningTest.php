<?php

declare(strict_types=1);

use Rivalex\Lingua\Support\AtomicFileWriter;

beforeEach(function (): void {
    $this->hardeningDir = sys_get_temp_dir().'/afw_hardening_'.bin2hex(random_bytes(6));
    mkdir($this->hardeningDir, 0755, true);
});

afterEach(function (): void {
    foreach (glob($this->hardeningDir.'/{,.}*', GLOB_BRACE) ?: [] as $file) {
        if (is_file($file) || is_link($file)) {
            unlink($file);
        }
    }
    rmdir($this->hardeningDir);
});

test('a symlink planted at the old predictable temp name is not followed', function (): void {
    $path = $this->hardeningDir.'/target.txt';
    $victim = $this->hardeningDir.'/victim.txt';
    file_put_contents($victim, 'untouched');
    symlink($victim, $path.'.tmp.'.getmypid());

    (new AtomicFileWriter)->put($path, 'new contents');

    expect(file_get_contents($victim))->toBe('untouched')
        ->and(file_get_contents($path))->toBe('new contents');
})->skip(PHP_OS_FAMILY === 'Windows', 'symlinks need extra privileges on Windows');

test('an existing file keeps its mode after a rewrite', function (): void {
    $path = $this->hardeningDir.'/mode.txt';
    file_put_contents($path, 'old');
    chmod($path, 0640);
    clearstatcache();

    (new AtomicFileWriter)->put($path, 'new');
    clearstatcache();

    expect(fileperms($path) & 0777)->toBe(0640)
        ->and(file_get_contents($path))->toBe('new');
})->skip(PHP_OS_FAMILY === 'Windows', 'chmod not supported on Windows');

test('a new file gets the default mode', function (): void {
    $path = $this->hardeningDir.'/fresh.txt';

    (new AtomicFileWriter)->put($path, 'x');
    clearstatcache();

    expect(fileperms($path) & 0777)->toBe(0666 & ~umask());
})->skip(PHP_OS_FAMILY === 'Windows', 'chmod not supported on Windows');

test('no temp file is left behind after a write', function (): void {
    (new AtomicFileWriter)->put($this->hardeningDir.'/a.txt', 'x');

    expect(array_values(array_diff(scandir($this->hardeningDir), ['.', '..'])))->toBe(['a.txt']);
});

test('a non-writable directory is rejected instead of using the system temp dir', function (): void {
    $dir = $this->hardeningDir.'/locked';
    mkdir($dir, 0755);
    $path = $dir.'/existing.txt';
    file_put_contents($path, 'old');
    chmod($dir, 0555);

    try {
        expect(fn () => (new AtomicFileWriter)->put($path, 'new'))
            ->toThrow(RuntimeException::class, 'Could not create temp file');
    } finally {
        chmod($dir, 0755);
        unlink($path);
        rmdir($dir);
    }
})->skip(
    PHP_OS_FAMILY === 'Windows' || (function_exists('posix_geteuid') && posix_geteuid() === 0),
    'requires chmod support and a non-root user'
);

test('no temp file is left behind when the rename fails', function (): void {
    $target = $this->hardeningDir.'/blocked';
    mkdir($target, 0755);

    try {
        expect(fn () => (new AtomicFileWriter)->put($target, 'x'))->toThrow(RuntimeException::class);

        expect(array_values(array_diff(scandir($this->hardeningDir), ['.', '..'])))->toBe(['blocked']);
    } finally {
        rmdir($target);
    }
});

test('no temp file is left behind when a non-runtime throwable interrupts the write', function (): void {
    // A handler that throws regardless of the @ operator turns the suppressed
    // warning of the failing rename into an ErrorException (not a RuntimeException).
    $target = $this->hardeningDir.'/blocked';
    mkdir($target, 0755);

    set_error_handler(function (int $severity, string $message): never {
        throw new ErrorException($message, 0, $severity);
    });

    try {
        expect(fn () => (new AtomicFileWriter)->put($target, 'x'))->toThrow(ErrorException::class);
    } finally {
        restore_error_handler();
        $leftovers = array_values(array_diff(scandir($this->hardeningDir), ['.', '..']));
        rmdir($target);
    }

    expect($leftovers)->toBe(['blocked']);
});
