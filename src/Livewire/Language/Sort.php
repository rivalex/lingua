<?php

namespace Rivalex\Lingua\Livewire\Language;

use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Async;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Renderless;
use Livewire\Component;
use Rivalex\Lingua\Models\Language;

class Sort extends Component
{
    #[Renderless, Async]
    public function updateLanguageOrder($item, $position): void
    {
        try {
            $languages = Language::orderBy('sort')->get();
            $movedLanguage = $languages->firstWhere('id', $item);
            if (! $movedLanguage) {
                return;
            }
            $languages = $languages->except($item);
            $languages->splice($position, 0, [$movedLanguage]);
            $languages->each(function ($language, $index) {
                $language->update(['sort' => $index + 1]);
            });
            $this->dispatch('languages_sorted');
            $this->dispatch('refreshLanguages');
            $this->dispatch('refreshLanguageSelector');
        } catch (\Throwable $e) {
            $this->addError('updateLanguageOrder', $e->getMessage());
            $this->dispatch('languages_sorted_fail');
            Log::error('Languages reorder failed! {error}', ['error' => $e->getMessage()]);
        }
    }

    #[Computed]
    public function languages()
    {
        return Language::orderBy('sort')->get();
    }

    #[On('refreshLanguages')]
    public function refreshSortList(): void
    {
        $this->forceRender();
    }

    public function render()
    {
        return view('lingua::language.sort');
    }
}
