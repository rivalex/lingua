<?php

namespace Rivalex\Lingua\Traits;

use Flux\Flux;
use Livewire\Attributes\Validate;

trait Modals
{
    public string $modalName = '';

    public function openModal(): void
    {
        Flux::modal($this->modalName)->show();
    }

    public function closeModal(): void
    {
        if (!$this->modalName) {
            $this->closeModals();
        }
        Flux::modal($this->modalName)->close();
    }

    public function closeModals(): void
    {
        Flux::modals()->close();
    }
}
