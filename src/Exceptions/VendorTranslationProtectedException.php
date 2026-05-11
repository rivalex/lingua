<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Exceptions;

class VendorTranslationProtectedException extends \RuntimeException
{
    public function __construct(string $message = 'Vendor translations cannot be deleted.')
    {
        parent::__construct($message);
    }
}
