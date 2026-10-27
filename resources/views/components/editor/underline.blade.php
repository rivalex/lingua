@blaze

<flux:tooltip :content="__('lingua::lingua.translations.editor.underline')">
    <button title="{{ __('lingua::lingua.translations.editor.underline') }}" tabindex="-1"
            x-on:mousedown.stop.prevent class="border-none bg-transparent p-[0.3rem] hover:cursor-pointer hover:bg-transparent hover:text-accent-content focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50 disabled:select-none" @click.stop.prevent="toggleUnderline()"
            x-bind:class="isActive('underline', updatedAt) ? 'text-accent-content' : 'text-zinc-500'">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
             stroke="currentColor"
             stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
             class="lucide lucide-underline-icon lucide-underline">
            <path d="M6 4v6a6 6 0 0 0 12 0V4"/>
            <line x1="4" x2="20" y1="20" y2="20"/>
        </svg>
    </button>
</flux:tooltip>
