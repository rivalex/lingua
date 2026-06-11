{{-- Lingua branding: fixed watermark (mark) + inline logo (horizontal) --}}
@once
<div class="lingua-watermark rotate-20" aria-hidden="true"
     style="position:fixed;top:0;right:0;width:18rem;opacity:.042;pointer-events:none;user-select:none;z-index:0;">
    <img src="{{ route('lingua.assets', 'images/logoLinguaMark.svg') }}"
         alt=""
         width="288"
         height="288"
         style="width:100%;height:auto;display:block;">
</div>
@endonce

<div class="lingua-logo" style="display:flex;justify-content:flex-start;margin-bottom:1rem;">
    <img src="{{ route('lingua.assets', 'images/logoLinguaHorizzontal.svg') }}"
         alt="Lingua"
         width="auto"
         height="32"
         style="height:2rem;width:auto;display:block;">
</div>

<flux:separator class="mb-4"/>
