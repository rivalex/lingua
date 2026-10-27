{{-- Lingua branding: fixed watermark (mark) + inline logo (horizontal) --}}
@once
<div class="fixed top-0 right-0 z-0 w-72 rotate-20 opacity-[0.042] blur-[3px] pointer-events-none select-none" aria-hidden="true">
    <img src="{{ route('lingua.assets', 'images/logoLinguaMark.svg') }}"
         alt="" class="block h-auto w-[85%] [color-scheme:light] dark:[color-scheme:dark]">
</div>
@endonce

<div class="flex justify-start mb-4">
    <img src="{{ route('lingua.assets', 'images/logoLinguaHorizzontal.svg') }}"
         alt="Lingua" class="block h-20 w-auto [color-scheme:light] dark:[color-scheme:dark]">
</div>

<flux:separator class="mb-4"/>
