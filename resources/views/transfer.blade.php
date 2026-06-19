<div class="lingua">
    <x-lingua::branding />
    <section class="flex flex-col gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('lingua::lingua.transfer.title') }}</flux:heading>
            <flux:subheading size="lg">{{ __('lingua::lingua.transfer.subtitle') }}</flux:subheading>
        </div>

        {{-- Page navigation links --}}
        <div class="flex flex-wrap items-center gap-2 text-sm">
            <flux:button href="{{ route('lingua.languages') }}" variant="ghost" size="sm" icon="language">
                {{ __('lingua::lingua.transfer.nav.languages') }}
            </flux:button>
            <flux:button href="{{ route('lingua.translations') }}" variant="ghost" size="sm" icon="document-text">
                {{ __('lingua::lingua.transfer.nav.translations') }}
            </flux:button>
            <flux:button href="{{ route('lingua.statistics') }}" variant="ghost" size="sm" icon="chart-bar">
                {{ __('lingua::lingua.transfer.nav.statistics') }}
            </flux:button>
            <flux:button href="{{ route('lingua.settings') }}" variant="ghost" size="sm" icon="cog-6-tooth">
                {{ __('lingua::lingua.transfer.nav.settings') }}
            </flux:button>
        </div>

        <flux:separator />

        <div x-data="{ tab: 'export' }">
            <div role="tablist" class="flex border-b border-zinc-200 dark:border-zinc-700">
                <button
                    type="button"
                    role="tab"
                    @click="tab = 'export'"
                    :aria-selected="tab === 'export'"
                    :class="tab === 'export'
                        ? 'border-b-2 border-accent text-zinc-900 dark:text-white'
                        : 'border-b-2 border-transparent text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200'"
                    class="-mb-px px-4 py-2 text-sm font-medium transition-colors">
                    {{ __('lingua::lingua.transfer.tabs.export') }}
                </button>
                <button
                    type="button"
                    role="tab"
                    @click="tab = 'import'"
                    :aria-selected="tab === 'import'"
                    :class="tab === 'import'
                        ? 'border-b-2 border-accent text-zinc-900 dark:text-white'
                        : 'border-b-2 border-transparent text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200'"
                    class="-mb-px px-4 py-2 text-sm font-medium transition-colors">
                    {{ __('lingua::lingua.transfer.tabs.import') }}
                </button>
            </div>

            <div role="tabpanel" x-show="tab === 'export'" x-cloak class="pt-4">
                <livewire:lingua::export />
            </div>

            <div role="tabpanel" x-show="tab === 'import'" x-cloak class="pt-4">
                <livewire:lingua::import />
            </div>
        </div>
    </section>
</div>
