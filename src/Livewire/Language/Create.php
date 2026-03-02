<?php

namespace Rivalex\Lingua\Livewire\Language;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use LaravelLang\Locales\Facades\Locales;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\Translation;
use Rivalex\Lingua\Traits\Modals;

class Create extends Component
{
    use Modals;

    public array $availableLanguages = [];

    #[Validate('required|string')]
    public string $language = '';

    public function mount(): void
    {
        $this->modalName = 'language-create-modal';
        $this->setDefaults();
    }

    protected function setDefaults(): void
    {
        $this->reset('availableLanguages');

        foreach (Locales::raw()->notInstalled() as $locale) {
            try {
                $lang = Locales::info($locale);
            } catch (\Throwable) {
                continue;
            }
            $this->availableLanguages[] = [
                'code' => $lang->code,
                'label' => $lang->locale->name,
                'description' => $lang->native,
            ];
        }
    }

    #[On('refreshLanguages')]
    public function refreshLanguages(): void
    {
        $this->setDefaults();
    }

    public function addNewLanguage(): void
    {
        $this->validate();
        try {
            $newLanguage = Locales::info($this->language);
            Artisan::call('lang:add '.$this->language);
            Language::create([
                'code' => $newLanguage->code,
                'regional' => $newLanguage->regional,
                'type' => $newLanguage->type,
                'name' => $newLanguage->locale->name,
                'native' => $newLanguage->native,
                'direction' => $newLanguage->direction,
                'is_default' => false,
            ]);
            Translation::syncToDatabase();
            $this->dispatch('refreshLanguages');
            $this->dispatch('language_added');
            $this->reset('language');
            $this->closeModal();
        } catch (\Throwable $e) {
            $this->reset('language');
            $this->closeModal();
            $this->dispatch('language_added_fail');
            Log::error('Languages reorder failed! {error}', ['error' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('lingua::language.create');
    }
}
