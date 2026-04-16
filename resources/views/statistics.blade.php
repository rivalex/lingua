<div class="lingua">
    <section class="flex flex-col gap-4">

        {{-- Header --}}
        <div class="relative w-full">
            <flux:heading size="xl" level="1">{{ __('lingua::lingua.statistics.title') }}</flux:heading>
            <flux:subheading size="lg" class="mb-4">
                {{ $this->totalKeys }} {{ __('lingua::lingua.statistics.keys_label') }} &middot;
                {{ $this->totalGroups }} {{ __('lingua::lingua.statistics.groups_label') }} &middot;
                {{ $this->languages->count() }} {{ __('lingua::lingua.statistics.languages_label') }}
            </flux:subheading>
            <flux:separator variant="subtle"/>
        </div>

        {{-- Vendor toggle --}}
        <div class="flex items-center gap-3">
            <flux:field variant="inline" class="flex items-center gap-2 w-fit">
                <flux:label>
                    <p style="white-space: nowrap; font-weight: 400;">{{ __('lingua::lingua.statistics.include_vendor') }}</p>
                </flux:label>
                <flux:switch
                    :checked="$this->includeVendor"
                    wire:change="toggleVendor"
                />
            </flux:field>
        </div>

        {{-- Coverage per locale --}}
        @include('lingua::statistics.partials._coverage')

        {{-- Group breakdown --}}
        @include('lingua::statistics.partials._breakdown')

    </section>
</div>
@assets
@once
    <link rel="stylesheet" href="{{ route('lingua.assets', 'css/lingua.min.css') }}">
    <script type="module" src="{{ route('lingua.assets', 'js/lingua.min.js') }}"></script>
@endonce
@endassets
