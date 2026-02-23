@blaze

<flux:menu.item class="editor-button" tabindex="-1" @click.stop.prevent="toggleHeading({ level: 3 })" x-on:mousedown.stop.prevent>
    <x-slot name="icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
             class="lucide lucide-heading3-icon lucide-heading-3">
            <path d="M4 12h8"/>
            <path d="M4 18V6"/>
            <path d="M12 18V6"/>
            <path d="M17.5 10.5c1.7-1 3.5 0 3.5 1.5a2 2 0 0 1-2 2"/>
            <path d="M17 17.5c2 1.5 4 .3 4-1.5a2 2 0 0 0-2-2"/>
        </svg>
    </x-slot>
    <p class="headings-h3">@lang('rivalex::lingua.translations.editor.headings.header-3')</p>
</flux:menu.item>
