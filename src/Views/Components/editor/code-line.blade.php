@blaze

<flux:tooltip :content="__('lingua::lingua.translations.editor.code-line')">
    <button title="{{ __('lingua::lingua.translations.editor.code-line') }}"
            tabindex="-1" x-on:mousedown.stop.prevent class="editor-button" @click.stop.prevent="toggleCode()"
            x-bind:class="{ 'active' : isActive('code', updatedAt) }">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
             class="lucide lucide-code-icon lucide-code">
            <path d="m16 18 6-6-6-6"/>
            <path d="m8 6-6 6 6 6"/>
        </svg>
    </button>
</flux:tooltip>
