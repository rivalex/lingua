@blaze

<flux:tooltip :content="__('lingua::lingua.translations.editor.bullet')">
    <button title="{{ __('lingua::lingua.translations.editor.bullet') }}" tabindex="-1"
            x-on:mousedown.stop.prevent class="border-none bg-transparent p-[0.3rem] hover:cursor-pointer hover:bg-transparent hover:text-accent-content focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50 disabled:select-none" @click.stop.prevent="toggleBulletList()"
            x-bind:class="isActive('bulletList', updatedAt) ? 'text-accent-content' : 'text-zinc-500'">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
             stroke="currentColor"
             stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
             class="lucide lucide-list-icon lucide-list">
            <path d="M3 5h.01"/>
            <path d="M3 12h.01"/>
            <path d="M3 19h.01"/>
            <path d="M8 5h13"/>
            <path d="M8 12h13"/>
            <path d="M8 19h13"/>
        </svg>
    </button>
</flux:tooltip>
