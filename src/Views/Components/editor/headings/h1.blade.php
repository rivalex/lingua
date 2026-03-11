@blaze

<flux:menu.item class="editor-button" tabindex="-1" @click.stop.prevent="toggleHeading({ level: 1 })" x-on:mousedown.stop.prevent>
    <x-slot name="icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
             class="lucide lucide-heading1-icon lucide-heading-1">
            <path d="M4 12h8"/>
            <path d="M4 18V6"/>
            <path d="M12 18V6"/>
            <path d="m17 12 3-2v8"/>
        </svg>
    </x-slot>
    <p class="headings-h1">@lang('lingua::lingua.translations.editor.headings.header-1')</p>
</flux:menu.item>
