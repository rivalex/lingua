<?php

namespace Rivalex\Lingua\Traits;

use Livewire\Attributes\Validate;

trait ModalsConfirm
{
    use Modals;

    #[Validate('required|string|uppercase')]
    public string $control = '';

    public string $confirm = '';

    public function close(): void
    {
        $this->reset('control');
        $this->closeModal();
    }
}
