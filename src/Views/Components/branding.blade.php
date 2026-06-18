{{-- Lingua branding: fixed watermark (mark) + inline logo (horizontal) --}}
@once
<div class="lingua-watermark rotate-20" aria-hidden="true"
     style="position:fixed;top:0;right:0;pointer-events:none;user-select:none;">
    <img src="{{ route('lingua.assets', 'images/logoLinguaMark.svg') }}"
         alt="" style="width:85%;height:auto;display:block;">
</div>
@endonce

<div class="lingua-logo" style="display:flex;justify-content:flex-start;margin-bottom:1rem;">
    <img src="{{ route('lingua.assets', 'images/logoLinguaHorizzontal.svg') }}"
         alt="Lingua" style="height:5rem;width:auto;display:block;">
</div>

<flux:separator class="mb-4"/>
