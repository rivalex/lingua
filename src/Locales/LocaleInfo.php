<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Locales;

/**
 * Immutable value object representing locale metadata.
 */
final readonly class LocaleInfo
{
    /**
     * @param  string  $code  ISO language code (e.g. 'en', 'ar')
     * @param  string|null  $regional  Regional code (e.g. 'en_US')
     * @param  string  $type  Script type (e.g. 'Latn', 'Arab')
     * @param  string  $name  English display name (e.g. 'English')
     * @param  string  $native  Native display name (e.g. 'Français')
     * @param  string  $direction  Text direction: 'ltr' or 'rtl'
     */
    public function __construct(
        public string $code,
        public ?string $regional,
        public string $type,
        public string $name,
        public string $native,
        public string $direction,
    ) {}
}
