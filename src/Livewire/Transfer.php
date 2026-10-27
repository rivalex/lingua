<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Livewire;

use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;
use Rivalex\Lingua\Traits\AuthorizesLingua;

/**
 * Full-page host component for the Translation Transfer (import/export) area.
 *
 * Renders two nested child components: Export and Import.
 * Follows the same render() + layout-config pattern as Statistics and Settings.
 */
#[Title('Translation Transfer')]
final class Transfer extends Component
{
    use AuthorizesLingua;

    /**
     * Render the transfer page view with optional custom layout.
     */
    public function render(): View
    {
        $view = view('lingua::transfer');
        $layout = config('lingua.layout');

        return $layout ? $view->layout($layout) : $view;
    }
}
