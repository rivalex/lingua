<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Database;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator as ConcretePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Rivalex\Lingua\Contracts\BaseTranslationSource;
use Rivalex\Lingua\Contracts\TranslationRepository;
use Rivalex\Lingua\Contracts\WritesTranslationBatches;
use Rivalex\Lingua\Enums\LinguaType;
use Rivalex\Lingua\Exceptions\UnsafeTranslationValue;
use Rivalex\Lingua\Exceptions\VendorTranslationProtectedException;
use Rivalex\Lingua\Models\Translation;
use Rivalex\Lingua\Support\PathGuard;
use Rivalex\Lingua\Support\TranslationIdentityRules;
use Rivalex\Lingua\Support\TranslationLine;
use Rivalex\Lingua\Support\TranslationValuePolicy;

/**
 * Database-backed TranslationRepository implementation.
 *
 * Wraps the Translation Eloquent model 1:1. No behaviour change from the
 * direct Model call-sites it replaces. Search filtering is done in PHP to
 * comply with the multi-DB constraint (no JSON SQL functions).
 */
final class DatabaseRepository implements TranslationRepository, WritesTranslationBatches
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
        TranslationIdentityRules::assertValid($group, $key, $vendor, $locale);
        $this->assertNoCollision($group, $key, $isVendor, $vendor, null);
        $value = TranslationValuePolicy::normalize($type, $value, $group, $key);

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
        TranslationIdentityRules::assertLocale($locale);

        return DB::transaction(function () use ($line, $locale, $value): TranslationLine {
            $model = $this->requireModel($line, lock: true);
            $model->setTranslation($locale, TranslationValuePolicy::normalize($model->type, $value, $model->group, $model->key));

            return $this->toLine($model->fresh());
        });
    }

    /**
     * Set several values in one transaction: a rejected change rolls back the ones before it.
     *
     * @param  list<array{line: TranslationLine, locale: string, value: string}>  $changes
     */
    public function setValues(array $changes): void
    {
        DB::transaction(function () use ($changes): void {
            foreach ($changes as $change) {
                $this->setValue($change['line'], $change['locale'], $change['value']);
            }
        });
    }

    /**
     * Change meta (group/key/type) for a key.
     */
    public function updateMeta(TranslationLine $line, string $group, string $key, LinguaType $type): TranslationLine
    {
        $model = $this->requireModel($line);

        if ($model->is_vendor) {
            $text = $this->acceptedValues($model, $model->group, $model->key, $type);
            $model->update(['type' => $type, 'text' => $text]);
        } else {
            // Only a changed identity is validated: rows synchronized from host files keep their names.
            if ($group !== $model->group || $key !== $model->key) {
                TranslationIdentityRules::assertValid($group, $key, null, null);
                $this->assertNoCollision($group, $key, false, null, $model);
            }

            $text = $this->acceptedValues($model, $group, $key, $type);

            $model->update([
                'group' => $group,
                'key' => $key,
                'type' => $type,
                'text' => $text,
            ]);
        }

        return $this->toLine($model->fresh());
    }

    /**
     * Run the value policy on every locale value of the row with its new group, key and type, and
     * return the values to store (html is sanitized, text and markdown stay as they are).
     * Nothing is written: the first rejected value throws, naming its locale.
     *
     * @return array<string, string>
     *
     * @throws UnsafeTranslationValue
     */
    private function acceptedValues(Translation $model, string $group, string $key, LinguaType $type): array
    {
        if ($group === $model->group && $key === $model->key && $type === $model->type) {
            return $model->text ?? [];
        }

        $accepted = [];

        foreach ($model->text ?? [] as $locale => $value) {
            try {
                $accepted[$locale] = TranslationValuePolicy::normalize($type, (string) $value, $group, $key);
            } catch (UnsafeTranslationValue $e) {
                throw new UnsafeTranslationValue(
                    Str::replaceFirst('[Lingua] The value', "[Lingua] The {$locale} value", $e->getMessage()),
                    previous: $e,
                );
            }
        }

        return $accepted;
    }

    /**
     * Remove the value for one locale (does not delete the key).
     *
     * @throws VendorTranslationProtectedException if the line belongs to a vendor namespace
     */
    public function forgetLocale(TranslationLine $line, string $locale): void
    {
        if ($line->isVendor) {
            throw new VendorTranslationProtectedException;
        }

        TranslationIdentityRules::assertLocale($locale);

        DB::transaction(fn () => $this->requireModel($line, lock: true)->forgetTranslation($locale));
    }

    /**
     * Delete the entire key (all locales).
     *
     * @throws VendorTranslationProtectedException if the line belongs to a vendor namespace
     */
    public function deleteKey(TranslationLine $line): void
    {
        if ($line->isVendor) {
            throw new VendorTranslationProtectedException;
        }

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
        $safeLocale = preg_match('/^[a-zA-Z]{2,8}([_-][a-zA-Z0-9]{1,8})*\z/', $locale)
            ? $locale
            : linguaDefaultLocale();

        $defaultLocale = linguaDefaultLocale();

        // Load rows (group-scoped in SQL) and apply the locale/search filters in PHP.
        // JSON-SQL functions are avoided to keep the package portable across database
        // engines, so value-level matching never touches a JSON path expression.
        $rows = Translation::query()
            ->when($group, fn ($q) => $q->where('group', '=', $group))
            ->get()
            ->map(fn ($model) => $this->toLine($model));

        if ($onlyMissing) {
            // "Missing" = key absent, null, or empty string - same definition used by
            // FileRepository and the Statistics component.
            $rows = $rows->filter(fn (TranslationLine $l) => trim($l->value($safeLocale)) === '');
        }

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $rows = $rows->filter(fn (TranslationLine $l) => str_contains(mb_strtolower($l->groupKey), $needle)
                || str_contains(mb_strtolower((string) $l->vendor), $needle)
                || str_contains(mb_strtolower($l->value($defaultLocale)), $needle)
                || str_contains(mb_strtolower($l->value($safeLocale)), $needle)
            );
        }

        $rows = $rows->values();
        $total = $rows->count();
        $page = (int) (Paginator::resolveCurrentPage() ?: 1);
        $slice = $rows->slice(($page - 1) * $perPage, $perPage)->values();

        return new ConcretePaginator($slice->all(), $total, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
        ]);
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
     * Find by full key (`group.key`, `vendor::group.key`, `single.<sentence>`); a bare key
     * is accepted only when exactly one line has it.
     */
    public function findByKey(string $key): ?TranslationLine
    {
        $model = Translation::where('group_key', $key)->first();

        if ($model === null) {
            $matches = Translation::where('key', $key)->limit(2)->get();
            $model = $matches->count() === 1 ? $matches->first() : null;
        }

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
            ->get()
            ->map(fn ($model) => $this->toLine($model))
            ->when($locale !== null, fn ($c) => $c->filter(
                fn (TranslationLine $l) => isset($l->text[$locale]) && trim((string) $l->text[$locale]) !== ''
            ))
            ->values();
    }

    /**
     * All vendor rows for $vendor, optionally filtered by locale presence (PHP filter).
     *
     * @return Collection<int, TranslationLine>
     */
    public function vendor(string $vendor, ?string $locale = null): Collection
    {
        return Translation::where('is_vendor', true)
            ->where('vendor', $vendor)
            ->get()
            ->map(fn ($model) => $this->toLine($model))
            ->when($locale !== null, fn ($c) => $c->filter(
                fn (TranslationLine $l) => isset($l->text[$locale]) && trim((string) $l->text[$locale]) !== ''
            ))
            ->values();
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
        PathGuard::assertSafeSegment($locale, 'locale');

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
                    // Belt-and-braces alongside Translation::save()'s own recompute.
                    // Cast to string: bundled entries can carry an int key for a
                    // top-level numeric-indexed array item (see TranslationFileReader::flatten()).
                    'group_key' => Translation::buildGroupKey((string) $entry['group'], (string) $entry['key'], false, null),
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
     * With $lock the row is read with a write lock, so a read-modify-write on it must run in a transaction.
     *
     * @throws ModelNotFoundException
     */
    private function requireModel(TranslationLine $line, bool $lock = false): Translation
    {
        $query = Translation::query();

        if ($lock) {
            $query->lockForUpdate();
        }

        if ($line->id !== null) {
            return $query->findOrFail($line->id);
        }

        return $query->where('group', $line->group)
            ->where('key', $line->key)
            ->where('is_vendor', $line->isVendor)
            ->where('vendor', $line->vendor)
            ->firstOrFail();
    }

    /**
     * Reject a key that collides with an existing key of the group, and a new group that differs
     * from an existing one only by case. $own is the row being renamed, excluded from the comparison.
     *
     * @throws \InvalidArgumentException on a collision
     */
    private function assertNoCollision(string $group, string $key, bool $isVendor, ?string $vendor, ?Translation $own): void
    {
        $existingKeys = TranslationIdentityRules::existingKeys($this, $group, $isVendor, $vendor);

        if ($own !== null && $own->group === $group) {
            $existingKeys = array_values(array_filter($existingKeys, fn (string $existing) => $existing !== $own->key));
        }

        $error = TranslationIdentityRules::collisionError($group, $key, $existingKeys);

        if ($error === null) {
            $groups = $this->groups();
            $error = in_array($group, $groups, true) ? null : TranslationIdentityRules::groupCaseError($group, $groups);
        }

        if ($error !== null) {
            throw new \InvalidArgumentException('[Lingua] '.$error);
        }
    }
}
