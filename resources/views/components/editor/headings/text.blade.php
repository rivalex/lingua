@blaze

<flux:menu.item class="editor-button" tabindex="-1" @click.stop.prevent="removeStyles()" x-on:mousedown.stop.prevent>
    <x-slot name="icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
             class="lucide lucide-pilcrow-icon lucide-pilcrow">
            <path d="M13 4v16"/>
            <path d="M17 4v16"/>
            <path d="M19 4H9.5a4.5 4.5 0 0 0 0 9H13"/>
        </svg>
    </x-slot>
    <p>@lang('rivalex::lingua.translations.editor.headings.paragraph')</p>
</flux:menu.item>
