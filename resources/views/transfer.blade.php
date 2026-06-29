<div class="lingua">
    <x-lingua::branding />

    <section class="flex flex-col gap-4">
        <div class="relative w-full">
            <flux:heading size="xl" level="1">{{ __('lingua::lingua.transfer.title') }}</flux:heading>
            <flux:subheading size="lg" class="mb-6">{{ __('lingua::lingua.transfer.subtitle') }}</flux:subheading>
        </div>

        <x-lingua::nav />

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
@assets
@once
    <link rel="stylesheet" href="{{ linguaAssetUrl('css/lingua.min.css') }}">
    <script type="module" src="{{ linguaAssetUrl('js/lingua.min.js') }}"></script>
@endonce
@endassets
