<?php

namespace Rivalex\Lingua\Commands;

use Illuminate\Console\Command;

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

	}
}
