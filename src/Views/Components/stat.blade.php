{{--
    KPI stat tile.
    Props: value (int|string), label (string), icon (heroicon name, optional).
--}}
@props([
    'value' => 0,
    'label' => '',
    'icon'  => null,
])

<div {{ $attributes->merge(['class' => 'flex items-start justify-between rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900']) }}>
    <div>
        <p class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ $label }}</p>
        <p class="mt-2 text-3xl font-semibold tabular-nums text-zinc-900 dark:text-white">{{ $value }}</p>
    </div>
    @if ($icon)
    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800">
        <flux:icon :name="$icon" size="sm" class="text-zinc-500 dark:text-zinc-400"/>
    </div>
    @endif
</div>
