@blaze

<flux:tooltip :content="__('rivalex::lingua.translations.editor.bullet')">
    <button title="{{ __('rivalex::lingua.translations.editor.bullet') }}" tabindex="-1"
            x-on:mousedown.stop.prevent class="editor-button" @click.stop.prevent="toggleBulletList()"
            x-bind:class="{ 'active' : isActive('bulletList', updatedAt) }">
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
