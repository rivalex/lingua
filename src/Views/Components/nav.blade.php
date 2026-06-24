{{-- Lingua shared page navigation menu --}}
@php
    use Rivalex\Lingua\Models\LinguaSetting;

    if (! (bool) LinguaSetting::get(LinguaSetting::KEY_NAV_ENABLED, config('lingua.nav.enabled', true))) {
        return;
    }

    $navItems = [
        [
            'route' => 'lingua.languages',
            'icon'  => 'language',
            'label' => __('lingua::lingua.transfer.nav.languages'),
        ],
        [
            'route' => 'lingua.translations',
            'icon'  => 'document-text',
            'label' => __('lingua::lingua.transfer.nav.translations'),
        ],
        [
            'route' => 'lingua.statistics',
            'icon'  => 'chart-bar',
            'label' => __('lingua::lingua.transfer.nav.statistics'),
        ],
        [
            'route' => 'lingua.transfer',
            'icon'  => 'arrows-right-left',
            'label' => __('lingua::lingua.transfer.nav.transfer'),
        ],
        [
            'route' => 'lingua.settings',
            'icon'  => 'cog-6-tooth',
            'label' => __('lingua::lingua.transfer.nav.settings'),
        ],
    ];
@endphp

<div class="flex flex-wrap items-center gap-2 text-sm">
    @foreach ($navItems as $item)
        @php $active = request()->routeIs($item['route']); @endphp
        <flux:button
            href="{{ route($item['route']) }}"
            :variant="$active ? 'filled' : 'ghost'"
            size="sm"
            :icon="$item['icon']"
            :aria-current="$active ? 'page' : false">
            {{ $item['label'] }}
        </flux:button>
    @endforeach
</div>

<flux:separator class="my-2" />
