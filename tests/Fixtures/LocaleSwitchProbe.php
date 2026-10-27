<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Tests\Fixtures;

use Livewire\Component;
use Rivalex\Lingua\Traits\ManagesLocale;

/**
 * Test-only component: mounts ManagesLocale with a server-side currentUrl,
 * because the property is locked and a test cannot set it from the client.
 */
final class LocaleSwitchProbe extends Component
{
    use ManagesLocale;

    public function mount(string $url): void
    {
        $this->initLocaleState();
        $this->currentUrl = $url;
    }

    public function render(): string
    {
        return '<div></div>';
    }
}
