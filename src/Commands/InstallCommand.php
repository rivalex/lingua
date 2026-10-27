<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Schema;
use Rivalex\Lingua\Database\Seeders\LinguaSeeder;
use Rivalex\Lingua\Lingua;
use Rivalex\Lingua\Support\MigrationPublisher;

use function Laravel\Prompts\select;

/**
 * Interactive installer: storage driver choice, config publish, the migrations
 * the chosen driver needs, optional migrate, then seeding.
 */
final class InstallCommand extends Command
{
    protected $signature = 'lingua:install';

    protected $description = 'Install lingua';

    public function handle(Filesystem $files): int
    {
        $this->info('Hello, and welcome to Lingua!');
        $this->info('Starting the installation process...');

        // Arrow-key selector (falls back to numbered choice in non-interactive/CI).
        $driver = select(
            label: 'Translation storage driver?',
            options: ['database', 'file'],
            default: 'database',
            hint: '"database" uses the language_lines table. "file" writes translations directly to lang/.',
        );

        $this->info("Set LINGUA_STORAGE_DRIVER={$driver} in your .env, then run 'php artisan config:clear'.");

        if ($driver === 'file') {
            $this->warn('FILE DRIVER: translations are written directly to lang/.');
            $this->warn('Your deploy pipeline (Forge/Envoyer/CI) may overwrite these files');
            $this->warn('or fail on a dirty working tree. Commit lang/ changes deliberately.');
            $this->warn('See docs: Storage Drivers > File mode caveats.');
        }

        $this->comment('Publishing config...');
        $this->callSilently('vendor:publish', ['--tag' => 'lingua-config']);

        // Publish only the migrations needed for the chosen driver, then offer to run them.
        $newlyPublished = (new MigrationPublisher($files))->publishFor($driver);

        if (! empty($newlyPublished)) {
            $this->info('Published migrations: '.implode(', ', $newlyPublished).'.');
        }

        if ($this->confirm('Would you like to run the migrations now?')) {
            $this->call('migrate');
        }

        // Seed / bootstrap once the tables are available.
        $tablesCreated = Schema::hasTable('languages') && Schema::hasTable('lingua_settings');

        if ($tablesCreated) {
            $this->info('Installing Lingua package...');

            if ($driver === 'database') {
                $this->call('db:seed', ['--class' => LinguaSeeder::class]);
            } elseif ($driver === 'file') {
                try {
                    Lingua::installDefaultLanguage();
                    $this->info('Default language installed and lang/ files seeded.');
                } catch (\Throwable $e) {
                    $this->warn('Could not seed default language: '.$e->getMessage());
                }
            }

            $this->info('Lingua package installed successfully!');
        } else {
            $this->info('Lingua package installed successfully!');
            $this->info('Please run "php artisan migrate" to create the database tables.');

            if ($driver === 'database') {
                $this->info('Then run "php artisan db:seed --class='.LinguaSeeder::class.'" to populate default data.');
            } elseif ($driver === 'file') {
                $this->info('After migrating, run "php artisan lingua:storage file" to seed default lang/ files.');
            }
        }

        $this->configureTailwind($files);

        $gate = Lingua::gate();

        if ($gate !== null) {
            $this->warn('The admin UI is denied outside the local environment until your app defines the "'.$gate.'" ability. See README: "Securing the admin UI".');
        }

        $this->info('lingua has been installed!');

        return self::SUCCESS;
    }

    /**
     * Add the Tailwind @source line for Lingua's views to the host stylesheet.
     * Changes nothing without confirmation, in non-interactive mode or when the file is missing.
     */
    private function configureTailwind(Filesystem $files): void
    {
        $path = base_path('resources/css/app.css');
        $sourceLine = "@source '../../vendor/rivalex/lingua/resources/views';";

        $this->comment('Configuring Tailwind...');

        if ($files->exists($path)) {
            $css = $files->get($path);

            if (str_contains($css, 'vendor/rivalex/lingua/resources/views')) {
                $this->info('resources/css/app.css already scans the Lingua views.');

                return;
            }

            if ($this->input->isInteractive()
                && $this->confirm('Add the Lingua @source line to resources/css/app.css?', true)) {
                $files->put($path, $this->insertSourceLine($css, $sourceLine));
                $this->info('Added the Lingua @source line to resources/css/app.css.');
                $this->line('Rebuild your assets so the panel is styled: npm run build');

                return;
            }
        }

        $this->warn('Add this line to your resources/css/app.css, next to the Flux import:');
        $this->line('    '.$sourceLine);
        $this->line('See the README section "Styling".');
        $this->line('Then rebuild your assets: npm run build');
    }

    private function insertSourceLine(string $css, string $sourceLine): string
    {
        $eol = str_contains($css, "\r\n") ? "\r\n" : "\n";
        $lines = explode($eol, $css);

        $lastSource = null;
        $lastImport = null;

        foreach ($lines as $index => $line) {
            $trimmed = ltrim($line);

            if (str_starts_with($trimmed, '@source')) {
                $lastSource = $index;
            } elseif (str_starts_with($trimmed, '@import')) {
                $lastImport = $index;
            }
        }

        $after = $lastSource ?? $lastImport;

        if ($after === null) {
            return $sourceLine.$eol.$css;
        }

        array_splice($lines, $after + 1, 0, [$sourceLine]);

        return implode($eol, $lines);
    }
}
