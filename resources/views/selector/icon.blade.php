{{-- Classes built in Selector\Icon (PHP) that appear nowhere else: text-[.625rem] rounded-sm h-6 w-6 w-10 --}}
<div wire:ignore {{ $attributes->class('') }}>
    @if($showFlags)
        <x-lingua::flag :code="$locale" :circle="! $square" :class="$flagClass"/>
    @else
        <div @class($textIconClasses)>
            {{ $locale }}
        </div>
    @endif
</div>
