@blaze

<flux:tooltip :content="__('lingua::lingua.translations.editor.blockquote')">
    <button title="{{ __('lingua::lingua.translations.editor.blockquote') }}" tabindex="-1"
            x-on:mousedown.stop.prevent class="editor-button" @click.stop.prevent="toggleBlockquote()"
            x-bind:class="{ 'active' : isActive('blockquote', updatedAt) }">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
             class="lucide lucide-text-quote-icon lucide-text-quote">
            <path d="M17 5H3"/>
            <path d="M21 12H8"/>
            <path d="M21 19H8"/>
            <path d="M3 12v7"/>
        </svg>
    </button>
</flux:tooltip>
