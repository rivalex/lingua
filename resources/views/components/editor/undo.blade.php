@blaze

<flux:tooltip :content="__('rivalex::lingua.translations.editor.undo')">
    <button title="{{ __('rivalex::lingua.translations.editor.undo') }}" tabindex="-1"
            x-on:mousedown.stop.prevent class="editor-button" @click.stop.prevent="redo()" x-bind:disabled="!canRedo">
        <svg xmlns='http://www.w3.org/2000/svg' width="20" height="20" viewBox='0 0 24 24' fill='none'
             stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'
             class='lucide lucide-redo-icon lucide-redo'>
            <path d='M21 7v6h-6'/>
            <path d='M3 17a9 9 0 0 1 9-9 9 9 0 0 1 6 2.3l3 2.7'/>
        </svg>
    </button>
</flux:tooltip>
