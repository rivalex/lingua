@props([
    'type',
    'size' => 4,
])
{{-- Translation type icon. Only size 4 (h-4 w-4) exists; every class is a literal so the host compiles it. --}}
<div class="h-4 w-4 {{ match ($type) {
    \Rivalex\Lingua\Enums\LinguaType::any => 'text-zinc-400 dark:text-zinc-600',
    \Rivalex\Lingua\Enums\LinguaType::text => 'text-zinc-600 dark:text-zinc-400',
    \Rivalex\Lingua\Enums\LinguaType::html => 'text-sky-600 dark:text-sky-500',
    \Rivalex\Lingua\Enums\LinguaType::markdown => 'text-orange-600 dark:text-orange-500',
} }}">{!! $type->icon() !!}</div>
