<?php

namespace Rivalex\Lingua\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
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
            $locales = Language::all()->pluck('code')->toArray();

            if (empty($locales)) {
                $this->info('No languages installed. Skipping update.');

                return;
            }

            Artisan::call('lang:update', ['locales' => $locales]);
            $this->info('Language files updated. Syncing translations to database...');
            app(Translation::class)->syncToDatabase();
            $this->info('Translations updated and synced to database successfully.');
        } catch (\Throwable $e) {
            $this->error('Failed to update language files: '.$e->getMessage());
        }
    }
}
