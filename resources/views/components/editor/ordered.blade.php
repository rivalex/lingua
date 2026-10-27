@blaze

<flux:tooltip :content="__('lingua::lingua.translations.editor.ordered')">
    <button title="{{ __('lingua::lingua.translations.editor.ordered') }}" tabindex="-1"
            x-on:mousedown.stop.prevent class="border-none bg-transparent p-[0.3rem] hover:cursor-pointer hover:bg-transparent hover:text-accent-content focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50 disabled:select-none" @click.stop.prevent="toggleOrderedList()"
            x-bind:class="isActive('orderedList', updatedAt) ? 'text-accent-content' : 'text-zinc-500'">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
             stroke="currentColor"
             stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
             class="lucide lucide-list-ordered-icon lucide-list-ordered">
            <path d="M11 5h10"/>
            <path d="M11 12h10"/>
            <path d="M11 19h10"/>
            <path d="M4 4h1v5"/>
            <path d="M4 9h2"/>
            <path d="M6.5 20H3.4c0-1 2.6-1.925 2.6-3.5a1.5 1.5 0 0 0-2.6-1.02"/>
        </svg>
    </button>
</flux:tooltip>
