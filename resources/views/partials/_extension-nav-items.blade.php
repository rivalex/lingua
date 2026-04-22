{{--
    Internal partial — renders navigation items provided by lingua extensions.

    Included by <x-lingua::extension-nav-items />.
    Do not include this partial directly; use the Blade component instead.

    Variables expected:
      $items       array<int, array{label, route, icon, active_pattern}>
      $itemClass   string  CSS class applied to each <a> element
--}}
@foreach ($items as $item)
    <a
        href="{{ route($item['route']) }}"
        class="{{ $itemClass }}"
        @if (request()->routeIs($item['active_pattern'])) aria-current="page" @endif
    >
        <flux:icon name="{{ $item['icon'] }}" size="sm"/>
        {{ $item['label'] }}
    </a>
@endforeach
