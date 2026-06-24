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

        $tmp = $path.'.tmp.'.getmypid();

        try {
            // @ suppresses the PHP warning that would otherwise become an
            // ErrorException before we can check the return value ourselves.
            $written = @file_put_contents($tmp, $contents);

            if ($written === false) {
                throw new RuntimeException(
                    "[Lingua] Could not write temp file: {$tmp}"
                );
            }

            if (! @rename($tmp, $path)) {
                throw new RuntimeException(
                    "[Lingua] Could not rename {$tmp} → {$path}"
                );
            }
        } catch (RuntimeException $e) {
            if (file_exists($tmp)) {
                @unlink($tmp);
            }
            throw $e;
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
