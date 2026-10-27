@blaze

<flux:tooltip :content="__('lingua::lingua.translations.editor.redo')">
    <button title="{{ __('lingua::lingua.translations.editor.redo') }}" tabindex="-1"
            x-on:mousedown.stop.prevent class="text-zinc-500 border-none bg-transparent p-[0.3rem] hover:cursor-pointer hover:bg-transparent hover:text-accent-content focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50 disabled:select-none" @click.stop.prevent="undo()" x-bind:disabled="!canUndo">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
             stroke="currentColor"
             stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
             class="lucide lucide-undo-icon lucide-undo">
            <path d="M3 7v6h6"/>
            <path d="M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13"/>
        </svg>
    </button>
</flux:tooltip>
