@props([
    'searchable'      => false,
    'clearable'       => false,
    'multiple'        => false,
    'placeholder'     => null,
    'empty'           => null,
    'size'            => null,
    'selected-suffix' => null,
    'label'           => null,
    'description'     => null,
    'badge'           => null,
    'disabled'        => false,
    'invalid'         => false,
    'required'        => false,
    'id'              => null,
])

@php
    $emptyText     = $empty ?? __('lingua::lingua.global.no_results_found');
    $suffixText    = $selectedSuffix ?? __('lingua::lingua.global.selected');
    $clearLabel    = __('lingua::lingua.global.clear');
    $searchLabel   = __('lingua::lingua.global.search');
    $selectId      = $id ?? 'ls-' . uniqid();
    $listboxId     = $selectId . '-lb';
    $resolvedBadge = $badge ?? ($required ? __('lingua::lingua.global.required') : null);

    $heightClass = match ($size) {
        'sm'    => 'h-8',
        'xs'    => 'h-7 text-xs',
        default => 'h-10',
    };

    $opts = [
        'multiple'       => $multiple,
        'searchable'     => $searchable,
        'clearable'      => $clearable,
        'emptyText'      => $emptyText,
        'selectedSuffix' => $suffixText,
        'clearLabel'     => $clearLabel,
        'selectId'       => $selectId,
        'listboxId'      => $listboxId,
        'disabled'       => $disabled,
        'invalid'        => $invalid,
        'placeholder'    => $placeholder ?? '',
    ];
@endphp

