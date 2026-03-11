<?php

namespace Rivalex\Lingua\Livewire\Translation;

use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Rivalex\Lingua\Models\Translation;

class Row extends Component
{
    public string $currentLocale;
    public Translation $translation;
    public string $key;
    #[Validate]
    public string $value = '';
    public string $defaultValue = '';
    public string $editModalName;
    public string $deleteModalName;
    public string $translationType = '';

    protected function rules(): array
    {
        return [
            'currentLocale' => 'sometimes|string',
            'value' => 'required_if:currentLocale,' . linguaDefaultLocale() . '|string|min:1'
        ];
    }

    public function validationAttributes(): array
    {
        $attribute = match ($this->translation->type->value) {
            'text' => __('lingua::lingua.translations.attributes.text_value'),
            'html' => __('lingua::lingua.translations.attributes.html_value'),
            'markdown' => __('lingua::lingua.translations.attributes.md_value')
        };
        return [
            'value' => match ($this->translation->type->value) {
                'text' => __('lingua::lingua.translations.attributes.text_value'),
                'html' => __('lingua::lingua.translations.attributes.html_value'),
                'markdown' => __('lingua::lingua.translations.attributes.md_value')
            }
        ];
    }

    public function mount(): void
    {
        $this->setDefaults();
        $this->editModalName = 'translation-update-modal-' . $this->translation->id;
        $this->deleteModalName = 'translation-delete-modal-' . $this->translation->id;
    }

    protected function setDefaults(): void
    {
        $this->translation->refresh();
        $this->value = $this->translation->text[$this->currentLocale] ?? '';
        $this->defaultValue = $this->translation->text[linguaDefaultLocale()] ?? '';
        $this->translationType = $this->translation->type->value;
    }

    #[On('refreshTranslationRow.{translation.id}')]
    public function refreshTranslationRow(): void
    {
        $this->setDefaults();
        $this->forceRender();
    }


    public function updatedValue(): void
    {
        $this->validate();
        if (empty($this->value)) return;
        $testValue = trim(preg_replace('%<p(.*?)>|</p>%s', '', $this->value));
        if (empty($testValue)) {
            $this->reset('value');
            $this->validateOnly('value');
            if ($this->currentLocale != linguaDefaultLocale()) {
                $this->translation->forgetTranslation($this->currentLocale);
            }
        } else {
            $this->translation->setTranslation($this->currentLocale, $this->value);
            $this->translation->save();
            $this->translation->refresh();
        }
        $this->setDefaults();
        $this->dispatch('updateTranslationModal.' . $this->translation->id);
        $this->dispatch($this->translation->group_key . '_updated');
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
		return view('lingua::translation.row');
	}
}
