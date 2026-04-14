<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Livewire;

use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\Translation;

/**
 * Statistics component.
 *
 * Displays translation coverage per locale, per-group breakdown,
 * and an expandable missing-keys panel. All aggregation is done in PHP
 * after a single DB fetch, so it works across MySQL, PostgreSQL, SQLite
 * and SQL Server without JSON SQL functions.
 */
#[Title('Translation Statistics')]
final class Statistics extends Component
{
    /**
     * The locale code currently expanded in the missing-keys panel.
     * Null means no panel is open.
     */
    public ?string $expandedLocale = null;

    /**
     * Whether to include vendor translations in statistics.
     * Exposed as a toggle so the user can filter the view.
     */
    public bool $includeVendor = false;

    // -------------------------------------------------------------------------
    // Computed — cached (invalidated manually in toggleVendor)
    // -------------------------------------------------------------------------

    /**
     * All registered languages ordered by sort position, then by name.
     *
     * @return Collection<int, Language>
     */
    #[Computed(cache: true)]
    public function languages(): Collection
    {
        return Language::orderBy('sort')->orderBy('name')->get();
    }

    /**
     * The language marked as the system default.
     */
    #[Computed(cache: true)]
    public function defaultLanguage(): ?Language
    {
        return $this->languages->firstWhere('is_default', true);
    }

    /**
     * All translation records, optionally filtered by vendor flag.
     * This is the single DB fetch that feeds every other computed property.
     *
     * @return Collection<int, Translation>
     */
    #[Computed(cache: true)]
    public function lines(): Collection
    {
        $query = Translation::select(['id', 'group', 'key', 'group_key', 'text', 'is_vendor', 'vendor']);

        if (! $this->includeVendor) {
            $query->where('is_vendor', false);
        }

        return $query->get();
    }

    /**
     * Total number of translation keys currently visible.
     */
    #[Computed(cache: true)]
    public function totalKeys(): int
    {
        return $this->lines->count();
    }

    /**
     * Total number of distinct translation groups.
     */
    #[Computed(cache: true)]
    public function totalGroups(): int
    {
        return $this->lines->pluck('group')->unique()->count();
    }

    /**
     * Per-locale coverage statistics.
     *
     * Each entry contains:
     *   - language   : Language model instance
     *   - translated : int   (keys with a non-empty value for this locale)
     *   - missing    : int   (keys without a value for this locale)
     *   - percentage : float (0–100, rounded to one decimal)
     *
     * @return Collection<int, array{language: Language, translated: int, missing: int, percentage: float}>
     */
    #[Computed(cache: true)]
    public function coverageStats(): Collection
    {
        $total = $this->totalKeys;
        $lines = $this->lines;

        return $this->languages->map(function (Language $language) use ($total, $lines): array {
            $locale = $language->code;

            $translated = $lines->filter(
                fn (Translation $line): bool => $this->isTranslated($line->text, $locale)
            )->count();

            $missing = $total - $translated;
            $percentage = $total > 0 ? round(($translated / $total) * 100, 1) : 0.0;

            return [
                'language' => $language,
                'translated' => $translated,
                'missing' => $missing,
                'percentage' => $percentage,
            ];
        });
    }

    /**
     * Per-group breakdown: translated-count per locale for each group.
     *
     * Returns a Collection keyed by group name. Each entry contains:
     *   - total   : int                   (total keys in this group)
     *   - locales : array<string, int>    (locale_code => translated_count)
     *
     * @return Collection<string, array{total: int, locales: array<string, int>}>
     */
    #[Computed(cache: true)]
    public function groupBreakdown(): Collection
    {
        $localeCodes = $this->languages->pluck('code')->all();

        return $this->lines
            ->groupBy('group')
            ->map(function (Collection $groupLines) use ($localeCodes): array {
                $localeCounts = [];

                foreach ($localeCodes as $locale) {
                    $localeCounts[$locale] = $groupLines->filter(
                        fn (Translation $line): bool => $this->isTranslated($line->text, $locale)
                    )->count();
                }

                return [
                    'total' => $groupLines->count(),
                    'locales' => $localeCounts,
                ];
            })
            ->sortKeys();
    }

    // -------------------------------------------------------------------------
    // Computed — not cached (depends on expandedLocale which changes per action)
    // -------------------------------------------------------------------------

    /**
     * Missing keys for the currently expanded locale.
     * Returns an empty collection when no locale is expanded.
     *
     * @return Collection<int, array{group: string, key: string, group_key: string}>
     */
    #[Computed]
    public function missingKeys(): Collection
    {
        if ($this->expandedLocale === null) {
            return collect();
        }

        $locale = $this->expandedLocale;

        return $this->lines
            ->filter(fn (Translation $line): bool => ! $this->isTranslated($line->text, $locale))
            ->map(fn (Translation $line): array => [
                'group' => $line->group,
                'key' => $line->key,
                'group_key' => $line->group_key,
            ])
            ->values();
    }

    // -------------------------------------------------------------------------
    // Actions
    // -------------------------------------------------------------------------

    /**
     * Toggle the missing-keys panel for a given locale.
     * Clicking the same locale again collapses the panel.
     */
    public function toggleMissingKeys(string $locale): void
    {
        $this->expandedLocale = $this->expandedLocale === $locale ? null : $locale;
    }

    /**
     * Toggle vendor-translation inclusion and bust all cached computed properties.
     *
     * Livewire 4 caches computed properties per request; unsetting them forces
     * recomputation on the next access within the same request cycle.
     */
    public function toggleVendor(): void
    {
        $this->includeVendor = ! $this->includeVendor;

        unset(
            $this->languages,
            $this->defaultLanguage,
            $this->lines,
            $this->totalKeys,
            $this->totalGroups,
            $this->coverageStats,
            $this->groupBreakdown,
        );
    }

    // -------------------------------------------------------------------------
    // Render
    // -------------------------------------------------------------------------

    public function render(): View
    {
        return view('lingua::statistics');
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Determine whether a translation exists and is non-empty for a given locale.
     *
     * A translation is considered MISSING when any of the following is true:
     *   - the locale key is absent from the text array
     *   - the value is null
     *   - the value is an empty string after trimming
     *
     * @param  array<string, mixed>  $text  Decoded JSON from the database column
     */
    private function isTranslated(array $text, string $locale): bool
    {
        if (! array_key_exists($locale, $text)) {
            return false;
        }

        $value = $text[$locale];

        if ($value === null) {
            return false;
        }

        return trim((string) $value) !== '';
    }
}
