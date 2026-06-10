<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Support;

use Illuminate\Filesystem\Filesystem;

/**
 * Publishes only the migration files required by the given storage driver.
 *
 * Unlike Spatie's bulk `publishMigrations()` which publishes everything under
 * the `lingua-migrations` tag, this class lets the install command and the
 * driver-switch command copy only the migrations that are actually needed:
 *
 *  - `file`     driver: languages + lingua_settings (no language_lines)
 *  - `database` driver: all three tables
 *
 * Idempotent: basenames already present in the target directory are skipped.
 */
final class MigrationPublisher
{
    /**
     * Package migration basenames required per driver (in creation order).
     *
     * @var array<string, list<string>>
     */
    public const PER_DRIVER = [
        'database' => [
            'create_languages_table',
            'create_lingua_settings_table',
            'create_language_lines_table',
        ],
        'file' => [
            'create_languages_table',
            'create_lingua_settings_table',
        ],
    ];

    public function __construct(private readonly Filesystem $files) {}

    /**
     * Copy the migrations needed by the given driver into the target directory.
     *
     * Each file gets a timestamp prefix (`Y_m_d_His` + per-file second increment)
     * so the creation order is stable. Basenames already present are skipped.
     *
     * @param  string  $driver  `database` or `file`
     * @param  string|null  $targetDir  Destination directory; defaults to `database_path('migrations')`.
     * @return list<string> Basenames that were actually published (empty when all were already present).
     */
    public function publishFor(string $driver, ?string $targetDir = null): array
    {
        $dest = $targetDir ?? database_path('migrations');
        $basenames = self::PER_DRIVER[$driver] ?? self::PER_DRIVER['database'];
        $published = [];
        $ts = time();

        if (! $this->files->isDirectory($dest)) {
            $this->files->makeDirectory($dest, 0755, true);
        }

        foreach ($basenames as $i => $basename) {
            if ($this->isBasenamePublished($basename, $dest)) {
                continue;
            }

            $source = $this->findSource($basename);

            if ($source === null) {
                continue;
            }

            $timestamp = date('Y_m_d_His', $ts + $i);
            $this->files->copy($source, $dest.DIRECTORY_SEPARATOR.$timestamp.'_'.$basename.'.php');
            $published[] = $basename;
        }

        return $published;
    }

    /**
     * Return true when every migration required by the given driver
     * already exists in the target directory.
     *
     * @param  string  $driver  `database` or `file`
     * @param  string|null  $targetDir  Defaults to `database_path('migrations')`.
     */
    public function isPublishedFor(string $driver, ?string $targetDir = null): bool
    {
        $dest = $targetDir ?? database_path('migrations');
        $basenames = self::PER_DRIVER[$driver] ?? self::PER_DRIVER['database'];

        foreach ($basenames as $basename) {
            if (! $this->isBasenamePublished($basename, $dest)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Return true when a file ending with `_{basename}.php` already exists in $dir.
     */
    private function isBasenamePublished(string $basename, string $dir): bool
    {
        if (! $this->files->isDirectory($dir)) {
            return false;
        }

        foreach ($this->files->files($dir) as $file) {
            if (str_ends_with($file->getFilename(), '_'.$basename.'.php')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Locate the package source file for the given basename.
     */
    private function findSource(string $basename): ?string
    {
        $sourceDir = __DIR__.'/../../database/migrations';

        foreach ($this->files->files($sourceDir) as $file) {
            if ($file->getFilenameWithoutExtension() === $basename) {
                return $file->getPathname();
            }
        }

        return null;
    }
}
