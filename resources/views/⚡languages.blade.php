<?php

use Livewire\Attributes\On;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\Translation;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Async;
use Livewire\Attributes\Renderless;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('UI Translation Manager')]
class extends Component {

    public string $search = '';

    #[Renderless, Async]
    public function updateLanguages(): void
    {
        try {
            \Illuminate\Support\Facades\Artisan::call('lang:update');
            Translation::syncToDatabase();
            Artisan::call('optimize:clear');
            $this->dispatch('lang_updated');
            $this->dispatch('refreshLanguages');
        } catch (Throwable $e) {
            $this->dispatch('lang_updated_fail');
            Log::error('Translations UPDATE failed! {error}', ['error' => $e->getMessage()]);
        }
    }

    #[Renderless, Async]
    public function syncToDatabase(): void
    {
        try {
            Translation::syncToDatabase();
            Artisan::call('optimize:clear');
            $this->dispatch('synced_database');
            $this->dispatch('refreshLanguages');
        } catch (Throwable $e) {
            $this->dispatch('synced_database_fail');
            Log::error('Translations DATABASE sync failed! {error}', ['error' => $e->getMessage()]);
        }
    }

    #[Renderless, Async]
    public function syncToLocal(): void
    {
        try {
            Translation::syncToLocal();
            Artisan::call('optimize:clear');
            $this->dispatch('synced_local');
            $this->dispatch('refreshLanguages');
        } catch (Throwable $e) {
            $this->dispatch('synced_local_fail');
            Log::error('Translations LOCAL sync failed! {error}', ['error' => $e->getMessage()]);
        }
    }

    #[On('refreshLanguages')]
    public function refreshSortList(): void
    {
        $this->renderIsland('languageSort');
    }
};
?>

@placeholder
<section class="flex flex-col gap-4">
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">@lang('lingua::lingua.languages.title')</flux:heading>
        <flux:subheading size="lg" class="mb-6">@lang('lingua::lingua.languages.subtitle')</flux:subheading>
        <flux:separator variant="subtle"/>
    </div>
    <div class="flex w-full items-center justify-between">
        <div class="flex w-1/4">
            <flux:skeleton animate="shimmer" class="h-10 w-40 rounded-md"/>
        </div>
        <div class="flex w-max gap-x-3 items-center">
            <flux:skeleton animate="shimmer" class="h-10 w-40 rounded-md"/>
            <flux:skeleton animate="shimmer" class="h-10 w-40 rounded-md"/>
            <flux:skeleton animate="shimmer" class="h-10 w-40 rounded-md"/>
        </div>
    </div>
    <div class="relative">
        <flux:table>
            <flux:table.columns>
                <flux:table.column class="w-1/4">@lang('lingua::lingua.languages.table.language')</flux:table.column>
                <flux:table.column class="grow">@lang('lingua::lingua.languages.table.status')</flux:table.column>
                <flux:table.column class="w-1/12" align="center">
                    <flux:icon.cog/>
                </flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach (range(1, 5) as $line)
                    <flux:table.row>
                        <flux:table.cell>
                            <flux:skeleton.group animate="shimmer" class="flex items-center gap-4">
                                <flux:skeleton class="size-10 rounded-full"/>
                                <div class="flex-1">
                                    <flux:skeleton.line/>
                                    <flux:skeleton.line class="w-1/2"/>
                                </div>
                            </flux:skeleton.group>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:skeleton.group animate="shimmer">
                                <flux:skeleton.line class="w-1/4"/>
                                <flux:skeleton.line/>
                            </flux:skeleton.group>
                        </flux:table.cell>
                        <flux:table.cell align="center" class="place-items-center">
                            <flux:skeleton animate="shimmer" class="size-10 rounded-md"/>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </div>
</section>
@endplaceholder

