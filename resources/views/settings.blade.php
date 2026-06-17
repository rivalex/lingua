<div class="lingua">
    <x-lingua::branding />
    <section class="flex flex-col gap-6">

        {{-- Header --}}
        <div class="relative w-full">
            <flux:heading size="xl" level="1">{{ __('lingua::lingua.settings.title') }}</flux:heading>
            <flux:subheading size="lg" class="mb-4">
                {{ __('lingua::lingua.settings.subtitle') }}
            </flux:subheading>
            <flux:separator variant="subtle"/>
        </div>

        {{-- Selector --}}
        <x-lingua::card
            :title="__('lingua::lingua.settings.selector.title')"
            :subtitle="__('lingua::lingua.settings.selector.subtitle')"
            icon="language">
            @include('lingua::settings.partials._selector')
        </x-lingua::card>

        {{-- Routing & navigation --}}
        <x-lingua::card
            :title="__('lingua::lingua.settings.routing.title')"
            :subtitle="__('lingua::lingua.settings.routing.subtitle')"
            icon="map-pin">
            @include('lingua::settings.partials._routing')
        </x-lingua::card>

        {{-- Editor toolbar --}}
        <x-lingua::card
            :title="__('lingua::lingua.settings.editor.title')"
            :subtitle="__('lingua::lingua.settings.editor.subtitle')"
            icon="pencil-square">
            @include('lingua::settings.partials._editor')
        </x-lingua::card>

        {{-- Save / toast --}}
        @include('lingua::settings.partials._save')

        {{-- lingua extension hook: settings.tabs --}}
        @foreach ($linguaExtensions->allSettingsTabComponents() as $cls)
            <livewire:dynamic-component :component="$cls" :key="'ext_settings_'.$cls"/>
        @endforeach

    </section>
</div>
@assets
@once
    <link rel="stylesheet" href="{{ route('lingua.assets', 'css/lingua.min.css') }}">
    <script type="module" src="{{ route('lingua.assets', 'js/lingua.min.js') }}"></script>
@endonce
@endassets
