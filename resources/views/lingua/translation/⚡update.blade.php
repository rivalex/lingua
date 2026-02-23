<?php

use Flux\Flux;
use Livewire\Attributes\Validate;
use Rivalex\Lingua\Enums\LinguaType;
use Rivalex\Lingua\Models\Translation;
use Rivalex\Lingua\Traits\Modals;
use Rivalex\Lingua\Traits\NotificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {

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
            $this->dispatch('translation_updated' . $this->translation->id);
            $this->closeModal();
        } catch (\Throwable $e) {
            $this->closeModal();
            $this->dispatch('translation_updated_fail');
            Log::error('Translation update failed! {error}', ['error' => $e->getMessage()]);
        }
    }
};
?>

<flux:modal name="{{ $modalName }}" class="lingua-modal text-start">
    <div class="flex flex-col gap-4">
        <h2 class="text-lg">Update translation</h2>
        <form wire:submit.prevent="updateTranslation" id="updateTranslation" class="flex flex-col gap-4">
            @csrf
            <div class="grid grid-cols-3 gap-2">
                <div class="col-span-2">
                    <x-lingua::autocomplete wire:model.blur.live="group" required :options="$groups" :disabled="$locked"
                                            :label="__('rivalex::lingua.translations.create.fields.group')"
                                            :placeholder="__('rivalex::lingua.translations.create.fields.group_placeholder')"/>
                </div>
                <div class="col-span-1">
                    <flux:select wire:model.change.live="translationType" required :disabled="$locked"
                                 :badge="__('rivalex::lingua.global.required')"
                                 :label="__('rivalex::lingua.translations.create.fields.type')"
                                 :placeholder="__('rivalex::lingua.translations.create.fields.type_placeholder')"
                                 :variant="Flux::pro() ? 'listbox' : null">
                        @foreach($translationsTypes as $translationTypeItem)
                            <flux:select.option value="{{ $translationTypeItem['value'] }}">
                                {!! $translationTypeItem['label'] !!}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div class="col-span-3">
                    <flux:input wire:model.blur.live="key" type="text" icon="key" :disabled="$locked"
                                :label="__('rivalex::lingua.translations.create.fields.key')"
                                :placeholder="__('rivalex::lingua.translations.create.fields.key_placeholder')"
                                required :badge="__('rivalex::lingua.global.required')"/>
                </div>
                <div class="col-span-3">
                    <div x-cloak x-show="$wire.translationType === 'text'">
                        <flux:textarea wire:key="editor-text" wire:model.blur="textValue" rows="3" :helper="false"
                                       :required="$required"
                                       :label="__('rivalex::lingua.translations.create.fields.textValue')"
                                       :placeholder="__('rivalex::lingua.translations.create.fields.textValue_placeholder')"/>
                    </div>
                    <div x-cloak x-show="$wire.translationType === 'html'">
                        <x-lingua::editor wire:model.blur="htmlValue" type="html" :helper="false"
                                             :required="$required"
                                             :label="__('rivalex::lingua.translations.create.fields.htmlValue')"
                                             :placeholder="__('rivalex::lingua.translations.create.fields.htmlValue_placeholder')"/>
                    </div>
                    <div x-cloak x-show="$wire.translationType === 'markdown'">
                        <x-lingua::editor wire:model.blur="mdValue" type="markdown" :helper="false"
                                             :required="$required"
                                             :label="__('rivalex::lingua.translations.create.fields.mdValue')"
                                             :placeholder="__('rivalex::lingua.translations.create.fields.mdValue_placeholder')"/>
                    </div>
                </div>
            </div>
            <flux:separator/>
            <div class="flex justify-between gap-2 items-center">
                <flux:button variant="filled" color="gray" icon="x-mark"
                             x-on:click="$flux.modal('{{ $modalName }}').close()">@lang('rivalex::lingua.global.close')</flux:button>
                <flux:button type="submit" variant="primary" color="green"
                             icon="check">@lang('rivalex::lingua.global.save')</flux:button>
            </div>
        </form>
    </div>
</flux:modal>
