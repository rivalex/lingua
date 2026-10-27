<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Traits;

use Illuminate\Support\Facades\Gate;
use Rivalex\Lingua\Lingua;

/**
 * Authorizes the configured Lingua gate for an admin Livewire component: it
 * checks the gate on boot, every call, every update and every render, so components
 * embedded outside the Lingua routes are protected as well. Livewire calls the
 * hooks automatically. The boot hook is skipped for lazy-loaded components, so the
 * call, updating and rendering hooks are what protect a request that replays a lazy snapshot.
 */
trait AuthorizesLingua
{
    public function bootAuthorizesLingua(): void
    {
        $this->authorizeLingua();
    }

    /**
     * Livewire passes the named arguments methodName, params, returnEarly and metadata.
     */
    public function callAuthorizesLingua(mixed ...$arguments): void
    {
        $this->authorizeLingua();
    }

    /**
     * Livewire passes the property path and the new value.
     */
    public function updatingAuthorizesLingua(mixed ...$arguments): void
    {
        $this->authorizeLingua();
    }

    /**
     * Livewire passes the named arguments view and data, before the component is rendered.
     */
    public function renderingAuthorizesLingua(mixed ...$arguments): void
    {
        $this->authorizeLingua();
    }

    private function authorizeLingua(): void
    {
        $gate = Lingua::gate();

        if ($gate !== null) {
            Gate::authorize($gate);
        }
    }
}
