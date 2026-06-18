<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Transfer\Format;

/**
 * Thrown when an XLSX or ODS format is requested but openspout/openspout is not installed.
 */
final class SpreadsheetUnavailableException extends \RuntimeException
{
    public function __construct(string $format)
    {
        parent::__construct(
            "The '{$format}' format requires openspout/openspout. Install it with: composer require openspout/openspout"
        );
    }
}
