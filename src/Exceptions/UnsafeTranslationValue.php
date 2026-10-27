<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Exceptions;

use InvalidArgumentException;
use Rivalex\Lingua\Enums\LinguaType;

final class UnsafeTranslationValue extends InvalidArgumentException
{
    public static function forType(LinguaType $type): self
    {
        return new self(self::messageFor($type));
    }

    public static function messageFor(LinguaType $type): string
    {
        return "[Lingua] The value contains HTML that is not allowed for a {$type->value} translation.";
    }
}
