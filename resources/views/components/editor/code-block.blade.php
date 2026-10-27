@blaze

<flux:tooltip :content="__('lingua::lingua.translations.editor.code-block')">
    <button title="{{ __('lingua::lingua.translations.editor.code-block') }}"
            tabindex="-1" x-on:mousedown.stop.prevent class="border-none bg-transparent p-[0.3rem] hover:cursor-pointer hover:bg-transparent hover:text-accent-content focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50 disabled:select-none" @click.stop.prevent="toggleCodeBlock()"
            x-bind:class="isActive('codeBlock', updatedAt) ? 'text-accent-content' : 'text-zinc-500'">
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
