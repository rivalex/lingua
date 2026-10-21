<div class="flex flex-col gap-4">

    @include('lingua::transfer.partials._alerts')

    {{-- Export configuration card — all options grouped in a single card with rows --}}
    <x-lingua::card
        :title="__('lingua::lingua.transfer.tabs.export')"
        :subtitle="__('lingua::lingua.transfer.subtitle')"
        icon="arrows-right-left">

        {{-- Scope --}}
        <x-lingua::card.row
            :title="__('lingua::lingua.transfer.export.scope.title')"
            :description="__('lingua::lingua.transfer.export.scope.subtitle')">
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
        </x-lingua::card.row>

        {{-- Target locale (bilingual only) --}}
        @if($scope === 'bilingual')
            <x-lingua::card.row
                :title="__('lingua::lingua.transfer.export.locale.title')"
                :description="__('lingua::lingua.transfer.export.locale.subtitle')">
                <flux:select wire:model="targetLocale" :placeholder="__('lingua::lingua.transfer.export.locale.placeholder')" class="max-w-xs">
                    @foreach($this->languages as $language)
                        <option value="{{ $language->code }}">{{ $language->native }} ({{ $language->code }})</option>
                    @endforeach
                </flux:select>
            </x-lingua::card.row>
        @endif

        {{-- Filter (not for json_native) --}}
        @if($scope !== 'json_native')
            <x-lingua::card.row
                :title="__('lingua::lingua.transfer.export.filter.title')"
                :description="__('lingua::lingua.transfer.export.filter.subtitle')">
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
            </x-lingua::card.row>
        @endif

        {{-- File format --}}
        <x-lingua::card.row
            :title="__('lingua::lingua.transfer.export.format.title')"
            :description="__('lingua::lingua.transfer.export.format.subtitle')">
            <div class="flex flex-col gap-2">
                <flux:select wire:model="format" class="max-w-xs">
                    @foreach($this->availableFormats as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </flux:select>
                @unless($this->spreadsheetAvailable)
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        {!! __('lingua::lingua.transfer.export.format.openspout_hint') !!}
                    </p>
                @endunless
            </div>
        </x-lingua::card.row>

        {{-- Vendor translations --}}
        <x-lingua::card.row
            :title="__('lingua::lingua.transfer.export.vendor.title')"
            :description="__('lingua::lingua.transfer.export.vendor.subtitle')">
            <flux:switch wire:model="includeVendor" :label="__('lingua::lingua.transfer.export.vendor.toggle')" />
        </x-lingua::card.row>

        {{-- Footer action --}}
        <div class="flex justify-end border-t border-zinc-100 px-6 py-4 dark:border-zinc-800">
            <flux:button wire:click="export" variant="primary" icon="arrow-down-tray">
                {{ __('lingua::lingua.transfer.export.button') }}
            </flux:button>
        </div>

    </x-lingua::card>

</div>
