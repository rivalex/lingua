@blaze

<flux:tooltip :content="__('lingua::lingua.translations.editor.superscript')">
    <button title="{{ __('lingua::lingua.translations.editor.superscript') }}"
            tabindex="-1" x-on:mousedown.stop.prevent class="border-none bg-transparent p-[0.3rem] hover:cursor-pointer hover:bg-transparent hover:text-accent-content focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50 disabled:select-none" @click.stop.prevent="toggleSuperscript()"
            x-bind:class="isActive('superscript', updatedAt) ? 'text-accent-content' : 'text-zinc-500'">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
             stroke="currentColor"
             stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
             class="lucide lucide-superscript-icon lucide-superscript">
            <path d="m4 19 8-8"/>
            <path d="m12 19-8-8"/>
            <path
                d="M20 12h-4c0-1.5.442-2 1.5-2.5S20 8.334 20 7.002c0-.472-.17-.93-.484-1.29a2.105 2.105 0 0 0-2.617-.436c-.42.239-.738.614-.899 1.06"/>
        </svg>
    </button>
</flux:tooltip>
