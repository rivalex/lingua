<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Commands;

use Illuminate\Console\Command;
use Rivalex\Lingua\Facades\Lingua;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\Translation;

final class RemoveLangCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lingua:remove {locale : The locale code to remove (e.g. it, es, fr)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove a language from the application (removes files and cleans the database)';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $locale = $this->argument('locale');

        // Validate the format BEFORE the locale reaches any JSON path expression.
        if (! preg_match('/^[a-zA-Z]{2,8}([_-][a-zA-Z0-9]{1,8})*$/', $locale)) {
            $this->error("Invalid locale format: {$locale}");

            return;
        }

        $this->info("Removing language: {$locale}...");

        $language = Language::where('code', $locale)->first();

        if (! $language) {
            $this->warn("Language '{$locale}' was not found in the database.");
        }

        if ($language?->is_default) {
            $this->error("Cannot remove the default language '{$locale}'. Set another language as default first.");

            return;
        }

        try {
            if ($language) {
                $this->info('Removing translations from database...');
                $translations = Translation::all()->filter(
                    fn ($t) => isset($t->text[$locale]) && ! $t->is_vendor
                );
                foreach ($translations as $translation) {
                    $translation->forgetTranslation($locale);
                }
            }

            Lingua::removeLanguage($locale);

            if ($language) {
                app(Language::class)->reorderLanguages();
            }

            // NOTE: no syncToDatabase() here. Re-syncing after a removal would
            // re-import the locale from lang/{locale} files (and recreate its
            // Language record), silently undoing the removal.
            $this->info("Language '{$locale}' removed successfully.");
        } catch (\Throwable $e) {
            $this->error("Failed to remove language '{$locale}': ".$e->getMessage());
        }
    }
}
