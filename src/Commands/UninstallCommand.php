<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Schema;
use Rivalex\Lingua\Models\Translation;
use Rivalex\Lingua\Support\MigrationPublisher;

/**
 * Safely remove Lingua from a host application.
 *
 * Steps (each destructive step is confirmed unless --force is given):
 *  1. Warn about what will be removed.
 *  2. Export DB translations to lang/ files (database driver only, no data loss).
 *  3. Drop the three Lingua database tables.
 *  4. Remove published config/lingua.php (unless --keep-config).
 *  5. Remove published views + migration files (unless --keep-published).
 *  6. Leave lang/ files intact (they may contain the exported translations).
 *  7. Remind the developer to run `composer remove rivalex/lingua`.
 */
final class UninstallCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'lingua:uninstall
        {--force : Skip all confirmations}
        {--keep-config : Keep published config/lingua.php}
        {--keep-published : Keep published views and migration files}';

    /**
     * @var string
     */
    protected $description = 'Safely remove Lingua from your application (exports translations first)';

    public function __construct(private readonly Filesystem $files)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->warn('This will remove Lingua\'s database tables and published assets from your application.');
        $this->warn('Your lang/ translation files will NOT be modified.');
        $this->newLine();

        if (! $this->option('force') && ! $this->confirm('Do you wish to continue?')) {
            $this->info('Uninstall aborted.');

            return self::SUCCESS;
        }

        $this->exportTranslationsIfNeeded();
        $this->dropTables();
        $this->removePublishedConfig();
        $this->removePublishedFiles();

        $this->newLine();
        $this->info('Lingua has been uninstalled successfully.');
        $this->info('Your lang/ files have been preserved.');
        $this->newLine();
        $this->line('Run <comment>composer remove rivalex/lingua</comment> to finish removing the package.');

        return self::SUCCESS;
    }

    /**
     * When running in database mode, export all translations to lang/ files
     * before dropping the language_lines table so no data is lost.
     */
    private function exportTranslationsIfNeeded(): void
    {
        if (linguaStorageDriver() !== 'database') {
            return;
        }

        if (! Schema::hasTable('language_lines')) {
            return;
        }

        $this->info('Exporting translations to lang/ files before removal...');
        Translation::syncToLocal();
        $this->info('Translations exported to lang/ successfully.');
        $this->newLine();
    }

    /**
     * Drop all three Lingua tables (language_lines first, then dependants).
     */
    private function dropTables(): void
    {
        if (! $this->option('force') && ! $this->confirm('Drop Lingua database tables (languages, language_lines, lingua_settings)?')) {
            $this->line('Skipped: database tables were not removed.');

            return;
        }

        Schema::dropIfExists('language_lines');
        Schema::dropIfExists('lingua_settings');
        Schema::dropIfExists('languages');

        $this->info('Database tables dropped.');
    }

    /**
     * Remove the published config file from the host application.
     */
    private function removePublishedConfig(): void
    {
        if ($this->option('keep-config')) {
            return;
        }

        $configFile = config_path('lingua.php');

        if (! $this->files->exists($configFile)) {
            return;
        }

        if (! $this->option('force') && ! $this->confirm('Remove published config file (config/lingua.php)?')) {
            $this->line('Skipped: config/lingua.php was not removed.');

            return;
        }

        $this->files->delete($configFile);
        $this->info('config/lingua.php removed.');
    }

    /**
     * Remove published views and the host's published migration files.
     */
    private function removePublishedFiles(): void
    {
        if ($this->option('keep-published')) {
            return;
        }

        if (! $this->option('force') && ! $this->confirm('Remove published views and migration files?')) {
            $this->line('Skipped: published views and migrations were not removed.');

            return;
        }

        $viewsDir = resource_path('views/vendor/lingua');

        if ($this->files->isDirectory($viewsDir)) {
            $this->files->deleteDirectory($viewsDir);
            $this->info('Published views removed.');
        }

        $this->removeLinguaMigrations();
    }

    /**
     * Delete host migration files that match any of Lingua's three migration basenames.
     */
    private function removeLinguaMigrations(): void
    {
        $migrationsDir = database_path('migrations');

        if (! $this->files->isDirectory($migrationsDir)) {
            return;
        }

        /** @var list<string> $basenames */
        $basenames = array_unique(array_merge(...array_values(MigrationPublisher::PER_DRIVER)));

        foreach ($this->files->files($migrationsDir) as $file) {
            foreach ($basenames as $basename) {
                if (str_ends_with($file->getFilename(), '_'.$basename.'.php')) {
                    $this->files->delete($file->getPathname());
                    $this->line("Removed migration: {$file->getFilename()}");
                    break;
                }
            }
        }
    }
}
