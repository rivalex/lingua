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

</section>
@assets
@once
    <link rel="stylesheet" href="{{ route('lingua.assets', 'css/lingua.min.css') }}">
    <script type="module" src="{{ route('lingua.assets', 'js/lingua.min.js') }}"></script>
@endonce
@endassets
