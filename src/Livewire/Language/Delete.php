<?php

namespace Rivalex\Lingua\Livewire\Language;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Component;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\Translation;
use Rivalex\Lingua\Traits\ModalsConfirm;

class Delete extends Component
{
    use ModalsConfirm;

    public Language $language;

    public function mount(): void
    {
        $this->modalName = 'language-delete-modal-'.$this->language->code;
        $this->confirm = Str::of(__('lingua::lingua.languages.delete.confirm',
            ['language' => $this->language->name]))
            ->upper()->squish()->trim();
    }

    public function deleteLanguage(): void
    {
        $this->validate();
        try {
            $locale = $this->language->code;
            Artisan::call('lang:rm '.$locale.' --force');
            $translations = Translation::whereNotNull('text->'.$locale)->get();
            foreach ($translations as $translation) {
                $translation->forgetTranslation($locale);
            }
            $this->language->delete();
            Language::reorderLanguages();
            $this->close();
            $this->dispatch('refreshLanguages');
        } catch (\Throwable $e) {
            $this->close();
            $this->dispatch('languages_sorted_fail');
            Log::error('Languages reorder failed! {error}', ['error' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('lingua::language.delete');
    }
}
