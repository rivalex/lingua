@props([
    'label' => '',
    'options' => [],
    'placeholder' => '',
    'disabled' => false
])

<x-lingua::select
    searchable
    :placeholder="$placeholder ?: null"
    :disabled="$disabled"
    :label="! empty($label) ? $label : null"
    :badge="$attributes->has('required') ? __('lingua::lingua.global.required') : null"
    {{ $attributes->whereStartsWith('wire:') }}>
    @foreach($options as $opt)
        <x-lingua::select.option
            :value="$opt['name']"
            :disabled="$opt['disabled'] ?? false">{{ $opt['name'] }}</x-lingua::select.option>
    @endforeach
</x-lingua::select>
