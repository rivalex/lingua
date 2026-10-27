<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Support;

use Illuminate\Support\Facades\DB;
use Rivalex\Lingua\Contracts\TranslationRepository;
use Rivalex\Lingua\Facades\Lingua;
use Rivalex\Lingua\Models\Language;

/**
 * Removes a language together with its translation values.
 *
 * Shared by the delete component and the lingua:remove command so both
 * behave the same in database mode and in file mode.
 */
final class LanguageRemover
{
    public function __construct(private readonly TranslationRepository $repository) {}

    /**
     * Forget the locale on every non-vendor line, delete the Language record
     * and reorder the remaining languages.
     *
     * Database mode runs inside one transaction. File mode has no database
     * transaction covering the lang files, so it runs unwrapped.
     */
    public function remove(string $locale): void
    {
        $remove = function () use ($locale): void {
            // Vendor lines are protected: they are never edited, so they keep the locale.
            $this->repository->all()
                ->filter(fn ($line) => ! $line->isVendor && array_key_exists($locale, $line->text))
                ->each(fn ($line) => $this->repository->forgetLocale($line, $locale));
            // removeLanguage() unprojects notification keys AND deletes the Language record.
            Lingua::removeLanguage($locale);
            app(Language::class)->reorderLanguages();
        };

        if (linguaIsFileMode()) {
            $remove();

            return;
        }

        DB::transaction($remove);
    }
}
