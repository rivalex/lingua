<div class="lingua">
    <section class="flex flex-col gap-4">

        {{-- Header --}}
        <div class="relative w-full">
            <flux:heading size="xl" level="1">{{ __('lingua::lingua.settings.title') }}</flux:heading>
            <flux:subheading size="lg" class="mb-4">
                {{ __('lingua::lingua.settings.subtitle') }}
            </flux:subheading>
            <flux:separator variant="subtle"/>
        </div>

        {{-- Selector settings --}}
        @include('lingua::settings.partials._selector')

        <flux:separator variant="subtle"/>

        {{-- Routing & navigation --}}
        @include('lingua::settings.partials._routing')

        <flux:separator variant="subtle"/>

        {{-- Editor toolbar --}}
        @include('lingua::settings.partials._editor')

        <flux:separator variant="subtle"/>

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
