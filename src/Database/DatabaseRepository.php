<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Database;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Rivalex\Lingua\Contracts\BaseTranslationSource;
use Rivalex\Lingua\Contracts\TranslationRepository;
use Rivalex\Lingua\Enums\LinguaType;
use Rivalex\Lingua\Models\Translation;
use Rivalex\Lingua\Support\TranslationLine;

/**
 * Database-backed TranslationRepository implementation.
 *
 * Wraps the Translation Eloquent model 1:1. No behaviour change from the
 * direct Model call-sites it replaces. SQL JSON path expressions are
 * intentionally used here (DB-mode only — see plan §3.3).
 */
final class DatabaseRepository implements TranslationRepository
{
    public function __construct(
        private readonly BaseTranslationSource $bundled,
    ) {}

    /**
     * Create a new key with a value for the default locale.
     *
     * @throws \InvalidArgumentException on unsafe path segment
     */
    public function create(
        string $group,
        string $key,
        LinguaType $type,
        string $locale,
        string $value,
        bool $isVendor = false,
        ?string $vendor = null
    ): TranslationLine {
        $model = Translation::create([
            'group' => $group,
            'key' => $key,
            'type' => $type,
            'text' => [$locale => $value],
            'is_vendor' => $isVendor,
            'vendor' => $vendor,
        ]);

        return $this->toLine($model);
    }

    /**
     * Set/update the value of one key for one locale.
     */
    public function setValue(TranslationLine $line, string $locale, string $value): TranslationLine
    {
        $model = $this->requireModel($line);
        $model->setTranslation($locale, $value);

        return $this->toLine($model->fresh());
    }

    /**
     * Change meta (group/key/type) for a key.
     */
    public function updateMeta(TranslationLine $line, string $group, string $key, LinguaType $type): TranslationLine
    {
        $model = $this->requireModel($line);

        if ($model->is_vendor) {
            $model->update(['type' => $type]);
        } else {
            $model->update([
                'group' => $group,
                'key' => $key,
                'type' => $type,
            ]);
        }

        return $this->toLine($model->fresh());
    }

    /**
     * Remove the value for one locale (does not delete the key).
     */
    public function forgetLocale(TranslationLine $line, string $locale): void
    {
        $this->requireModel($line)->forgetTranslation($locale);
    }

    /**
     * Delete the entire key (all locales).
     */
    public function deleteKey(TranslationLine $line): void
    {
        $this->requireModel($line)->delete();
    }

    /**
     * Distinct group names, sorted.
     *
     * @return list<string>
     */
    public function groups(): array
    {
        return Translation::orderBy('group')->groupBy('group')->pluck('group')->toArray();
    }

    /**
     * Look up a single key.
     */
    public function find(string $group, string $key, bool $isVendor, ?string $vendor): ?TranslationLine
    {
        $model = Translation::where('group', $group)
            ->where('key', $key)
            ->where('is_vendor', $isVendor)
            ->where('vendor', $vendor)
            ->first();

        return $model ? $this->toLine($model) : null;
    }

    /**
     * Paginated list for the Translations table component.
     *
     * @return LengthAwarePaginator<TranslationLine>
     */
    public function paginate(
        string $locale,
        string $search,
        string $group,
        bool $onlyMissing,
        int $perPage
    ): LengthAwarePaginator {
        $safeLocale = preg_match('/^[a-zA-Z]{2,8}([_-][a-zA-Z0-9]{1,8})*$/', $locale)
            ? $locale
            : linguaDefaultLocale();

        $defaultLocale = linguaDefaultLocale();

        return Translation::query()
            ->when(! empty($search), fn ($q) => $q->where(
                fn ($inner) => $inner
                    ->whereLike('group_key', "%{$search}%")
                    ->orWhereLike('text->'.$defaultLocale, "%{$search}%")
                    ->orWhereLike('text->'.$safeLocale, "%{$search}%")
            ))
            // "Missing" = key absent, null, or empty string — same definition
            // used by FileRepository and the Statistics component. whereNull
            // alone missed empty-string values, so the two drivers disagreed.
            ->when($onlyMissing, fn ($q) => $q->where(
                fn ($inner) => $inner
                    ->whereNull('text->'.$safeLocale)
                    ->orWhere('text->'.$safeLocale, '=', '')
            ))
            ->when($group, fn ($q) => $q->where('group', '=', $group))
            ->paginate($perPage)
            ->through(fn ($model) => $this->toLine($model));
    }

