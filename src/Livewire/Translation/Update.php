<?php

namespace Rivalex\Lingua\Livewire\Translation;

use Flux\Flux;
use Livewire\Attributes\Validate;
use Rivalex\Lingua\Enums\LinguaType;
use Rivalex\Lingua\Models\Translation;
use Rivalex\Lingua\Traits\Modals;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class Update extends Component
{
    use Modals;

    public Translation $translation;
    public string $currentLocale;
    #[Validate]
    public ?string $group = null;
    #[Validate]
    public ?string $key = null;
    #[Validate]
    public string $textValue = '';
    #[Validate]
    public string $htmlValue = '';
    #[Validate]
    public string $mdValue = '';

    public array $translationsTypes = [];
    public string $translationType = LinguaType::text->value;
    public array $groups = [];
    public bool $required = false;
    public bool $locked = false;

    public function rules(): array
    {
        return [
            'currentLocale' => 'string',
            'group' => 'required|string',
            'key' => [
                'required',
                'string',
                'min:2',
                Rule::unique('language_lines', 'group_key')->ignore($this->translation->id)
            ],
            'translationType' => 'required|string',
            'textValue' => [
                Rule::requiredIf($this->translationType === 'text'),
                Rule::requiredIf($this->currentLocale === defaultLocale()),
                'string',
            ],
            'htmlValue' => [
                Rule::requiredIf($this->translationType === 'html'),
                Rule::requiredIf($this->currentLocale === defaultLocale()),
                'string',
            ],
            'mdValue' => [
                Rule::requiredIf($this->translationType === 'markdown'),
                Rule::requiredIf($this->currentLocale === defaultLocale()),
                'string',
            ]
        ];
    }

    public function mount(): void
    {
        $this->setDefaults();
    }

    #[On('updateTranslationModal.{translation.id}')]
    public function setDefaults(): void
    {
        $this->reset('group', 'key', 'textValue', 'htmlValue', 'mdValue');
        $this->getGroupsList();
        $this->group = $this->translation->group;
        $this->key = $this->translation->key;
        $this->textValue = $this->translation->text[$this->currentLocale] ?? '';
        $this->htmlValue = $this->translation->text[$this->currentLocale] ?? '';
        $this->mdValue = $this->translation->text[$this->currentLocale] ?? '';
        $this->translationType = $this->translation->type->value;
        $this->required = $this->currentLocale === defaultLocale();
        $this->locked = $this->currentLocale !== defaultLocale();
    }

    protected function getGroupsList(): void
    {
        $this->reset('groups', 'translationsTypes');
        foreach (Translation::orderBy('group')->groupBy('group')->pluck('group')->toArray() as $group) {
            $this->groups[] = ['id' => $group, 'name' => $group, 'disabled' => false];
        }
        $this->translationsTypes = LinguaType::selectValues();
    }

    public function updateTranslation(): void
    {
        $this->validate();
        try {
            $translationValue = match ($this->translationType) {
                'text' => $this->textValue,
                'html' => $this->htmlValue,
                'markdown' => $this->mdValue,
                default => ''
            };

            $this->translation->update([
                'group' => $this->group,
                'key' => $this->key,
                'type' => $this->translationType
            ]);
            $this->translation->setTranslation($this->currentLocale, $translationValue);
            $this->translation->save();
            $this->translation->refresh();
            $this->setDefaults();
            $this->dispatch('refreshTranslationRow.' . $this->translation->id);
            $this->dispatch($this->translation->group_key . '_updated');
            $this->dispatch('translation_updated');
            $this->closeModal();
        } catch (\Throwable $e) {
            $this->closeModal();
            $this->dispatch('translation_update_fail');
            Log::error('Translation update failed! {error}', ['error' => $e->getMessage()]);
        }
    }

	public function render()
	{
		return view('lingua::translation.update');
	}
}
