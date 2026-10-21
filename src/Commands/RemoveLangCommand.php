<?php

namespace Rivalex\Lingua\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\Translation;

class RemoveLangCommand extends Command
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
            $this->info('Removing language files via Laravel Lang...');
            Artisan::call('lang:rm '.strtolower($locale).' --force');

            if ($language) {
                $this->info('Removing translations from database...');
                $translations = Translation::whereNotNull('text->'.$locale)->get();
                foreach ($translations as $translation) {
                    $translation->forgetTranslation($locale);
                }

                $this->info('Deleting language record...');
                $language->delete();
                app(Language::class)->reorderLanguages();
            }

            $this->info('Syncing remaining translations to database...');
            app(Translation::class)->syncToDatabase();

            $this->info("Language '{$locale}' removed successfully.");
        } catch (\Throwable $e) {
            $this->error("Failed to remove language '{$locale}': ".$e->getMessage());
        }
    }
}
