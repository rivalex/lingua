<?php

namespace Rivalex\Lingua\Commands;

use Illuminate\Console\Command;
use Rivalex\Lingua\Models\Translation;

class SyncToLocalCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lingua:sync-to-local';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync translations from remote Database to local files';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Syncing translations from database to local files...');

        try {
            app(Translation::class)->syncToLocal();
            $this->info('Translations synced to local files successfully.');
        } catch (\Throwable $e) {
            $this->error('Failed to sync translations to local files: '.$e->getMessage());
        }
    }
}
