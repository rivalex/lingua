<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Livewire\Translation;

use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Rivalex\Lingua\Contracts\TranslationRepository;
use Rivalex\Lingua\Enums\LinguaType;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Support\TranslationLine;
use Rivalex\Lingua\Traits\ModalsConfirm;

final class Delete extends Component
{
    use ModalsConfirm;

    public string $currentLocale;

    public string $localName;

    public string $translationIdentity;

    public string $deleteAction;

    public string $deleteHeader;

    public bool $isDefaultLocale;

    #[Computed]
    public function line(): TranslationLine
    {
        $parts = explode('|', $this->translationIdentity, 4);
        $isVendor = ($parts[2] ?? '0') === '1';
        $vendor = (($parts[3] ?? '') !== '') ? $parts[3] : null;

        return app(TranslationRepository::class)->find($parts[0], $parts[1], $isVendor, $vendor)
            ?? new TranslationLine(
                group: $parts[0],
                key: $parts[1],
                groupKey: $parts[0].'.'.$parts[1],
                type: LinguaType::text,
                text: [],
                isVendor: $isVendor,
                vendor: $vendor,
            );
    }

    public function mount(): void
    {
        $this->isDefaultLocale = $this->currentLocale === linguaDefaultLocale();
        $this->localName = Language::where('code', $this->currentLocale)->first()?->name ?? $this->currentLocale;
        if ($this->isDefaultLocale) {
            $this->deleteHeader = __('lingua::lingua.translations.delete.header');
            $this->confirm = __('lingua::lingua.translations.delete.confirm');
            $this->deleteAction = __('lingua::lingua.translations.delete.action');
        } else {
            $this->deleteHeader = __('lingua::lingua.translations.delete.header_locale', ['locale' => strtoupper($this->localName)]);
            $this->confirm = __('lingua::lingua.translations.delete.confirm_locale', ['locale' => strtoupper($this->localName)]);
            $this->deleteAction = __('lingua::lingua.translations.delete.action_translation_locale', ['locale' => $this->localName]);
        }
    }

    public function deleteTranslation(): void
    {
        if ($this->line->isVendor) {
            $this->closeModal();
            $this->dispatch('vendor_translation_protected');

            return;
        }

        $this->validateConfirmControl();

        try {
            $this->closeModal();
            $repo = app(TranslationRepository::class);
            if ($this->isDefaultLocale) {
                $repo->deleteKey($this->line);
                $this->dispatch('translation_deleted');
                $this->dispatch('refreshTranslationsTableDefaults');
            } else {
                $repo->forgetLocale($this->line, $this->currentLocale);
                $this->dispatch('translation_locale_deleted');
                $this->dispatch('refreshTranslationRow.'.$this->translationIdentity);
            }
        } catch (\Exception $e) {
            $this->closeModal();
            if ($this->isDefaultLocale) {
                $this->dispatch('translation_delete_fail');
            } else {
                $this->dispatch('translation_locale_delete_fail');
            }
            Log::error('Translation delete failed! {error}', ['error' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('lingua::translation.delete');
    }
}
