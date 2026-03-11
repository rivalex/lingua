<?php

namespace Rivalex\Lingua\Livewire\Language;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Traits\ModalsConfirm;
use Livewire\Component;

class SetDefault extends Component
{
    use ModalsConfirm;

    public Language $language;
    public bool $canSetDefault = false;

    public function mount(): void
    {
        $this->modalName = 'language-set-default-modal-' . $this->language->code;
        $this->confirm = Str::of(__('lingua::lingua.languages.default.confirm',
            ['language' => $this->language->name]))
                            ->upper()->squish()->trim();
    }

    public function setDefaultLanguage(): void
    {
        try {
            app(Language::class)->setDefault($this->language);
            $this->dispatch('refreshLanguageRows');
            $this->dispatch('language_default_set');
            $this->closeModal();
        } catch (\Throwable $e) {
            $this->closeModal();
            $this->dispatch('language_default_fail');
            $this->addError('setDefaultLanguage', $e->getMessage());
            Log::error('Languages reorder failed! {error}', ['error' => $e->getMessage()]);
        }
    }

	public function render()
	{
		return view('lingua::language.set-default');
	}
}
