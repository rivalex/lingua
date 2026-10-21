<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Traits;

use Flux\Flux;

trait Modals
{
    public string $modalName = '';

    public function closeModal(): void
    {
        if ($this->modalName === '') {
            $this->closeModals();

            return;
        }

        Flux::modal($this->modalName)->close();
    }

    public function closeModals(): void
    {
        Flux::modals()->close();
    }
}
