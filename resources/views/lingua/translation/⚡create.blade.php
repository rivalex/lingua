<?php

use Rivalex\Lingua\Enums\LinguaType;
use Rivalex\Lingua\Models\Translation;
use Rivalex\Lingua\Traits\Modals;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component {

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
            'value' => 'required|string',
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
            $this->dispatch('refreshTranslationsTable');
            $this->dispatch('refreshTranslationsTableDefaults');
            $this->dispatch('translation_added');
        } catch (\Throwable $th) {
            $this->closeModal();
            $this->dispatch('translation_added_fail');
            Log::error('Languages reorder failed! {error}', ['error' => $e->getMessage()]);
        }

    }
};
?>

<div wire:ignore>
    <flux:modal.trigger name="{{ $modalName }}">
        <flux:button variant="primary" color="green"
                     icon="plus">@lang('rivalex::lingua.translations.create.action')</flux:button>
    </flux:modal.trigger>
    <flux:modal name="{{ $modalName }}" class="lingua-modal">
        <div class="flex flex-col gap-4">
            <h2 class="text-lg">@lang('rivalex::lingua.translations.create.header')</h2>
            <form wire:submit.prevent="addNewTranslation" id="addNewTranslationForm" class="flex flex-col gap-4">
                @csrf
                <div class="grid grid-cols-3 gap-2 items-start">
                    <div class="col-span-2">
                        <x-lingua::autocomplete wire:model.blur.live="group" required :options="$groups"
                                                :label="__('rivalex::lingua.translations.create.fields.group')"
                                                :placeholder="__('rivalex::lingua.translations.create.fields.group_placeholder')"/>
                    </div>
                    <div class="col-span-1">
                        <flux:select wire:model.change.live="translationType"
                                     required
                                     :badge="__('rivalex::lingua.global.required')"
                                     :label="__('rivalex::lingua.translations.create.fields.type')"
                                     :placeholder="__('rivalex::lingua.translations.create.fields.type_placeholder')"
                                     :variant="Flux::pro() ? 'listbox' : null">
                            @foreach($translationsTypes as $translationTypeItem)
                                <flux:select.option value="{{ $translationTypeItem['value'] }}"
                                                    :key="'group-' . $translationTypeItem['value']">
                                    {!! $translationTypeItem['label'] !!}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                    <div class="col-span-3">
                        <flux:input wire:model.blur.live="key" type="text" icon="key"
                                    :label="__('rivalex::lingua.translations.create.fields.key')"
                                    :placeholder="__('rivalex::lingua.translations.create.fields.key_placeholder')"
                                    required :badge="__('rivalex::lingua.global.required')"/>
                    </div>
                    <div class="col-span-3">
                        <div x-cloak x-show="$wire.translationType === 'text'">
                            <x-lingua::editor wire:model.blur="textValue" type="text" :helper="false" required
                                              :label="__('rivalex::lingua.translations.create.fields.textValue')"
                                              :placeholder="__('rivalex::lingua.translations.create.fields.textValue_placeholder')"/>
                        </div>
                        <div x-cloak x-show="$wire.translationType === 'html'">
                            <x-lingua::editor wire:model.blur="htmlValue" type="html" :helper="false" required
                                                 :label="__('rivalex::lingua.translations.create.fields.htmlValue')"
                                                 :placeholder="__('rivalex::lingua.translations.create.fields.htmlValue_placeholder')"/>
                        </div>
                        <div x-cloak x-show="$wire.translationType === 'markdown'">
                            <x-lingua::editor wire:model.blur="mdValue" type="markdown" :helper="false" required
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
</div>

