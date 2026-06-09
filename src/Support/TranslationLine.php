<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Support;

use Rivalex\Lingua\Enums\LinguaType;

/**
 * Immutable DTO representing a single translation key across all locales.
 *
 * Driver-neutral: produced by both DatabaseRepository and FileRepository.
 * Livewire components identify rows by identity(), not by DB primary key.
 */
final readonly class TranslationLine
{
    /**
     * @param  array<string, string>  $text  locale => value map
     * @param  int|string|null  $id  DB primary key (database-mode); null in file-mode
     */
    public function __construct(
        public string $group,
        public string $key,
        public string $groupKey,
        public LinguaType $type,
        public array $text,
        public bool $isVendor,
        public ?string $vendor,
        public int|string|null $id = null,
    ) {}

    /**
     * Stable Livewire identity: "{group}|{key}|{isVendor?1:0}|{vendor}".
     * Null-safe; never contains a DB primary key so it works in file-mode.
     */
    public function identity(): string
    {
        return $this->group.'|'.$this->key.'|'.($this->isVendor ? '1' : '0').'|'.($this->vendor ?? '');
    }

    /**
     * Return the translated value for $locale, or empty string when absent.
     */
    public function value(string $locale): string
    {
        return $this->text[$locale] ?? '';
    }
}
