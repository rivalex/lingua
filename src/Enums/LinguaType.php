<?php

namespace Rivalex\Lingua\Enums;

use Flux\Flux;

/**
 * Enum TranslationType
 *
 * This enum represents different types of translation formats,
 * providing a standardized way to manage and label translation content.
 *
 * ### Cases:
 * - `any`: Represents any type of translation without restriction.
 * - `text`: Simple text content with no formatting.
 * - `html`: HTML content with formatting.
 * - `markdown`: Markdown content, which allows lightweight text formatting.
 *
 * ### Methods:
 * - `label()`: Returns a human-readable label for the enum case.
 * - `description()`: Provides a brief description of what the enum case represents.
 * - `color()`: Returns a CSS class string for styling purposes.
 * - `icon()`: Provides an associated FontAwesome class string for icons.
 *
 * ### Example usage:
 * ```
 * use App\Enums\TranslationType;
 *
 * // Get the label for a specific case
 * echo TranslationType::text->label(); // Output: Text
 *
 * // Get a description for the enum case
 * echo TranslationType::html->description(); // Output: HTML with formatting
 *
 * // Retrieve the associated CSS class for styling
 * echo TranslationType::markdown->color(); // Output: text-raid-orange-600 dark:text-raid-orange-500
 *
 * // Get the FontAwesome icon classes
 * echo TranslationType::any->icon(); // Output: fa-duotone fa-light fa-border-all text-zinc-400 dark:text-zinc-600
 *
 * // You can use these enums in conditional logic:
 * $format = TranslationType::html;
 * if ($format === TranslationType::markdown) {
 *     // Markdown-specific logic here
 * }
 * ```
 */
enum LinguaType: string
{
    case any = 'any';
    case text = 'text';
    case html = 'html';
    case markdown = 'markdown';

    public function label(): string
    {
        return match ($this) {
            self::any => 'Any',
            self::text => 'Text',
            self::html => 'HTML',
            self::markdown => 'Markdown',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::any => 'Any type of translation',
            self::text => 'Simple Text with no formatting',
            self::html => 'HTML with formatting',
            self::markdown => 'Markdown with formatting',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::any => 'text-zinc-400 dark:text-zinc-600',
            self::text => 'text-zinc-600 dark:text-zinc-400',
            self::html => 'text-sky-600 dark:text-sky-500',
            self::markdown => 'text-orange-600 dark:text-orange-500'
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::any => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" fill="currentColor" stroke="currentColor"><!--!Font Awesome Pro v7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2026 Fonticons, Inc.--><path d="M384 64c17.7 0 32 14.3 32 32l0 144-176 0 0-176 144 0zm32 208l0 144c0 17.7-14.3 32-32 32l-144 0 0-176 176 0zM208 240L32 240 32 96c0-17.7 14.3-32 32-32l144 0 0 176zM32 272l176 0 0 176-144 0c-17.7 0-32-14.3-32-32l0-144zM64 32C28.7 32 0 60.7 0 96L0 416c0 35.3 28.7 64 64 64l320 0c35.3 0 64-28.7 64-64l0-320c0-35.3-28.7-64-64-64L64 32z"/></svg>',
            self::text => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" fill="currentColor" stroke="currentColor"><!--!Font Awesome Pro v7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2026 Fonticons, Inc.--><path d="M512 96c17.7 0 32 14.3 32 32l0 256c0 17.7-14.3 32-32 32L64 416c-17.7 0-32-14.3-32-32l0-256c0-17.7 14.3-32 32-32l448 0zM64 64C28.7 64 0 92.7 0 128L0 384c0 35.3 28.7 64 64 64l448 0c35.3 0 64-28.7 64-64l0-256c0-35.3-28.7-64-64-64L64 64zM96 224l0 112c0 8.8 7.2 16 16 16s16-7.2 16-16l0-48 64 0 0 48c0 8.8 7.2 16 16 16s16-7.2 16-16l0-112c0-35.3-28.7-64-64-64s-64 28.7-64 64zm96 32l-64 0 0-32c0-17.7 14.3-32 32-32s32 14.3 32 32l0 32zm80-80l0 160c0 8.8 7.2 16 16 16l64 0c30.9 0 56-25.1 56-56 0-17.8-8.3-33.6-21.2-43.9 8.2-9.8 13.2-22.4 13.2-36.1 0-30.9-25.1-56-56-56l-56 0c-8.8 0-16 7.2-16 16zm96 40c0 13.3-10.7 24-24 24l-40 0 0-48 40 0c13.3 0 24 10.7 24 24zM304 320l0-48 48 0c13.3 0 24 10.7 24 24s-10.7 24-24 24l-48 0z"/></svg>',
            self::html => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" fill="currentColor" stroke="currentColor"><!--!Font Awesome Free v7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path d="M0 32L34.9 427.8 191.5 480 349.1 427.8 384 32 0 32zM308.2 159.9l-183.8 0 4.1 49.4 175.6 0-13.6 148.4-97.9 27 0 .3-1.1 0-98.7-27.3-6-75.8 47.7 0 3.5 38.1 53.5 14.5 53.7-14.5 6-62.2-166.9 0-12.8-145.6 241.1 0-4.4 47.7z"/></svg>',
            self::markdown => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512" fill="currentColor" stroke="currentColor"><!--!Font Awesome Free v7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path d="M593.8 59.1l-547.6 0C20.7 59.1 0 79.8 0 105.2L0 406.7c0 25.5 20.7 46.2 46.2 46.2l547.7 0c25.5 0 46.2-20.7 46.1-46.1l0-301.6c0-25.4-20.7-46.1-46.2-46.1zM338.5 360.6l-61.5 0 0-120-61.5 76.9-61.5-76.9 0 120-61.7 0 0-209.2 61.5 0 61.5 76.9 61.5-76.9 61.5 0 0 209.2 .2 0zm135.3 3.1l-92.3-107.7 61.5 0 0-104.6 61.5 0 0 104.6 61.5 0-92.2 107.7z"/></svg>'
        };
    }

    public function iconColor(int $size = 4): string
    {
        $iconSize = 'h-'.$size.' w-'.$size;

        return match ($this) {
            self::any => '<div class="'.$iconSize.' text-zinc-400 dark:text-zinc-600">'.$this->icon().'</div>',
            self::text => '<div class="'.$iconSize.' text-zinc-600 dark:text-zinc-400">'.$this->icon().'</div>',
            self::html => '<div class="'.$iconSize.' text-sky-600 dark:text-sky-500">'.$this->icon().'</div>',
            self::markdown => '<div class="'.$iconSize.' text-orange-600 dark:text-orange-500">'.$this->icon().'</div>'
        };
    }

    public function labelWithIcon(): string
    {
        return '<div class="flex flex-row items-center gap-2 '.$this->color().'">'.$this->icon().'<span>'.$this->label().'</span></div>';
    }

    public static function selectValues(): array
    {
        $array = [];
        foreach (self::cases() as $value) {
            if ($value === self::any) {
                continue;
            }
            $array[] = [
                'value' => $value->value,
                'label' => Flux::pro() ? $value->labelWithIcon() : $value->label(),
            ];
        }

        return $array;
    }
}
