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

        <flux:tabs>
            <flux:tab name="export">{{ __('lingua::lingua.transfer.tabs.export') }}</flux:tab>
            <flux:tab name="import">{{ __('lingua::lingua.transfer.tabs.import') }}</flux:tab>
        </flux:tabs>

        <flux:tab.panel name="export">
            <livewire:lingua::export />
        </flux:tab.panel>

        <flux:tab.panel name="import">
            <livewire:lingua::import />
        </flux:tab.panel>
    </section>
</div>
