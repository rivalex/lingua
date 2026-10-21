<?php

namespace Rivalex\Lingua\Livewire;

use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Async;
use Livewire\Attributes\On;
use Livewire\Attributes\Renderless;
use Livewire\Attributes\Title;
use Livewire\Component;
use Rivalex\Lingua\Lingua;
use Rivalex\Lingua\Models\Translation;

#[Title('UI Translation Manager')]
class Languages extends Component
{
    public string $search = '';

    #[Renderless, Async]
    public function updateLanguages(): void
    {
        try {
            Lingua::updateLanguages();
            app(Translation::class)->syncToDatabase();
            Lingua::optimize();
            $this->dispatch('lang_updated');
            $this->dispatch('refreshLanguages');
        } catch (\Throwable $e) {
            $this->dispatch('lang_updated_fail');
            $this->addError('updateLanguagesError', $e->getMessage());
            Log::error('Translations UPDATE failed! {error}', ['error' => $e->getMessage()]);
        }
    }

    #[Renderless, Async]
    public function syncToDatabase(): void
    {
        try {
            app(Translation::class)->syncToDatabase();
            Lingua::optimize();
            $this->dispatch('synced_database');
            $this->dispatch('refreshLanguages');
        } catch (\Throwable $e) {
            $this->dispatch('synced_database_fail');
            $this->addError('syncToDatabaseError', $e->getMessage());
            Log::error('Translations DATABASE sync failed! {error}', ['error' => $e->getMessage()]);
        }
    }

    #[Renderless, Async]
    public function syncToLocal(): void
    {
        try {
            app(Translation::class)->syncToLocal();
            Lingua::optimize();
            $this->dispatch('synced_local');
            $this->dispatch('refreshLanguages');
        } catch (\Throwable $e) {
            $this->dispatch('synced_local_fail');
            $this->addError('syncToLocalError', $e->getMessage());
            Log::error('Translations LOCAL sync failed! {error}', ['error' => $e->getMessage()]);
        }
    }

    #[On('refreshLanguages')]
    public function refreshSortList(): void
    {
        $this->renderIsland('languageSort');
    }

    public function placeholder(): string
    {
        return <<<'HTML'
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
        HTML;
    }

    public function render()
    {
        return view('lingua::languages');
    }
}
