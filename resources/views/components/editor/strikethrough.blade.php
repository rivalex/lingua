@blaze

<flux:tooltip :content="__('lingua::lingua.translations.editor.strikethrough')">
    <button title="{{ __('lingua::lingua.translations.editor.strikethrough') }}" tabindex="-1"
            x-on:mousedown.stop.prevent class="border-none bg-transparent p-[0.3rem] hover:cursor-pointer hover:bg-transparent hover:text-accent-content focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50 disabled:select-none" @click.stop.prevent="toggleStrike()"
            x-bind:class="isActive('strike', updatedAt) ? 'text-accent-content' : 'text-zinc-500'">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
             class="lucide lucide-strikethrough-icon lucide-strikethrough">
            <path d="M16 4H9a3 3 0 0 0-2.83 4"/>
            <path d="M14 12a4 4 0 0 1 0 8H6"/>
            <line x1="4" x2="20" y1="12" y2="12"/>
        </svg>
    </button>
</flux:tooltip>
