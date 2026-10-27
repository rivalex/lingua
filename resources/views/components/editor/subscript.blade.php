@blaze

<flux:tooltip :content="__('lingua::lingua.translations.editor.subscript')">
    <button title="{{ __('lingua::lingua.translations.editor.subscript') }}" tabindex="-1"
            x-on:mousedown.stop.prevent class="border-none bg-transparent p-[0.3rem] hover:cursor-pointer hover:bg-transparent hover:text-accent-content focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50 disabled:select-none" @click.stop.prevent="toggleSubscript()"
            x-bind:class="isActive('subscript', updatedAt) ? 'text-accent-content' : 'text-zinc-500'">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
             stroke="currentColor"
             stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
             class="lucide lucide-subscript-icon lucide-subscript">
            <path d="m4 5 8 8"/>
            <path d="m12 5-8 8"/>
            <path
                d="M20 19h-4c0-1.5.44-2 1.5-2.5S20 15.33 20 14c0-.47-.17-.93-.48-1.29a2.11 2.11 0 0 0-2.62-.44c-.42.24-.74.62-.9 1.07"/>
        </svg>
    </button>
</flux:tooltip>
