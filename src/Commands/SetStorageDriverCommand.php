<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Commands;

use Illuminate\Console\Command;
use Rivalex\Lingua\Enums\LinguaType;
use Rivalex\Lingua\Models\Translation;

final class SetStorageDriverCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'lingua:storage
        {driver : Target driver: database|file}
        {--force : Skip type-loss confirmation when switching to file}
        {--write-env : Attempt to write LINGUA_STORAGE_DRIVER to .env}';

    /**
     * @var string
     */
    protected $description = 'Switch the translation storage driver between database and file';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $driver = $this->argument('driver');

        if (! in_array($driver, ['database', 'file'], true)) {
            $this->error("Invalid driver '{$driver}'. Must be 'database' or 'file'.");

            return self::FAILURE;
        }

        $current = config('lingua.storage.driver', 'database');

        if ($driver === $current) {
            $this->info("Driver already set to '{$driver}'. Nothing to do.");

            return self::SUCCESS;
        }

        if ($driver === 'file') {
            $typed = Translation::select('type')->get()
                ->filter(fn ($r) => $r->type === LinguaType::html || $r->type === LinguaType::markdown)
                ->count();

            if ($typed > 0 && ! $this->option('force')) {
                $this->warn("{$typed} strings use html/markdown — type info is LOST in file-mode.");

                if (! $this->confirm('Proceed?')) {
                    $this->info('Aborted.');

                    return self::SUCCESS;
                }
            }

            $this->info('Syncing translations to local files...');
            app(Translation::class)->syncToLocal();
        } else {
            $this->info('Syncing translations from local files to database...');
            app(Translation::class)->syncToDatabase();
        }

        $this->applyDriver($driver);

        return self::SUCCESS;
    }

    /**
     * Instruct the user to set the driver in .env, or write it directly when --write-env is given.
     */
    private function applyDriver(string $driver): void
    {
        if ($this->option('write-env')) {
            $envPath = base_path('.env');

            if (file_exists($envPath) && is_writable($envPath)) {
                $content = file_get_contents($envPath);

                if (str_contains($content, 'LINGUA_STORAGE_DRIVER=')) {
                    $content = (string) preg_replace('/^LINGUA_STORAGE_DRIVER=.*/m', "LINGUA_STORAGE_DRIVER={$driver}", $content);
                } else {
                    $content .= "\nLINGUA_STORAGE_DRIVER={$driver}\n";
                }

                file_put_contents($envPath, $content);
                $this->info("LINGUA_STORAGE_DRIVER={$driver} written to .env.");
                $this->info("Run 'php artisan config:clear' to apply.");

                return;
            }

            $this->warn('.env not found or not writable. Set manually:');
        }

        $this->info("Set LINGUA_STORAGE_DRIVER={$driver} in your .env, then run 'php artisan config:clear'.");
    }
}
