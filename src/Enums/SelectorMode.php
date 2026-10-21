<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Enums;

/**
 * Enum SelectorMode
 *
 * Represents the rendering mode for the Lingua language selector component.
 *
 * ### Cases:
 * - `Sidebar`:  Renders the selector in a sidebar panel.
 * - `Modal`:    Renders the selector in a modal dialog.
 * - `Dropdown`: Renders the selector as a dropdown menu.
 * - `Headless`: No built-in rendering; requires manual implementation by the host application.
 *
 * ### Example usage:
 * ```php
 * use Rivalex\Lingua\Enums\SelectorMode;
 *
 * $mode = SelectorMode::Sidebar;
 * echo $mode->label(); // Output: Sidebar
 *
 * // Iterate all modes for a select input
 * foreach (SelectorMode::selectValues() as $option) {
 *     echo $option['value'] . ': ' . $option['label'];
 * }
 * ```
 */
enum SelectorMode: string
{
    case Sidebar = 'sidebar';
    case Modal = 'modal';
    case Dropdown = 'dropdown';
    case Headless = 'headless';

    /**
     * Returns a human-readable label for the enum case.
     */
    public function label(): string
    {
        return match ($this) {
            self::Sidebar => 'Sidebar',
            self::Modal => 'Modal',
            self::Dropdown => 'Dropdown',
            self::Headless => 'Headless',
        };
    }

    /**
     * Returns a brief description of what the mode represents.
     */
    public function description(): string
    {
        return match ($this) {
            self::Sidebar => 'Opens in a slide-over sidebar panel',
            self::Modal => 'Opens in a centred modal dialog',
            self::Dropdown => 'Opens as an inline dropdown menu',
            self::Headless => 'No built-in UI — render manually in your layout',
        };
    }

    /**
     * Returns all modes as value/label pairs suitable for a select input.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function selectValues(): array
    {
        return array_map(
            fn (self $case) => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }
}
