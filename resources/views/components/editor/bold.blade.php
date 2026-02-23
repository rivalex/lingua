@blaze

<flux:tooltip :content="__('rivalex::lingua.translations.editor.bold')">
    <button title="{{ __('rivalex::lingua.translations.editor.bold') }}" tabindex="-1"
            x-on:mousedown.stop.prevent class="editor-button" @click.stop.prevent="toggleBold()"
            x-bind:class="{ 'active' : isActive('bold', updatedAt) }">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
             class="lucide lucide-bold-icon lucide-bold">
            <path d="M6 12h9a4 4 0 0 1 0 8H7a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1h7a4 4 0 0 1 0 8"/>
        </svg>
    </button>
</flux:tooltip>
