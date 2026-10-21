@blaze

<flux:tooltip :content="__('lingua::lingua.translations.editor.code')">
    <button title="{{ __('lingua::lingua.translations.editor.code') }}" tabindex="-1"
            x-on:mousedown.stop.prevent class="editor-button" @click.stop.prevent="toggleSourceCode()">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
             stroke="currentColor"
             stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
             class="lucide lucide-square-code-icon lucide-square-code">
            <path d="m10 9-3 3 3 3"/>
            <path d="m14 15 3-3-3-3"/>
            <rect x="3" y="3" width="18" height="18" rx="2"/>
        </svg>
    </button>
</flux:tooltip>