<section class="flex flex-col gap-4">
    <div class="relative w-full">
        <flux:heading size="xl" level="1">@lang('lingua::lingua.languages.title')</flux:heading>
        <flux:subheading size="lg" class="mb-6">@lang('lingua::lingua.languages.subtitle')</flux:subheading>
    </div>
    @island(name: 'languageSort', always: true)
        <livewire:lingua::language.sort :key="'sortLanguages_'. uniqid()"/>
    @endisland
    <flux:separator/>
    <div
        class="flex flex-col lg:flex-row w-full items-center justify-between sticky top-0 z-1 bg-white dark:bg-zinc-800 py-4 gap-4">
        <div class="flex w-1/4">
            <div class="relative w-full items-center justify-between">
                <flux:input type="search" wire:model.live.debounce.1000ms="search"
                            :placeholder="__('lingua::lingua.global.search')"
                            icon="magnifying-glass" wire:island="languagesRows"
                            name="searchLanguage" id="searchLanguage"/>
            </div>
        </div>
        <div class="flex flex-col lg:flex-row w-max gap-2 items-center">
            <flux:button wire:click="syncToLocal" icon="arrow-path" variant="primary"
                         color="sky">@lang('lingua::lingua.languages.actions.sync.local')</flux:button>
            <flux:button wire:click="syncToDatabase" icon="arrow-path" variant="primary"
                         color="sky">@lang('lingua::lingua.languages.actions.sync.database')</flux:button>
            <flux:button wire:click="updateLanguages" icon="arrow-down-on-square" variant="primary"
                         color="orange">@lang('lingua::lingua.languages.actions.update_lang')</flux:button>
            <livewire:lingua::language.create :key="'newLanguage_'. uniqid()"/>
        </div>
    </div>
    <div class="flex flex-col w-full gap-2">
        <x-lingua::message on="language_added">
            <flux:badge color="green">
                <div class="flex items-center gap-2" style="white-space: normal;">
                    <flux:icon icon="check-circle" size="sm"/>
                    <p>@lang('lingua::lingua.languages.create.save.new_language_added')</p>
                </div>
            </flux:badge>
        </x-lingua::message>
        <x-lingua::message on="language_added_fail">
            <flux:badge color="red">
                <div class="flex items-center gap-2" style="white-space: normal;">
                    <flux:icon icon="exclamation-circle" size="sm"/>
                    <p>@lang('lingua::lingua.languages.create.save.new_language_added_fail')</p>
                </div>
            </flux:badge>
        </x-lingua::message>
        <x-lingua::message on="synced_local">
            <flux:badge color="green">
                <div class="flex items-center gap-2" style="white-space: normal;">
                    <flux:icon icon="check-circle" size="sm"/>
                    <p>@lang('lingua::lingua.languages.actions.status.sync_local_done')</p>
                </div>
            </flux:badge>
        </x-lingua::message>
        <x-lingua::message on="synced_local_fail">
            <flux:badge color="red">
                <div class="flex items-center gap-2" style="white-space: normal;">
                    <flux:icon icon="exclamation-circle" size="sm"/>
                    <p>@lang('lingua::lingua.languages.actions.status.sync_local_fail')</p>
                </div>
            </flux:badge>
        </x-lingua::message>
        <x-lingua::message on="synced_database">
            <flux:badge color="green">
                <div class="flex items-center gap-2" style="white-space: normal;">
                    <flux:icon icon="check-circle" size="sm"/>
                    <p>@lang('lingua::lingua.languages.actions.status.sync_database_done')</p>
                </div>
            </flux:badge>
        </x-lingua::message>
        <x-lingua::message on="synced_database_fail">
            <flux:badge color="red">
                <div class="flex items-center gap-2" style="white-space: normal;">
                    <flux:icon icon="exclamation-circle" size="sm"/>
                    <p>@lang('lingua::lingua.languages.actions.status.sync_database_fail')</p>
                </div>
            </flux:badge>
        </x-lingua::message>
        <x-lingua::message on="lang_updated">
            <flux:badge color="green">
                <div class="flex items-center gap-2" style="white-space: normal;">
                    <flux:icon icon="check-circle" size="sm"/>
                    <p>@lang('lingua::lingua.languages.actions.status.lang_updated')</p>
                </div>
            </flux:badge>
        </x-lingua::message>
        <x-lingua::message on="lang_updated_fail">
            <flux:badge color="red">
                <div class="flex items-center gap-2" style="white-space: normal;">
                    <flux:icon icon="exclamation-circle" size="sm"/>
                    <p>@lang('lingua::lingua.languages.actions.status.lang_updated_fail')</p>
                </div>
            </flux:badge>
        </x-lingua::message>
    </div>
    <div class="relative">
        <livewire:lingua::language.table wire:model.live="search" lazy/>
    </div>
</section>
@assets
@once
    <link rel="stylesheet" href="{{ asset('vendor/lingua/css/lingua.min.css') }}">
@endonce
@endassets
