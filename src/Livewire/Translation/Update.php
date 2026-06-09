<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Livewire\Translation;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Rivalex\Lingua\Contracts\TranslationRepository;
use Rivalex\Lingua\Enums\LinguaType;
use Rivalex\Lingua\Support\TranslationLine;
use Rivalex\Lingua\Traits\Modals;

class Update extends Component
{
    use Modals;

    public string $translationIdentity;

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

    public bool $isVendor = false;

    public bool $fileMode = false;

    /**
     * Re-fetch the TranslationLine from the repository using the stable identity.
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

    public function rules(): array
    {
        $isDefault = $this->currentLocale === linguaDefaultLocale();

        if ($this->fileMode) {
            return [
                'currentLocale' => 'string',
                'group' => 'required|string',
                'key' => ['required', 'string', 'min:2'],
                'textValue' => [Rule::requiredIf($isDefault), 'nullable', 'string'],
            ];
        }

        return [
            'currentLocale' => 'string',
            'group' => 'required|string',
            'key' => [
                'required',
                'string',
                'min:2',
                Rule::unique('language_lines', 'key')
                    ->where('group', $this->group ?? $this->line->group)
                    ->where('is_vendor', $this->isVendor)
                    ->ignore($this->line->id),
            ],
            'translationType' => ['required', Rule::enum(LinguaType::class)],
            'textValue' => [
                Rule::requiredIf($this->translationType === 'text' && $isDefault),
                'nullable',
                'string',
            ],
            'htmlValue' => [
                Rule::requiredIf($this->translationType === 'html' && $isDefault),
                'nullable',
                'string',
            ],
            'mdValue' => [
                Rule::requiredIf($this->translationType === 'markdown' && $isDefault),
                'nullable',
                'string',
            ],
        ];
    }

    public function mount(): void
    {
        $this->fileMode = linguaIsFileMode();
        $this->isVendor = $this->line->isVendor;
        $this->setDefaults();
    }

    #[On('updateTranslationModal.{translationIdentity}')]
    public function setDefaults(): void
    {
        $this->reset('group', 'key', 'textValue', 'htmlValue', 'mdValue');
        $this->getGroupsList();
        unset($this->line);
        $line = $this->line;
        $this->group = $line->group;
        $this->key = $line->key;
        $this->required = $this->currentLocale === linguaDefaultLocale();
        $this->locked = $this->fileMode || $this->currentLocale !== linguaDefaultLocale();
        $this->textValue = $line->value($this->currentLocale);

        if ($this->fileMode) {
            $this->translationType = LinguaType::text->value;
        } else {
            $this->htmlValue = $line->value($this->currentLocale);
            $this->mdValue = $line->value($this->currentLocale);
            $this->translationType = $line->type->value;
        }
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

    public function updateTranslation(): void
    {
        $this->validate();
        try {
            $repo = app(TranslationRepository::class);

            if ($this->fileMode) {
                $line = $repo->setValue($this->line, $this->currentLocale, $this->textValue);
                $this->translationIdentity = $line->identity();
            } else {
                $translationValue = match ($this->translationType) {
                    'text' => $this->textValue,
                    'html' => $this->htmlValue,
                    'markdown' => $this->mdValue,
                    default => ''
                };

                $line = $repo->updateMeta(
                    $this->line,
                    Str::of($this->group)->squish()->trim()->toString(),
                    Str::of($this->key)->squish()->trim()->toString(),
                    LinguaType::from($this->translationType),
                );
                $line = $repo->setValue($line, $this->currentLocale, $translationValue);
                $this->translationIdentity = $line->identity();
            }
            unset($this->line);
            $this->setDefaults();
            $this->dispatch('refreshTranslationRow.'.$this->translationIdentity);
            $this->dispatch($this->line->groupKey.'_updated');
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
