<div class="flex flex-col gap-6">

    @if($errorMessage)
        <flux:callout variant="danger" icon="exclamation-triangle">
            <flux:callout.heading>{{ __('lingua::lingua.transfer.export.error') }}</flux:callout.heading>
            <flux:callout.text>{{ $errorMessage }}</flux:callout.text>
        </flux:callout>
    @endif

    {{-- Scope --}}
    <x-lingua::card
        :title="__('lingua::lingua.transfer.export.scope.title')"
        :subtitle="__('lingua::lingua.transfer.export.scope.subtitle')"
        icon="arrows-right-left">
        <div class="flex flex-col gap-3">
            <flux:radio.group wire:model.live="scope" variant="cards">
                <flux:radio
                    value="bilingual"
                    :label="__('lingua::lingua.transfer.export.scope.bilingual')"
                    :description="__('lingua::lingua.transfer.export.scope.bilingual_desc')"
                />
                <flux:radio
                    value="multi_locale"
                    :label="__('lingua::lingua.transfer.export.scope.multi_locale')"
                    :description="__('lingua::lingua.transfer.export.scope.multi_locale_desc')"
                />
                <flux:radio
                    value="json_native"
                    :label="__('lingua::lingua.transfer.export.scope.json_native')"
                    :description="__('lingua::lingua.transfer.export.scope.json_native_desc')"
                />
            </flux:radio.group>
        </div>
    </x-lingua::card>

    {{-- Target locale (bilingual only) --}}
    @if($scope === 'bilingual')
        <x-lingua::card
            :title="__('lingua::lingua.transfer.export.locale.title')"
            :subtitle="__('lingua::lingua.transfer.export.locale.subtitle')"
            icon="language">
            <flux:select wire:model="targetLocale" :placeholder="__('lingua::lingua.transfer.export.locale.placeholder')">
                @foreach($this->languages as $language)
                    <flux:option value="{{ $language->code }}">{{ $language->native }} ({{ $language->code }})</flux:option>
                @endforeach
            </flux:select>
        </x-lingua::card>
    @endif

    {{-- Filter --}}
    @if($scope !== 'json_native')
        <x-lingua::card
            :title="__('lingua::lingua.transfer.export.filter.title')"
            :subtitle="__('lingua::lingua.transfer.export.filter.subtitle')"
            icon="funnel">
            <flux:radio.group wire:model="filter" variant="cards">
                <flux:radio
                    value="all"
                    :label="__('lingua::lingua.transfer.export.filter.all')"
                    :description="__('lingua::lingua.transfer.export.filter.all_desc')"
                />
                <flux:radio
                    value="only_missing"
                    :label="__('lingua::lingua.transfer.export.filter.only_missing')"
                    :description="__('lingua::lingua.transfer.export.filter.only_missing_desc')"
                />
            </flux:radio.group>
        </x-lingua::card>
    @endif

    {{-- Format --}}
    <x-lingua::card
        :title="__('lingua::lingua.transfer.export.format.title')"
        :subtitle="__('lingua::lingua.transfer.export.format.subtitle')"
        icon="document-arrow-down">
        <div class="flex flex-col gap-3">
            <flux:select wire:model="format">
                @foreach($this->availableFormats as $key => $label)
                    <flux:option value="{{ $key }}">{{ $label }}</flux:option>
                @endforeach
            </flux:select>
            @unless($this->spreadsheetAvailable)
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    {!! __('lingua::lingua.transfer.export.format.openspout_hint') !!}
                </p>
            @endunless
        </div>
    </x-lingua::card>

    {{-- Vendor toggle --}}
    <x-lingua::card
        :title="__('lingua::lingua.transfer.export.vendor.title')"
        :subtitle="__('lingua::lingua.transfer.export.vendor.subtitle')"
        icon="puzzle-piece">
        <flux:switch wire:model="includeVendor" :label="__('lingua::lingua.transfer.export.vendor.toggle')" />
    </x-lingua::card>

    {{-- Export button --}}
    <div class="flex justify-end">
        <flux:button wire:click="export" variant="primary" icon="arrow-down-tray">
            {{ __('lingua::lingua.transfer.export.button') }}
        </flux:button>
    </div>

</div>
