<?php

namespace Rivalex\Lingua\Livewire\Language;

use Livewire\Attributes\On;
use Livewire\Component;
use Rivalex\Lingua\Models\Language;

class Row extends Component
{
    public Language $language;

    public bool $showFlags = true;

    public function mount(int $languageId): void
    {
        $this->language = Language::withStatistics()->find($languageId);
    }

    #[On('refreshLanguageRows')]
    public function refreshLanguageRows(): void
    {
        $this->language = Language::withStatistics()->find($this->language->id);
    }

    public function placeholder(): string
    {
        return <<<'HTML'
        <flux:table.row>
            <flux:table.cell>
                <flux:skeleton.group animate="shimmer" class="flex items-center gap-4">
                    <flux:skeleton class="size-10 rounded-full"/>
                    <div class="flex-1">
                        <flux:skeleton.line/>
                        <flux:skeleton.line class="w-1/2"/>
                    </div>
                </flux:skeleton.group>
            </flux:table.cell>
            <flux:table.cell>
                <flux:skeleton.group animate="shimmer">
                    <flux:skeleton.line class="w-1/4"/>
                    <flux:skeleton.line/>
                </flux:skeleton.group>
            </flux:table.cell>
            <flux:table.cell align="center">
                <flux:skeleton animate="shimmer" class="size-10 rounded-md"/>
            </flux:table.cell>
        </flux:table.row>
        HTML;
    }

    public function render()
    {
        return view('lingua::language.row');
    }
}
