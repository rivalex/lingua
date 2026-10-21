<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Livewire\Translation;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Rivalex\Lingua\Enums\LinguaType;
use Rivalex\Lingua\Models\Translation;
use Rivalex\Lingua\Traits\Modals;

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

    public bool $isVendor = false;

    public function rules(): array
    {
        $isDefault = $this->currentLocale === linguaDefaultLocale();

        return [
            'currentLocale' => 'string',
            'group' => 'required|string',
            'key' => [
                'required',
                'string',
                'min:2',
                // M1 fix: validate against `key` column scoped by group+vendor, not the composite group_key column
                Rule::unique('language_lines', 'key')
                    ->where('group', $this->group ?? $this->translation->group)
                    ->where('is_vendor', $this->isVendor)
                    ->ignore($this->translation->id),
            ],
            // M4 fix: validate against enum values, not just any string
            'translationType' => ['required', Rule::enum(LinguaType::class)],
            // M3 fix: single combined condition — required only when type matches AND locale is default
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
        $this->isVendor = $this->translation->is_vendor;
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
        $this->required = $this->currentLocale === linguaDefaultLocale();
        $this->locked = $this->currentLocale !== linguaDefaultLocale();
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

            // Vendor translations: group and key are locked — they map to vendor package files.
            // Only the text value and type may be updated.
            if ($this->translation->is_vendor) {
                $this->translation->update(['type' => $this->translationType]);
            } else {
                $this->translation->update([
                    'group' => Str::of($this->group)->squish()->trim(),
                    'key' => Str::of($this->key)->squish()->trim(),
                    'type' => $this->translationType,
                ]);
            }
            $this->translation->setTranslation($this->currentLocale, $translationValue);
            $this->translation->save();
            $this->translation->refresh();
            $this->setDefaults();
            $this->dispatch('refreshTranslationRow.'.$this->translation->id);
            $this->dispatch($this->translation->group_key.'_updated');
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
