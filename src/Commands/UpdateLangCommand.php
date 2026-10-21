<?php

namespace Rivalex\Lingua\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use LaravelLang\Locales\Facades\Locales;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\Translation;

class UpdateLangCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lingua:update-lang';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the local language files via Laravel Lang and sync translations to database';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Updating language files via Laravel Lang...');

        try {
            $dbLocales = Language::all()->pluck('code')->toArray();

            if (empty($dbLocales)) {
                $this->info('No languages installed. Skipping update.');

                return;
            }

            // Remove filesystem locales not tracked in the database so that
            // lang:update only refreshes DB-managed locales.
            foreach (Locales::raw()->installed() as $locale) {
                if (! in_array($locale, $dbLocales)) {
                    Artisan::call('lang:rm '.$locale.' --force');
                }
            }

            Artisan::call('lang:update');
            $this->info('Language files updated. Syncing translations to database...');
            app(Translation::class)->syncToDatabase();
            $this->info('Translations updated and synced to database successfully.');
        } catch (\Throwable $e) {
            $this->error('Failed to update language files: '.$e->getMessage());
        }
    }
}
