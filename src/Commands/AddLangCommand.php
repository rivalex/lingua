<?php

namespace Rivalex\Lingua\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use LaravelLang\Locales\Facades\Locales;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\Translation;

class AddLangCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lingua:add {locale : The locale code to add (e.g. it, es, fr)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add a new language to the application (installs files and syncs to database)';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $locale = $this->argument('locale');

        $this->info("Adding language: {$locale}...");

        try {
            $newLanguage = Locales::info(locale: $locale);

            $this->info('Installing language files via Laravel Lang...');
            Artisan::call('lang:add '.$locale);

            $this->info('Creating language record in database...');
            app(Language::class)->updateOrCreate(
                ['code' => $newLanguage->code, 'regional' => $newLanguage->regional],
                [
                    'type' => $newLanguage->type,
                    'name' => $newLanguage->locale->name,
                    'native' => $newLanguage->native,
                    'direction' => $newLanguage->direction,
                    'is_default' => false,
                ]
            );

            $this->info('Syncing translations to database...');
            app(Translation::class)->syncToDatabase();

            $this->info("Language '{$locale}' added successfully.");
        } catch (\Throwable $e) {
            $this->error("Failed to add language '{$locale}': ".$e->getMessage());
        }
    }
}
