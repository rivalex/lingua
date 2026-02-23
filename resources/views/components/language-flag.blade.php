@props([
    'code' => '',
    'name' => '',
    'description' => '',
    'size' => 4
])
@if(Flux::pro())
    <div class="flex flex-row gap-2 items-center w-full">
        @if(!empty($code))
            <x-icon name="flag-language-{{ $code }}" class="w-{{ $size }} h-{{ $size }}"/>
        @endif
        <div class="flex flex-col grow leading-5 truncate">
            <div class="truncate">{{ $name }}</div>
            <div class="text-xs font-light text-gray-500 dark:text-gray-300 truncate">{{ $description }}</div>
        </div>
    </div>
@else
    {!! $name . '&nbsp;(' . $description . ')' !!}
@endif
