@props([
    'value' => null,
    'label' => null,
    'selected-label' => null,
    'disabled' => false,
])

@php
    $searchParts = array_filter([
        $label,
        $selectedLabel ?? null,
        strip_tags((string) $slot),
    ]);
    $searchText   = trim(implode(' ', $searchParts));
    $displayLabel = $selectedLabel ?? $label ?? trim(strip_tags((string) $slot));
@endphp
<li
    data-lingua-option
    data-value="{{ json_encode($value) }}"
    data-search="{{ $searchText }}"
    data-selected-label="{{ $displayLabel }}"
    data-disabled="{{ $disabled ? 'true' : 'false' }}"
>{{ $slot }}</li>
