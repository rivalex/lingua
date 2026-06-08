<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Traits;

use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;

trait ModalsConfirm
{
    use Modals;

    #[Validate('required|string|uppercase')]
    public string $control = '';

    public string $confirm = '';

    protected function validateConfirmControl(): void
    {
        $this->validate([
            'control' => ['required', 'string', 'uppercase', Rule::in([$this->confirm])],
        ]);
    }

    public function close(): void
    {
        $this->reset('control');
        $this->closeModal();
    }
}
