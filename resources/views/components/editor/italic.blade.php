@blaze

<flux:tooltip :content="__('lingua::lingua.translations.editor.italic')">
    <button title="{{ __('lingua::lingua.translations.editor.italic') }}" tabindex="-1"
            x-on:mousedown.stop.prevent class="border-none bg-transparent p-[0.3rem] hover:cursor-pointer hover:bg-transparent hover:text-accent-content focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50 disabled:select-none" @click.stop.prevent="toggleItalic()"
            x-bind:class="isActive('italic', updatedAt) ? 'text-accent-content' : 'text-zinc-500'">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
             stroke="currentColor"
             stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
             class="lucide lucide-italic-icon lucide-italic">
            <line x1="19" x2="10" y1="4" y2="4"/>
            <line x1="14" x2="5" y1="20" y2="20"/>
            <line x1="15" x2="9" y1="4" y2="20"/>
        </svg>
    </button>
</flux:tooltip>
