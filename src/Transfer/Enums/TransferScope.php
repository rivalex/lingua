<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Transfer\Enums;

enum TransferScope: string
{
    case bilingual = 'bilingual';
    case multiLocale = 'multi_locale';
    case jsonNative = 'json_native';
}
