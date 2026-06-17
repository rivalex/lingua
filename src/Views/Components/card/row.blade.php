{{--
    Two-column settings row.
    Left column: title + description context.
    Right column: controls (slot).
    Use inside x-lingua::card — divide-y on the card body separates rows.
--}}
@props([
    'title'       => null,
    'description' => null,
])

<div class="grid gap-4 px-6 py-6 sm:grid-cols-3 sm:gap-6">

    {{-- Left: label + description --}}
    <div class="sm:col-span-1">
        @if ($title)
        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $title }}</h3>
        @endif
        @if ($description)
        <p class="mt-1 text-sm leading-relaxed text-zinc-500 dark:text-zinc-400">{{ $description }}</p>
        @endif
    </div>

    {{-- Right: controls --}}
    <div class="sm:col-span-2 flex flex-col gap-4">
        {{ $slot }}
    </div>

</div>
