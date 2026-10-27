<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Support;

use JsonException;
use RuntimeException;

/**
 * Writes files atomically via a temp file + rename strategy.
 *
 * Every write: verifies mkdir and file_put_contents return values;
 * uses JSON_THROW_ON_ERROR so invalid data never silently corrupts
 * an existing file; renames atomically so readers never see a partial write.
 */
final class AtomicFileWriter
{
    /**
     * Encode $data as JSON and write atomically to $path.
     *
     * @param  int  $flags  JSON encode flags merged with JSON_THROW_ON_ERROR.
     *
     * @throws RuntimeException when encoding fails or the write cannot be completed.
     */
    public function putJson(string $path, array $data, int $flags): void
    {
        try {
            $contents = json_encode($data, $flags | JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException(
                "[Lingua] JSON encode failed for {$path}: {$e->getMessage()}",
                0,
                $e
            );
        }

        $this->put($path, $contents."\n");
    }

    /**
     * Write a PHP source file atomically to $path.
     *
     * Invalidates the opcache entry so the next `include` reads the new file
     * rather than the stale compiled bytecode cached within the same request.
     *
     * @throws RuntimeException when the write cannot be completed.
     */
    public function putPhp(string $path, string $content): void
    {
        $this->put($path, $content);

        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($path, true);
        }
    }

    /**
     * Write $contents to $path atomically (temp file in same dir + rename).
     *
     * Ensures the parent directory exists before writing. Removes the temp
     * file on any failure and rethrows as RuntimeException.
     *
     * @throws RuntimeException on mkdir or write failure.
     */
    public function put(string $path, string $contents): void
    {
        $dir = dirname($path);
        $this->ensureDir($dir);

        // tempnam() creates the file exclusively with a random name in the same
        // directory, so a planted symlink or a predictable name cannot be followed.
        $tmp = @tempnam($dir, '.lingua-');

        if ($tmp === false) {
            throw new RuntimeException(
                "[Lingua] Could not create temp file in: {$dir}"
            );
        }

        // tempnam() silently falls back to the system temp dir when $dir is not
        // writable: a temp file outside $dir would break atomicity of the rename.
        if (realpath(dirname($tmp)) !== realpath($dir)) {
            @unlink($tmp);

            throw new RuntimeException(
                "[Lingua] Could not create temp file in: {$dir}"
            );
        }

        $renamed = false;

        try {
            // @ suppresses the PHP warning that would otherwise become an
            // ErrorException before we can check the return value ourselves.
            $written = @file_put_contents($tmp, $contents);

            if ($written === false) {
                throw new RuntimeException(
                    "[Lingua] Could not write temp file: {$tmp}"
                );
            }

            // tempnam() creates the file as 0600: restore the target's mode, or the
            // mode file_put_contents would have given a new file.
            $mode = file_exists($path) ? (fileperms($path) & 0777) : (0666 & ~umask());

            if (! @chmod($tmp, $mode)) {
                throw new RuntimeException(
                    "[Lingua] Could not set permissions on temp file: {$tmp}"
                );
            }

            if (! @rename($tmp, $path)) {
                throw new RuntimeException(
                    "[Lingua] Could not rename {$tmp} → {$path}"
                );
            }

            $renamed = true;
        } finally {
            // Any Throwable (not only RuntimeException) must not leave the temp file behind.
            if (! $renamed && file_exists($tmp)) {
                @unlink($tmp);
            }
        }
    }

    /**
     * Create $dir (and all parents) if it does not exist.
     *
     * Race-safe: if mkdir fails but the directory now exists (concurrent
     * creation), the call succeeds silently.
     *
     * @throws RuntimeException when the directory cannot be created.
     */
    public function ensureDir(string $dir): void
    {
        if (is_dir($dir)) {
            return;
        }

        if (! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new RuntimeException(
                "[Lingua] Could not create directory: {$dir}"
            );
        }
    }
}
