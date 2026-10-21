<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Locales;

use InvalidArgumentException;
use Rivalex\Lingua\Support\AtomicFileWriter;
use RuntimeException;

/**
 * Projects bundled notification translations into the user app's lang/{locale}.json
 * at install-time, enabling Laravel's Lang::getFromJson() to resolve translated email
 * strings out-of-the-box — without the user having to publish or edit lang files manually.
 *
 * Merge is NON-destructive: existing user-defined keys are never overwritten.
 * Idempotent: running project() twice produces the same result.
 *
 * A sidecar manifest (lang/.lingua-managed.json) tracks which keys lingua projected
 * into each locale, so unproject() can remove only those keys — never user keys.
 *
 * All file writes are atomic (temp + rename via AtomicFileWriter). The manifest is
 * updated only AFTER a successful write, so a failed write never leaves the manifest
 * in a divergent state.
 */
final class NotificationProjector
{
    public function __construct(
        private readonly string $notificationsPath,
        private readonly string $langPath,
        private readonly AtomicFileWriter $writer,
    ) {}

    /**
     * Project notification translations for the given locale into lang/{locale}.json.
     * Called by Lingua::addLanguage() after the DB record is created.
     *
     * @throws InvalidArgumentException on unsafe locale path segment
     * @throws RuntimeException on I/O failure (manifest is NOT updated in that case)
     */
    public function project(string $locale): void
    {
        $this->assertSafePathSegment($locale);

        $sourceFile = $this->notificationsPath.'/'.$locale.'.php';

        if (! file_exists($sourceFile)) {
            return;
        }

        $sourceTranslations = include $sourceFile;

        if (! is_array($sourceTranslations)) {
            return;
        }

        $jsonFile = $this->langPath.'/'.$locale.'.json';
        $existing = $this->readJson($jsonFile);

        $projectedKeys = [];
        $merged = $existing;

        foreach ($sourceTranslations as $enKey => $translation) {
            if (! is_string($enKey) || ! is_string($translation)) {
                continue;
            }

            // Non-destructive: only add keys not already present
            if (! array_key_exists($enKey, $merged)) {
                $merged[$enKey] = $translation;
                $projectedKeys[] = $enKey;
            }
        }

        if ($projectedKeys === []) {
            return;
        }

        // Write FIRST — throws on failure, so manifest is never updated on error.
        $this->writeJson($jsonFile, $merged);
        $this->updateManagedManifest($locale, $projectedKeys, 'add');
    }

    /**
     * Remove only the lingua-managed notification keys from lang/{locale}.json.
     * Called by Lingua::removeLanguage() before the DB record is deleted.
     * User-defined keys in the JSON file are never touched.
     *
     * @throws InvalidArgumentException on unsafe locale path segment
     * @throws RuntimeException on I/O failure (manifest is NOT cleaned in that case)
     */
    public function unproject(string $locale): void
    {
        $this->assertSafePathSegment($locale);

        $managedKeys = $this->readManagedKeys($locale);

        if ($managedKeys === []) {
            return;
        }

        $jsonFile = $this->langPath.'/'.$locale.'.json';
        $existing = $this->readJson($jsonFile);

        foreach ($managedKeys as $key) {
            unset($existing[$key]);
        }

        // Write / delete FIRST — throws on failure so the manifest is not
        // cleaned prematurely, keeping the keys recoverable on retry.
        if ($existing === []) {
            if (file_exists($jsonFile)) {
                if (! unlink($jsonFile)) {
                    throw new RuntimeException(
                        "[Lingua] Could not delete lang file: {$jsonFile}"
                    );
                }
            }
        } else {
            $this->writeJson($jsonFile, $existing);
        }

        $this->updateManagedManifest($locale, [], 'remove');
    }

    /**
     * @return array<string, mixed>
     */
    private function readJson(string $path): array
    {
        if (! file_exists($path)) {
            return [];
        }

        $raw = file_get_contents($path);

        if ($raw === false || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @throws RuntimeException on encode or write failure
     */
    private function writeJson(string $path, array $data): void
    {
        $this->writer->putJson(
            $path,
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }

    /**
     * @return list<string>
     */
    private function readManagedKeys(string $locale): array
    {
        $manifest = $this->readManagedManifest();

        return $manifest[$locale] ?? [];
    }

    /**
     * @return array<string, list<string>>
     */
    private function readManagedManifest(): array
    {
        $path = $this->langPath.'/.lingua-managed.json';

        if (! file_exists($path)) {
            return [];
        }

        $raw = file_get_contents($path);

        if ($raw === false) {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  list<string>  $keys
     */
    private function updateManagedManifest(string $locale, array $keys, string $operation): void
    {
        $manifest = $this->readManagedManifest();

        if ($operation === 'add') {
            $existing = $manifest[$locale] ?? [];
            $manifest[$locale] = array_values(array_unique(array_merge($existing, $keys)));
        } elseif ($operation === 'remove') {
            unset($manifest[$locale]);
        }

        $path = $this->langPath.'/.lingua-managed.json';
        $this->writeJson($path, $manifest);
    }

    private function assertSafePathSegment(string $segment): void
    {
        if ($segment === '' ||
            str_contains($segment, '/') ||
            str_contains($segment, '\\') ||
            str_contains($segment, '..') ||
            str_contains($segment, "\0")
        ) {
            throw new InvalidArgumentException(
                "[Lingua] Unsafe locale path segment: {$segment}"
            );
        }
    }
}
