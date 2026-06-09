<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Livewire\Translation;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Rivalex\Lingua\Contracts\TranslationRepository;
use Rivalex\Lingua\Enums\LinguaType;
use Rivalex\Lingua\Traits\Modals;

class Create extends Component
{
    use Modals;

    #[Validate]
    public string $group = '';

    #[Validate]
    public string $key = '';

    #[Validate]
    public string $textValue = '';

    #[Validate]
    public string $htmlValue = '';

    #[Validate]
    public string $mdValue = '';

    public array $translationsTypes = [];

    public string $translationType = LinguaType::text->value;

    public array $groups = [];

    public bool $fileMode = false;

    public function rules(): array
    {
        if ($this->fileMode) {
            return [
                'group' => 'required|string',
                'key' => ['required', 'string', 'min:2'],
                'textValue' => ['required', 'string'],
            ];
        }

        return [
            'group' => 'required|string',
            'key' => [
                'required',
                'string',
                'min:2',
                Rule::unique('language_lines', 'key')->where('group', $this->group),
            ],
            'translationType' => ['required', Rule::enum(LinguaType::class)],
            'textValue' => ['required_if:translationType,text', 'nullable', 'string'],
            'htmlValue' => ['required_if:translationType,html', 'nullable', 'string'],
            'mdValue' => ['required_if:translationType,markdown', 'nullable', 'string'],
        ];
    }

    public function mount(): void
    {
        $this->modalName = 'translation-create-modal';
        $this->fileMode = linguaIsFileMode();
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
        foreach (app(TranslationRepository::class)->groups() as $group) {
            $this->groups[] = ['id' => $group, 'name' => $group, 'disabled' => false];
        }
        if (! $this->fileMode) {
            $this->translationsTypes = LinguaType::selectValues();
        }
    }

    public function addNewTranslation(): void
    {
        $this->validate();
        try {
            $translationValue = $this->fileMode
                ? $this->textValue
                : match ($this->translationType) {
                    'text' => $this->textValue,
                    'html' => $this->htmlValue,
                    'markdown' => $this->mdValue,
                    default => ''
                };

            // is_vendor is intentionally NOT exposed in the form — vendor translations
            // are managed exclusively through file sync and cannot be created manually.
            app(TranslationRepository::class)->create(
                group: Str::of($this->group)->squish()->trim()->toString(),
                key: Str::of($this->key)->squish()->trim()->toString(),
                type: $this->fileMode ? LinguaType::text : LinguaType::from($this->translationType),
                locale: linguaDefaultLocale(),
                value: $translationValue,
            );
            $this->closeModal();
            $this->reset('key', 'translationType', 'textValue', 'htmlValue', 'mdValue');
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
