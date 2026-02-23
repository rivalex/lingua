<?php

namespace Rivalex\Lingua\Commands;

use Illuminate\Console\Command;

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
    public function handle(): void {}
}