    /**
     * All rows (for Statistics).
     *
     * @return Collection<int, TranslationLine>
     */
    public function all(bool $includeVendor = true): Collection
    {
        $query = Translation::select(['id', 'group', 'key', 'group_key', 'type', 'text', 'is_vendor', 'vendor']);

        if (! $includeVendor) {
            $query->where('is_vendor', false);
        }

        return $query->get()->map(fn ($model) => $this->toLine($model));
    }

    /**
     * @return array{total: int, byLocale: array<string, int>}
     */
    public function counts(): array
    {
        return Translation::translationCounts();
    }

    /**
     * @return array{total: int, translated: int, missing: int, percentage: float|int}
     */
    public function localeStats(string $locale): array
    {
        return Translation::getLocaleStats($locale);
    }

    /**
     * Find by full dotted key.
     */
    public function findByKey(string $key): ?TranslationLine
    {
        $model = Translation::where('key', $key)->first();

        return $model ? $this->toLine($model) : null;
    }

    /**
     * All rows for a group, optionally filtered by locale presence (SQL JSON).
     *
     * @return Collection<int, TranslationLine>
     */
    public function byGroup(string $group, ?string $locale = null): Collection
    {
        return Translation::where('group', $group)
            ->when($locale, fn ($q) => $q->whereNotNull('text->'.$locale))
            ->get()
            ->map(fn ($model) => $this->toLine($model));
    }

    /**
     * All vendor rows for $vendor, optionally filtered by locale presence (SQL JSON).
     *
     * @return Collection<int, TranslationLine>
     */
    public function vendor(string $vendor, ?string $locale = null): Collection
    {
        return Translation::where('is_vendor', true)
            ->where('vendor', $vendor)
            ->when($locale, fn ($q) => $q->whereNotNull('text->'.$locale))
            ->get()
            ->map(fn ($model) => $this->toLine($model));
    }

    /**
     * Seed the storage backend for a newly installed locale (database-mode).
     *
     * 1. Runs syncToDatabase() to import default-locale keys and any lang-file
     *    translations already present on disk.
     * 2. For non-default locales, deterministically writes every bundled
     *    translation for $locale into language_lines.text[$locale], independent
     *    of the two-pass orphan filter or default-locale resolution.
     *    Keys absent from the bundle are left untouched (shown as "missing").
     */
    public function installLocale(string $locale): void
    {
        Translation::syncToDatabase();

        if ($locale === linguaDefaultLocale()) {
            return;
        }

        foreach ($this->bundled->translationsFor($locale) as $entry) {
            if ($entry['is_vendor'] || $entry['value'] === '') {
                continue;
            }

            $existing = Translation::where('group', $entry['group'])
                ->where('key', $entry['key'])
                ->where('is_vendor', false)
                ->whereNull('vendor')
                ->first();

            // Create-if-absent: write the bundled value regardless of whether a default-locale
            // row already exists. This guarantees the locale is seeded independent of the
            // syncToDatabase orphan filter or default-locale resolution.
            Translation::updateOrCreate(
                ['group' => $entry['group'], 'key' => $entry['key'], 'is_vendor' => false, 'vendor' => null],
                [
                    'type' => $existing->type ?? LinguaType::text,
                    'text' => array_merge($existing->text ?? [], [$locale => $entry['value']]),
                ],
            );
        }
    }

    /**
     * Convert a Translation Eloquent model to a TranslationLine DTO.
     */
    public function toLine(Translation $model): TranslationLine
    {
        return new TranslationLine(
            group: $model->group,
            key: $model->key,
            groupKey: $model->group_key,
            type: $model->type,
            text: $model->text ?? [],
            isVendor: (bool) $model->is_vendor,
            vendor: $model->vendor,
            id: $model->getKey(),
        );
    }

    /**
     * Resolve a Translation model from a TranslationLine or throw.
     *
     * @throws ModelNotFoundException
     */
    private function requireModel(TranslationLine $line): Translation
    {
        if ($line->id !== null) {
            return Translation::findOrFail($line->id);
        }

        return Translation::where('group', $line->group)
            ->where('key', $line->key)
            ->where('is_vendor', $line->isVendor)
            ->where('vendor', $line->vendor)
            ->firstOrFail();
    }
}
