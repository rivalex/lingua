<?php

namespace Rivalex\Lingua\Commands;

use Illuminate\Console\Command;
use Rivalex\Lingua\Models\Translation;

class SyncToDatabaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lingua:sync-to-database';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync translations from local files to database';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Syncing translations from local files to database...');

        try {
            app(Translation::class)->syncToDatabase();
            $this->info('Translations synced to database successfully.');
        } catch (\Throwable $e) {
            $this->error('Failed to sync translations to database: '.$e->getMessage());
        }
    }
}
