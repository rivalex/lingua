@blaze

<flux:tooltip :content="__('rivalex::lingua.translations.editor.code-block')">
    <button title="{{ __('rivalex::lingua.translations.editor.code-block') }}"
            tabindex="-1" x-on:mousedown.stop.prevent class="editor-button" @click.stop.prevent="toggleCodeBlock()"
            x-bind:class="{ 'active' : isActive('codeBlock', updatedAt) }">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
             stroke="currentColor"
             stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
             class="lucide lucide-code-xml-icon lucide-code-xml">
            <path d="m18 16 4-4-4-4"/>
            <path d="m6 8-4 4 4 4"/>
            <path d="m14.5 4-5 16"/>
        </svg>
    </button>
</flux:tooltip>
