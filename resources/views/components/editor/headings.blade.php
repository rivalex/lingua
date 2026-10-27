@blaze

<flux:dropdown position="bottom" align="start">
    <flux:tooltip :content="__('lingua::lingua.translations.editor.format')">
        <button class="text-zinc-500 border-none bg-transparent p-[0.3rem] hover:cursor-pointer hover:bg-transparent hover:text-accent-content focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50 disabled:select-none" tabindex="-1" x-on:mousedown.stop.prevent @click.stop.prevent>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                 stroke-linejoin="round" class="lucide lucide-heading-icon lucide-heading">
                <path d="M6 12h12"/>
                <path d="M6 20V4"/>
                <path d="M18 20V4"/>
            </svg>
        </button>
    </flux:tooltip>
    <flux:menu class="w-fit max-w-fit border border-zinc-200 bg-zinc-50 text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800">
        <x-lingua::editor.headings.h1/>
        <x-lingua::editor.headings.h2/>
        <x-lingua::editor.headings.h3/>
    </flux:menu>
</flux:dropdown>
