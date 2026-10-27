<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Transfer;

use OpenSpout\Writer\XLSX\Writer;

/**
 * Runtime detection of the optional OpenSpout dependency.
 *
 * OpenSpout is listed in composer.json `suggest` only - never in `require`.
 * This class gates all XLSX/ODS functionality behind a class_exists probe so
 * the package works without it (CSV and JSON always available).
 */
final class SpreadsheetSupport
{
    private static ?bool $override = null;

    /**
     * Returns true when openspout/openspout is installed and the XLSX writer is available.
     */
    public static function available(): bool
    {
        return self::$override ?? class_exists(Writer::class);
    }

    /**
     * Force the detected availability; null restores the real detection.
     *
     * @internal Test seam, so the suite covers the "OpenSpout absent" path while OpenSpout is installed.
     */
    public static function fake(?bool $available): void
    {
        self::$override = $available;
    }
}
