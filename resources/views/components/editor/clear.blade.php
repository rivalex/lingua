@blaze

<flux:tooltip :content="__('lingua::lingua.translations.editor.clear')">
    <button title="{{ __('lingua::lingua.translations.editor.clear') }}" tabindex="-1"
            x-on:mousedown.stop.prevent class="text-zinc-500 border-none bg-transparent p-[0.3rem] hover:cursor-pointer hover:bg-transparent hover:text-accent-content focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50 disabled:select-none" @click.stop.prevent="removeStyles()">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
             stroke="currentColor"
             stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
             class="lucide lucide-remove-formatting-icon lucide-remove-formatting">
            <path d="M4 7V4h16v3"/>
            <path d="M5 20h6"/>
            <path d="M13 4 8 20"/>
            <path d="m15 15 5 5"/>
            <path d="m20 15-5 5"/>
        </svg>
    </button>
</flux:tooltip>
