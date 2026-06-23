<div class="lingua">
    <x-lingua::branding />
    <section class="flex flex-col gap-6">

        {{-- Header --}}
        <div class="relative w-full">
            <flux:heading size="xl" level="1">{{ __('lingua::lingua.statistics.title') }}</flux:heading>
            <flux:separator variant="subtle" class="mt-4"/>
        </div>

        {{-- KPI tiles --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <x-lingua::stat
                :value="$this->totalKeys"
                :label="__('lingua::lingua.statistics.keys_label')"
                icon="key"
            />
            <x-lingua::stat
                :value="$this->totalGroups"
                :label="__('lingua::lingua.statistics.groups_label')"
                icon="rectangle-stack"
            />
            <x-lingua::stat
                :value="$this->languages->count()"
                :label="__('lingua::lingua.statistics.languages_label')"
                icon="language"
            />
        </div>

        {{-- Coverage per locale --}}
        @include('lingua::statistics.partials._coverage')

        {{-- Group breakdown --}}
        @include('lingua::statistics.partials._breakdown')

        {{-- lingua extension hook: dashboard.widgets --}}
        @foreach ($linguaExtensions->allDashboardWidgetComponents() as $cls)
            <livewire:dynamic-component :component="$cls" :key="'ext_widget_'.$cls"/>
        @endforeach

    </section>
</div>
@assets
@once
    <link rel="stylesheet" href="{{ linguaAssetUrl('css/lingua.min.css') }}">
    <script type="module" src="{{ linguaAssetUrl('js/lingua.min.js') }}"></script>
@endonce
@endassets
