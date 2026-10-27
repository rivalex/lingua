<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Contracts;

use Rivalex\Lingua\Support\TranslationLine;

/**
 * Optional capability of a TranslationRepository: write many values in one operation.
 *
 * The import uses it when the repository implements it, so that file mode reads and writes each
 * lang file once and database mode uses one transaction, instead of one write per value.
 */
interface WritesTranslationBatches
{
    /**
     * Set the value of several keys for one locale each.
     *
     * Nothing is written when a change is rejected: the first rejected change throws and the
     * stored values stay as they were.
     *
     * @param  list<array{line: TranslationLine, locale: string, value: string}>  $changes
     */
    public function setValues(array $changes): void;
}
