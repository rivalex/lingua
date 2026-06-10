<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Livewire\Translation;

use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Rivalex\Lingua\Contracts\TranslationRepository;
use Rivalex\Lingua\Enums\LinguaType;
use Rivalex\Lingua\Support\HtmlSanitizer;
use Rivalex\Lingua\Support\TranslationLine;

final class Row extends Component
{
    public string $currentLocale;

    public string $translationIdentity;

    public string $key;

    #[Validate]
    public string $value = '';

    public string $defaultValue = '';

    public string $editModalName;

    public string $deleteModalName;

    public string $translationType = '';

    public bool $fileMode = false;

    /**
     * Re-fetch the TranslationLine from the repository using the stable identity.
     * Falls back to a stub when the key has just been deleted (between dispatches).
     */
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

    protected function rules(): array
    {
        return [
            'currentLocale' => 'sometimes|string',
            'value' => 'required_if:currentLocale,'.linguaDefaultLocale().'|string|min:1',
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'value' => $this->fileMode
                ? __('lingua::lingua.translations.attributes.text_value')
                : match ($this->line->type->value) {
                    'text' => __('lingua::lingua.translations.attributes.text_value'),
                    'html' => __('lingua::lingua.translations.attributes.html_value'),
                    'markdown' => __('lingua::lingua.translations.attributes.md_value'),
                },
        ];
    }

    public function mount(): void
    {
        $this->fileMode = linguaIsFileMode();
        $this->setDefaults();
        $this->editModalName = 'translation-update-modal-'.md5($this->translationIdentity);
        $this->deleteModalName = 'translation-delete-modal-'.md5($this->translationIdentity);
    }

    protected function setDefaults(): void
    {
        unset($this->line);
        $line = $this->line;
        $this->value = $line->value($this->currentLocale);
        $rawDefault = $line->value(linguaDefaultLocale());
        // SECURITY: the html preview is rendered with {!! !!} in the view.
        // strip_tags() kept attributes (onerror, javascript: URIs) on allowed
        // tags — a stored XSS vector. HtmlSanitizer whitelists tags AND attributes.
        $this->defaultValue = (! $this->fileMode && $line->type->value === 'html')
            ? HtmlSanitizer::sanitize($rawDefault)
            : $rawDefault;
        $this->translationType = $this->fileMode ? LinguaType::text->value : $line->type->value;
    }

    #[On('refreshTranslationRow.{translationIdentity}')]
    public function refreshTranslationRow(): void
    {
        $this->setDefaults();
        $this->forceRender();
    }

    public function updatedValue(): void
    {
        $this->validate();
        if (empty($this->value)) {
            return;
        }
        $testValue = trim(preg_replace('%<p(.*?)>|</p>%s', '', $this->value));
        $repo = app(TranslationRepository::class);
        if (empty($testValue)) {
            $this->reset('value');
            $this->validateOnly('value');
            if ($this->currentLocale !== linguaDefaultLocale() && ! $this->line->isVendor) {
                $repo->forgetLocale($this->line, $this->currentLocale);
                unset($this->line);
            }
        } else {
            $repo->setValue($this->line, $this->currentLocale, $this->value);
            unset($this->line);
        }
        $this->setDefaults();
        $this->dispatch('updateTranslationModal.'.$this->translationIdentity);
        $this->dispatch($this->line->groupKey.'_updated');
    }

    public function syncFromDefault(): void
    {
        $this->value = $this->defaultValue;
        $this->updatedValue();
    }

    public function placeholder()
    {
        return <<<'HTML'
        <flux:table.row>
            <flux:table.cell>
                <flux:skeleton animate="shimmer">
                    <flux:skeleton.line/>
                </flux:skeleton>
            </flux:table.cell>
            <flux:table.cell>
                <flux:skeleton animate="shimmer">
                    <flux:skeleton.line/>
                </flux:skeleton>
            </flux:table.cell>
            <flux:table.cell>
                <flux:skeleton animate="shimmer">
                    <flux:skeleton.line/>
                </flux:skeleton>
            </flux:table.cell>
            <flux:table.cell align="center">
                <flux:skeleton animate="shimmer">
                    <flux:skeleton.line/>
                </flux:skeleton>
            </flux:table.cell>
        </flux:table.row>
        HTML;
    }

    public function render()
    {
        return view('lingua::translation.row', [
            'translation' => $this->line,
        ]);
    }
}
