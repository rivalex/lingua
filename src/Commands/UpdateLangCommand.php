<?php

namespace Rivalex\Lingua\Commands;

use Illuminate\Console\Command;

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
    public function handle(): void {}
}
