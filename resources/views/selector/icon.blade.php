<div wire:ignore {{ $attributes->class('') }}>
    @if($showFlags)
        {!! $languageFlag !!}
    @else
        <div @class($textIconClasses)>
            {{ $locale }}
        </div>
    @endif
</div>
