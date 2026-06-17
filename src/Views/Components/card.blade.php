{{--
    Lingua card container.
    Props: title (string, optional), subtitle (string, optional), icon (heroicon name, optional).
    Named slots: $actions (header right-side, optional).
--}}
@props([
    'title'    => null,
    'subtitle' => null,
    'icon'     => null,
])

<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900']) }}>

    {{-- Card header --}}
    @if ($title || isset($actions))
    <div class="flex items-center justify-between gap-4 border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
        <div class="flex min-w-0 items-center gap-3">
            @if ($icon)
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800">
                <flux:icon :name="$icon" size="sm" class="text-zinc-500 dark:text-zinc-400"/>
            </div>
            @endif
            <div class="min-w-0">
                @if ($title)
                <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $title }}</p>
                @endif
                @if ($subtitle)
                <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $subtitle }}</p>
                @endif
            </div>
        </div>
        @if (isset($actions))
        <div class="shrink-0">{{ $actions }}</div>
        @endif
    </div>
    @endif

    {{-- Card body: divide-y separates x-lingua::card.row children --}}
    <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
        {{ $slot }}
    </div>

</div>
