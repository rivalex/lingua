<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Exceptions;

use RuntimeException;

final class InvalidConfiguration extends RuntimeException
{
    public static function invalidModel(string $class): self
    {
        return new self(
            "Class `{$class}` is not a valid translation model. ".
            'It must be an Eloquent Model with a `getTranslationsForGroup()` static method.'
        );
    }
}
