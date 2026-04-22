{{--
    <x-lingua::extension-nav-items />

    Opt-in Blade component for host application layouts.

    Renders navigation items contributed by installed lingua extensions
    (e.g. rivalex/lingua-pro adds Pro Dashboard + Driver Manager links).

    Usage in your layout:
        <x-lingua::extension-nav-items />

    With custom classes:
        <x-lingua::extension-nav-items
            wrapper-class="flex flex-col gap-1"
            item-class="flex items-center gap-2 px-3 py-2 rounded-md text-sm"
        />

    When no extensions are registered this renders nothing — safe to always include.
--}}
@props([
    'wrapperClass' => '',
    'itemClass'    => 'flex items-center gap-2',
])

@php
    $items = app(\Rivalex\Lingua\Services\ExtensionRegistry::class)->allNavigationItems();
@endphp

@if (count($items) > 0)
    <div class="{{ $wrapperClass }}">
        @include('lingua::partials._extension-nav-items', [
            'items'     => $items,
            'itemClass' => $itemClass,
        ])
    </div>
@endif
