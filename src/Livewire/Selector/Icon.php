<?php

namespace Rivalex\Lingua\Livewire\Selector;

use Livewire\Component;
use Rivalex\Lingua\Models\LinguaSetting;

class Icon extends Component
{
    public string $locale;

    public bool $showFlags = true;

    public bool $square = false;

    public string $languageFlag = '';

    public string $size = 'md';

    public string $customIcon = '';

    public array $textIconClasses = [];

    public function mount($showFlags = null): void
    {
        $this->locale = linguaLanguageCode($this->locale ?? app()->currentLocale());
        $this->showFlags = ($showFlags !== null)
            ? (bool) $showFlags
            : (bool) LinguaSetting::get(LinguaSetting::KEY_SHOW_FLAGS, config('lingua.selector.show_flags', true));
        if ($this->showFlags) {
            $this->buildFlag();
        } else {
            $this->buildDefault();
        }
    }

    protected function buildDefault(): void
    {
        $this->textIconClasses = [
            'flex items-center justify-center rounded-sm',
            'font-light uppercase',
            $this->getTextSize(),
            'bg-zinc-200',
            'border border-zinc-300',
            $this->getIconSize(),
        ];
    }

    protected function getFlagName(): string
    {
        return match ($this->square) {
            true => 'flag-language-'.strtolower($this->locale),
            false => 'flag-circle-language-'.strtolower($this->locale)
        };
    }

    protected function buildFlag(): void
    {
        $this->languageFlag = svg($this->getFlagName(), $this->getIconSize())->toHtml();
    }

    protected function getTextSize(): string
    {
        return match ($this->size) {
            'sm' => 'text-[.625rem]',
            'md' => 'text-xs',
            'lg' => 'text-lg',
            default => 'text-sm'
        };
    }

    protected function getIconSize(): string
    {
        return match ($this->size) {
            'sm' => 'h-4 '.($this->showFlags ? 'w-4' : 'px-1'),
            'md' => 'h-6 w-6',
            'lg' => 'h-10 w-10',
            default => 'h-8 w-8'
        };
    }

    public function render()
    {
        return view('lingua::selector.icon');
    }
}
