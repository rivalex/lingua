<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Commands;

use Illuminate\Console\Command;
use Rivalex\Lingua\Facades\Lingua;
use Rivalex\Lingua\Models\Translation;

final class AddLangCommand extends Command
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
            $this->info('Creating language record in database...');
            Lingua::addLanguage($locale);

            $this->info('Syncing translations to database...');
            app(Translation::class)->syncToDatabase();

            $this->info("Language '{$locale}' added successfully.");
        } catch (\Throwable $e) {
            $this->error("Failed to add language '{$locale}': ".$e->getMessage());
        }
    }
}
