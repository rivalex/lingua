<?php

namespace Rivalex\Lingua\Livewire\Translation;

use Illuminate\Support\Facades\Log;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\Translation;
use Rivalex\Lingua\Traits\ModalsConfirm;
use Rivalex\Lingua\Traits\NotificationService;
use Livewire\Component;

class Delete extends Component
{
    use ModalsConfirm;
    use NotificationService;

    public string $currentLocale;
    public string $localName;
    public Translation $translation;
    public bool $canDelete = false;
    public string $deleteAction;
    public string $deleteHeader;

    public bool $isDefaultLocale;

    public function mount(): void
    {
        $this->isDefaultLocale = $this->currentLocale === defaultLocale();
        $this->localName = Language::where('code', $this->currentLocale)->first()->native;
        if ($this->isDefaultLocale) {
            $this->deleteHeader = __('translations.delete.header');
            $this->confirm = __('translations.delete.confirm');
            $this->deleteAction = __('translations.delete.action');
        } else {
            $this->deleteHeader = __('translations.delete.header_locale', ['locale' => strtoupper($this->localName)]);
            $this->confirm = __('translations.delete.confirm_locale', ['locale' => strtoupper($this->localName)]);
            $this->deleteAction = __('translations.delete.action_translation_locale', ['locale' => $this->localName]);
        }
    }

    public function deleteTranslation(): void
    {
        try {
            $this->closeModal();
            if ($this->isDefaultLocale) {
                $this->translation->delete();
                $this->dispatch('refreshTranslationsTable');
                $this->dispatch('refreshTranslationsTable');
            } else {
                $this->translation->forgetTranslation($this->currentLocale);
                $this->dispatch('refreshTranslationRow.' . $this->translation->id);
            }
        } catch (\Exception $e) {
            $this->closeModal();
            Log::error('Translation delete failed! {error}', ['error' => $e->getMessage()]);
        }
    }

	public function render()
	{
		return view('lingua::translation.delete');
	}
}
