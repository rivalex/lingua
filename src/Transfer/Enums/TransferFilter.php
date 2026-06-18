<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Transfer\Enums;

enum TransferFilter: string
{
    case all = 'all';
    case onlyMissing = 'only_missing';
}
