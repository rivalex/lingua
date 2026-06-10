<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Commands;

use Illuminate\Console\Command;
use Rivalex\Lingua\Facades\Lingua;
use Rivalex\Lingua\Models\Language;

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
    protected $description = 'Re-sync local language files to the database for all installed languages';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Updating languages...');

        try {
            $dbLocales = Language::all()->pluck('code')->toArray();

            if (empty($dbLocales)) {
                $this->info('No languages installed. Skipping update.');

                return;
            }

            Lingua::updateLanguages();
            $this->info('Translations updated and synced to database successfully.');
        } catch (\Throwable $e) {
            $this->error('Failed to update languages: '.$e->getMessage());
        }
    }
}