<div data-flux-field {{ $attributes->only(['class']) }}>
    @if ($label)
        <flux:label :for="$selectId" :badge="$resolvedBadge">{{ $label }}</flux:label>
    @endif
    @if ($description)
        <flux:description>{{ $description }}</flux:description>
    @endif

    <div
        data-lingua-select
        x-data="linguaSelect(@entangle($attributes->wire('model')), @js($opts))"
        class="relative w-full"
        {{ $attributes->filter(fn ($v, $k) => str_starts_with($k, 'wire:') && ! str_starts_with($k, 'wire:model')) }}
    >
        {{-- Trigger --}}
        <button
            x-ref="trigger"
            type="button"
            :id="selectId"
            data-flux-control
            role="combobox"
            aria-haspopup="listbox"
            :aria-expanded="open.toString()"
            :aria-controls="listboxId"
            :aria-activedescendant="activeId || null"
            :aria-invalid="invalid ? 'true' : null"
            :disabled="disabled"
            @click="toggleSelect()"
            @keydown="handleTriggerKeydown($event)"
            class="flex {{ $heightClass }} w-full items-center justify-between gap-1.5 rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/10 px-3 py-2 text-start shadow-xs focus:outline-hidden focus:ring-2 focus:ring-accent focus:ring-offset-2 focus:ring-offset-accent-foreground disabled:opacity-50 disabled:pointer-events-none sm:text-sm"
            :class="{ 'ring-2 ring-red-500 border-red-500': invalid }"
        >
            <span
                class="flex-1 truncate"
                :class="{ 'text-zinc-400 dark:text-zinc-500': ! hasValue() }"
                x-text="triggerText()"
            ></span>

            <div class="flex shrink-0 items-center gap-1.5">
                {{-- Clear button --}}
                <div
                    x-cloak
                    x-show="clearable && hasValue()"
                    role="button"
                    :aria-label="clearLabel"
                    @click.stop="clearValue()"
                    tabindex="-1"
                    class="shrink-0 rounded p-0.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 focus:outline-none cursor-pointer"
                >
                    <svg class="size-3.5" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                        <path d="M3.72 3.72a.75.75 0 0 1 1.06 0L8 6.94l3.22-3.22a.75.75 0 1 1 1.06 1.06L9.06 8l3.22 3.22a.75.75 0 1 1-1.06 1.06L8 9.06l-3.22 3.22a.75.75 0 0 1-1.06-1.06L6.94 8 3.72 4.78a.75.75 0 0 1 0-1.06z"/>
                    </svg>
                </div>

                {{-- Chevron --}}
                <svg
                    class="size-4 shrink-0 text-zinc-400 transition-transform duration-150"
                    :class="{ 'rotate-180': open }"
                    viewBox="0 0 16 16"
                    fill="currentColor"
                    aria-hidden="true"
                >
                    <path fill-rule="evenodd" d="M4.22 6.22a.75.75 0 0 1 1.06 0L8 8.94l2.72-2.72a.75.75 0 1 1 1.06 1.06l-3.25 3.25a.75.75 0 0 1-1.06 0L4.22 7.28a.75.75 0 0 1 0-1.06z" clip-rule="evenodd"/>
                </svg>
            </div>
        </button>

        {{-- Popover — promoted to browser top layer via Popover API so it escapes any
             ancestor overflow/transform (including Flux modals). position:fixed resolves
             to the viewport; JS sets exact coords from trigger.getBoundingClientRect(). --}}
        <div
            popover="manual"
            data-lingua-popover
            x-ref="popover"
            x-cloak
            x-show="open"
            @click.stop
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="opacity-0 translate-y-0.5"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-0.5"
            class="m-0 z-50 min-w-32 overflow-hidden rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-800 shadow-lg"
        >
            @if ($searchable)
                <div class="border-b border-zinc-100 dark:border-white/10 p-1.5">
                    <input
                        x-ref="searchInput"
                        type="text"
                        x-model.debounce.100ms="query"
                        role="searchbox"
                        :aria-controls="listboxId"
                        :aria-activedescendant="activeId || null"
                        placeholder="{{ $searchLabel }}"
                        @keydown="handleSearchKeydown($event)"
                        autocomplete="off"
                        class="w-full rounded-md border-0 bg-transparent px-2 py-1.5 text-sm text-zinc-900 dark:text-white placeholder:text-zinc-400 focus:outline-none focus:ring-1 focus:ring-accent"
                    />
                </div>
            @endif

            <ul
                x-ref="listbox"
                role="listbox"
                :aria-multiselectable="multiple ? 'true' : undefined"
                class="max-h-60 overflow-y-auto overflow-x-hidden p-1.5"
            >
                <template x-for="(opt, idx) in filtered" :key="String(opt.value)">
                    <li
                        :id="selectId + '-opt-' + idx"
                        role="option"
                        :aria-selected="isSelected(opt).toString()"
                        :aria-disabled="opt.disabled ? 'true' : undefined"
                        @click="selectOption(opt)"
                        @mouseenter="! opt.disabled && (activeIndex = idx)"
                        class="flex w-full cursor-default select-none items-center gap-2 rounded-md px-2 py-1.5 text-sm transition-colors"
                        :class="{
                            'bg-zinc-100 dark:bg-zinc-700 text-zinc-900 dark:text-white': activeIndex === idx && ! opt.disabled,
                            'bg-accent/10 dark:bg-accent/20 text-zinc-800 dark:text-zinc-200': isSelected(opt) && activeIndex !== idx,
                            'text-zinc-700 dark:text-zinc-300': ! opt.disabled && activeIndex !== idx && ! isSelected(opt),
                            'pointer-events-none text-zinc-400 dark:text-zinc-600': opt.disabled,
                        }"
                    >
                        <span class="flex min-w-0 flex-1 items-center" x-html="opt.html"></span>
                        <span x-show="isSelected(opt)" class="ms-auto shrink-0 text-accent">
                            <svg class="size-3.5" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M12.416 3.376a.75.75 0 0 1 .208 1.04l-5 7.5a.75.75 0 0 1-1.154.114l-3-3a.75.75 0 0 1 1.06-1.06l2.353 2.353 4.493-6.74a.75.75 0 0 1 1.04-.207z" clip-rule="evenodd"/>
                            </svg>
                        </span>
                    </li>
                </template>

                <li
                    x-show="filtered.length === 0"
                    role="presentation"
                    class="px-2 py-1.5 text-center text-sm text-zinc-500 dark:text-zinc-400"
                    x-text="emptyText"
                ></li>
            </ul>
        </div>

        {{-- Hidden slot: option data read by Alpine init() --}}
        <div x-ref="optionSlot" class="hidden" aria-hidden="true">{{ $slot }}</div>
    </div>
</div>
