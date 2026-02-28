<?php

namespace Rivalex\Lingua\Livewire\Translation;

use Rivalex\Lingua\Enums\LinguaType;
use Rivalex\Lingua\Models\Translation;
use Rivalex\Lingua\Traits\Modals;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Create extends Component
{
    use Modals;

    #[Validate]
    public string $group = '';
    #[Validate]
    public string $key = '';
    #[Validate]
    public string $value = '';
    #[Validate]
    public string $textValue = '';
    #[Validate]
    public string $htmlValue = '';
    #[Validate]
    public string $mdValue = '';

    public array $translationsTypes = [];
    public string $translationType = LinguaType::text->value;

    public array $groups = [];

    public function rules(): array
    {
        return [
            'group' => 'required|string',
            'key' => [
                'required',
                'string',
                'min:2',
                Rule::unique('language_lines', 'key')->where('group', $this->group)
            ],
            'translationType' => 'required|string',
            'textValue' => 'required_if:translationType,text|string',
            'htmlValue' => 'required_if:translationType,html|string',
            'mdValue' => 'required_if:translationType,markdown|string'
        ];
    }

    public function mount(): void
    {
        $this->modalName = 'translation-create-modal';
        $this->getGroupsList();
    }

    #[On('updateTranslationGroup')]
    public function updateTranslationGroup(string $group): void
    {
        $this->group = $group;
    }

    protected function getGroupsList(): void
    {
        $this->reset('groups', 'translationsTypes');
        foreach (Translation::orderBy('group')->groupBy('group')->pluck('group')->toArray() as $group) {
            $this->groups[] = ['id' => $group, 'name' => $group, 'disabled' => false];
        }
        $this->translationsTypes = LinguaType::selectValues();
    }

    public function addNewTranslation(): void
    {
        $this->validate();
        try {

            $translationValue = match ($this->translationType) {
                'text' => $this->textValue,
                'html' => $this->htmlValue,
                'markdown' => $this->mdValue,
                default => ''
            };

            Translation::create([
                'group' => $this->group,
                'key' => $this->key,
                'type' => $this->translationType,
                'text' => [defaultLocale() => $translationValue],
            ]);
            $this->closeModal();
            $this->reset('group', 'key', 'translationType');
            $this->getGroupsList();
            $this->dispatch('refreshTranslationsTableDefaults');
            $this->dispatch('translation_added');
        } catch (\Throwable $th) {
            $this->closeModal();
            $this->dispatch('translation_add_fail');
            Log::error('Translation creation failed! {error}', ['error' => $th->getMessage()]);
        }

    }

	public function render()
	{
		return view('lingua::translation.create');
	}
}
