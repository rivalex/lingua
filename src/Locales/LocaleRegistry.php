<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Locales;

/**
 * In-process registry of static locale metadata.
 *
 * Loaded once from resources/data/locales.php and cached in-process.
 * Resolves both code ('en') and regional ('en_US') in info() / has().
 */
final class LocaleRegistry
{
    /** @var array<string, LocaleInfo>  keyed by code */
    private array $byCode = [];

    /** @var array<string, string>  regional → code index */
    private array $regionalIndex = [];

    private bool $loaded = false;

    public function __construct(
        private readonly string $dataPath,
    ) {}

    /**
     * Return all available locale info objects, keyed by code.
     *
     * @return array<string, LocaleInfo>
     */
    public function all(): array
    {
        $this->load();

        return $this->byCode;
    }

    /**
     * Return metadata for a single locale, resolving both code and regional forms.
     *
     * @param  string  $locale  ISO code ('en') or regional code ('en_US')
     */
    public function info(string $locale): ?LocaleInfo
    {
        $this->load();

        return $this->byCode[$locale]
            ?? (isset($this->regionalIndex[$locale]) ? $this->byCode[$this->regionalIndex[$locale]] : null);
    }

    /**
     * Return all available locale codes.
     *
     * @return array<int, string>
     */
    public function availableCodes(): array
    {
        $this->load();

        return array_keys($this->byCode);
    }

    /**
     * Determine whether a locale (by code or regional) is known in the dataset.
     */
    public function has(string $locale): bool
    {
        return $this->info($locale) !== null;
    }

    private function load(): void
    {
        if ($this->loaded) {
            return;
        }

        $data = require $this->dataPath;

        foreach ($data as $code => $entry) {
            $info = new LocaleInfo(
                code: $entry['code'],
                regional: $entry['regional'] ?? null,
                type: $entry['type'],
                name: $entry['name'],
                native: $entry['native'],
                direction: $entry['direction'],
            );

            $this->byCode[$code] = $info;

            if ($info->regional !== null) {
                $this->regionalIndex[$info->regional] = $code;
            }
        }

        $this->loaded = true;
    }
}
