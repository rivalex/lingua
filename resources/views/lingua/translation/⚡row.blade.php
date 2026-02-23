<?php

use Illuminate\Support\Str;
use Rivalex\Lingua\Enums\LinguaType;
use Rivalex\Lingua\Models\Translation;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component {

    public string $currentLocale;
    public Translation $translation;
    public string $key;
    #[Validate]
    public string $value = '';
    public string $defaultValue = '';
    public string $editModalName;
    public string $deleteModalName;

    public bool $fluxProIsActive = false;

    protected function rules(): array
    {
        return [
            'currentLocale' => 'sometimes|string',
            'value' => 'required_if:currentLocale,' . defaultLocale() . '|string|min:1'
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

    #[On('refreshTranslationRow.{translation.id}')]
    public function setDefaults(): void
    {
        $this->translation->refresh();
        $this->value = $this->translation->text[$this->currentLocale] ?? '';
        $this->defaultValue = $this->translation->text[defaultLocale()] ?? '';
    }

    public function updatedValue(): void
    {
        $this->validate();
        if (empty($this->value)) return;
        $testValue = trim(preg_replace('%<p(.*?)>|</p>%s', '', $this->value));
        if (empty($testValue)) {
            $this->reset('value');
            $this->validateOnly('value');
            if ($this->currentLocale != defaultLocale()) {
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
};
?>

@placeholder
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
@endplaceholder


<flux:table.row>
    <flux:table.cell class="lingua-row">
        <div class="flex flex-row gap-2 items-center">
            {!! $translation->type->iconColor(8) !!}
            <div class="flex flex-col gap-1">
                <div class="flex flex-row gap-2 items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                         class="lucide lucide-group-icon lucide-group">
                        <path d="M3 7V5c0-1.1.9-2 2-2h2"/>
                        <path d="M17 3h2c1.1 0 2 .9 2 2v2"/>
                        <path d="M21 17v2c0 1.1-.9 2-2 2h-2"/>
                        <path d="M7 21H5c-1.1 0-2-.9-2-2v-2"/>
                        <rect width="7" height="5" x="7" y="7" rx="1"/>
                        <rect width="7" height="5" x="10" y="12" rx="1"/>
                    </svg>
                    <strong style="white-space: break-spaces">{{ $translation->group }}</strong>
                </div>

                <div class="flex flex-row gap-2 items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                         class="lucide lucide-key-round-icon lucide-key-round">
                        <path
                            d="M2.586 17.414A2 2 0 0 0 2 18.828V21a1 1 0 0 0 1 1h3a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h1a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h.172a2 2 0 0 0 1.414-.586l.814-.814a6.5 6.5 0 1 0-4-4z"/>
                        <circle cx="16.5" cy="7.5" r=".5" fill="currentColor"/>
                    </svg>
                    <x-lingua::clipboard text-to-copy="{{ $translation->group_key }}">
                        <p class="text-sm text-gray-900 dark:text-white"
                           style="white-space: break-spaces">{{ $translation->key }}</p>
                    </x-lingua::clipboard>
                </div>
            </div>
        </div>
    </flux:table.cell>

    <flux:table.cell class="lingua-row">
        <div class="lingua-preview">
            <x-lingua::clipboard text-to-copy="{{ $defaultValue }}">
                @if($translation->type->value === 'markdown')
                    <div x-data="{ showMarkdown: @js($defaultValue) }">
                        <pre class="markdown" x-text="showMarkdown"></pre>
                    </div>
                @else
                    <div class="preview">{!! $defaultValue !!}</div>
                @endif
            </x-lingua::clipboard>
            <div class="flex flex-col gap-1 items-center">
                <flux:button variant="ghost" tabindex="-1" size="sm" icon="arrow-path"
                             wire:click="syncFromDefault"></flux:button>
                <x-action-message on="{{ $translation->group_key }}_updated">
                    <flux:icon icon="check-circle" variant="solid"
                               class="text-green-500 dark:text-green-400"/>
                </x-action-message>
            </div>
        </div>
    </flux:table.cell>
    <flux:table.cell class="lingua-row">
        <x-lingua::editor wire:model.blur.live="value" type="{{ $translation->type->value }}"
                             :placeholder="__('lingua::lingua.translations.create.fields.htmlValue_placeholder')"/>
    </flux:table.cell>

    <flux:table.cell align="center" class="lingua-row center" wire:loading.class="pointer-events-none">
        <flux:button.group>
            {{--            @if($this->currentLocale === defaultLocale())--}}
            <flux:modal.trigger name="{{ $editModalName }}">
                <flux:button tabindex="-1" variant="primary" color="green"
                             icon="pencil-square"/>
            </flux:modal.trigger>
            {{--            @endif--}}
            <flux:modal.trigger name="{{ $deleteModalName }}">
                <flux:button tabindex="-1" variant="danger" icon="trash"></flux:button>
            </flux:modal.trigger>
        </flux:button.group>
        {{--        @if($this->currentLocale === defaultLocale())--}}
        <livewire:lingua::translation.update
            :$translation :$currentLocale
            wire:key="update-translation-{{ $translation->id }}"
            :modal-name="$editModalName"/>
        {{--        @endif--}}
        <livewire:lingua::translation.delete
            :$translation :$currentLocale
            wire:key="delete-translation-{{ $translation->id }}"
            :modal-name="$deleteModalName"/>
    </flux:table.cell>
</flux:table.row>
