<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Livewire\Translation;

use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Rivalex\Lingua\Contracts\TranslationRepository;
use Rivalex\Lingua\Enums\LinguaType;
use Rivalex\Lingua\Support\TranslationIdentityRules;
use Rivalex\Lingua\Support\TranslationValuePolicy;
use Rivalex\Lingua\Traits\AuthorizesLingua;
use Rivalex\Lingua\Traits\Modals;

final class Create extends Component
{
    use AuthorizesLingua;
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

    #[Locked]
    public array $translationsTypes = [];

    public string $translationType = LinguaType::text->value;

    #[Locked]
    public array $groups = [];

    #[Locked]
    public bool $fileMode = false;

    public function rules(): array
    {
        $groupRule = function (string $attribute, mixed $value, Closure $fail): void {
            $group = Str::of((string) $value)->squish()->trim()->toString();
            $error = TranslationIdentityRules::groupError($group);
            if ($error === null) {
                $groups = app(TranslationRepository::class)->groups();
                $error = in_array($group, $groups, true) ? null : TranslationIdentityRules::groupCaseError($group, $groups);
            }
            if ($error !== null) {
                $fail($error);
            }
        };
        $keyRule = function (string $attribute, mixed $value, Closure $fail): void {
            $group = Str::of($this->group)->squish()->trim()->toString();
            $key = Str::of((string) $value)->squish()->trim()->toString();
            // The length is checked on the normalized key, the one that is stored.
            if (mb_strlen($key) < 2) {
                $fail(__('validation.min.string', ['attribute' => 'key', 'min' => 2]));

                return;
            }
            $error = TranslationIdentityRules::keyError($group, $key)
                ?? TranslationIdentityRules::collisionError($group, $key, TranslationIdentityRules::existingKeys(app(TranslationRepository::class), $group, false, null));
            if ($error !== null) {
                $fail($error);
            }
        };
        $valueRule = fn (LinguaType $type): Closure => function (string $attribute, mixed $value, Closure $fail) use ($type): void {
            // Only the field of the selected type is written; the others may hold stale input.
            if (! $this->fileMode && $this->translationType !== $type->value) {
                return;
            }
            $error = TranslationValuePolicy::assertAcceptable(
                $type,
                Str::of($this->group)->squish()->trim()->toString(),
                Str::of($this->key)->squish()->trim()->toString(),
                (string) $value,
            );
            if ($error !== null) {
                $fail($error);
            }
        };

        if ($this->fileMode) {
            return [
                'group' => ['required', 'string', $groupRule],
                'key' => ['required', 'string', $keyRule],
                'textValue' => ['required', 'string', $valueRule(LinguaType::text)],
            ];
        }

        return [
            'group' => ['required', 'string', $groupRule],
            'key' => [
                'required',
                'string',
                $keyRule,
                Rule::unique('language_lines', 'key')->where('group', $this->group),
            ],
            'translationType' => ['required', Rule::enum(LinguaType::class)],
            'textValue' => ['required_if:translationType,text', 'nullable', 'string', $valueRule(LinguaType::text)],
            'htmlValue' => ['required_if:translationType,html', 'nullable', 'string', $valueRule(LinguaType::html)],
            'mdValue' => ['required_if:translationType,markdown', 'nullable', 'string', $valueRule(LinguaType::markdown)],
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

            // is_vendor is intentionally NOT exposed in the form - vendor translations
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
