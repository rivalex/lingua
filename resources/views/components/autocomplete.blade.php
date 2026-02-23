@props([
    'label' => '',
    'options' => [],
    'placeholder' => '',
    'disabled' => false
])
<div
    x-data="autocomplete(@entangle($attributes->wire('model')), @js($options), @js($disabled))" class="w-full disabled"
    data-flux-field>
    @if(!empty($label))
        <flux:label x-bind:for="id"
                    :badge="$attributes->has('required') ? __('lingua::lingua.global.required') : null">{{ $label }}</flux:label>
    @endif
    <flux:with-field :$attributes>
        <!-- Combobox -->
        <div x-combobox x-model="value" class="autocomplete_wrapper">
            <div class="autocomplete_container group">
                <!-- Combobox Input -->
                <input
                    data-flux-control
                    :id="id"
                    x-bind:disabled="disabled"
                    x-combobox:input
                    @change="value = $event.target.value; query = $event.target.value;"
                    class="autocomplete_input"
                    placeholder="{{ $placeholder ?? '' }}"
                    autocomplete="off"/>
                <!-- Combobox Button -->
                <button x-combobox:button tabindex="-1" class="autocomplete_combo_button" x-bind:disabled="disabled">
                    <!-- Heroicons up/down -->
                    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" aria-hidden="true">
                        <path d="M7 7l3-3 3 3m0 6l-3 3-3-3" stroke-width="1.5" stroke-linecap="round"
                              stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>

            <!-- Combobox Options -->
            <div x-combobox:options x-cloak class="autocomplete_option_container">
                <ul class="">
                    <template x-for="opt in filteredOptions" :key="opt.id">
                        <!-- Combobox Option -->
                        <li
                            :id="'option-' + opt.id"
                            x-combobox:option
                            :value="opt.name"
                            :disabled="opt.disabled"
                            :class="{
                            'bg-zinc-100 dark:bg-zinc-800': $comboboxOption.isActive,
                            'text-zinc-800 dark:text-zinc-300': ! $comboboxOption.isActive && ! $comboboxOption.isDisabled,
                            'text-zinc-400 cursor-not-allowed': $comboboxOption.isDisabled,
                        }"
                            class="group autocomplete_option_li">
                            <span x-text="opt.name"></span>
                        </li>
                    </template>
                </ul>

                <p x-show="filteredOptions.length == 0"
                   class="px-2 py-1.5 text-zinc-600 dark:text-zinc-400">{{ __('lingua::lingua.global.no_results_found') }}</p>
            </div>
        </div>
    </flux:with-field>
</div>

{{--@assets--}}
{{--@once--}}
{{--    <script type="module" src="{{ lt_asset('js/lingua-alpine.min.js') }}"></script>--}}
{{--@endonce--}}
{{--@endassets--}}
