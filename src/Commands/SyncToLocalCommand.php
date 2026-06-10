<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Commands;

use Illuminate\Console\Command;
use Rivalex\Lingua\Models\Translation;

final class SyncToLocalCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lingua:sync-to-local {--force : Override file-mode no-op guard}';

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
        if (linguaIsFileMode()) {
            if (! $this->option('force')) {
                $this->warn('Refusing: file-mode active — DB is not the source of truth. Re-run with --force to override.');

                return;
            }

            if (! $this->confirm('File-mode is active. DB may be empty and overwrite your files. Proceed?')) {
                return;
            }
        }

        $this->info('Syncing translations from database to local files...');

        try {
            app(Translation::class)->syncToLocal();
            $this->info('Translations synced to local files successfully.');
        } catch (\Throwable $e) {
            $this->error('Failed to sync translations to local files: '.$e->getMessage());
        }
    }
}
