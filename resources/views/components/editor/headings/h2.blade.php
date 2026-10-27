@blaze

<flux:menu.item class="text-zinc-500 border-none bg-transparent p-[0.3rem] hover:cursor-pointer hover:bg-transparent hover:text-accent-content focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50 disabled:select-none" tabindex="-1" @click.stop.prevent="toggleHeading({ level: 2 })" x-on:mousedown.stop.prevent>
    <x-slot name="icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
             class="lucide lucide-heading2-icon lucide-heading-2">
            <path d="M4 12h8"/>
            <path d="M4 18V6"/>
            <path d="M12 18V6"/>
            <path d="M21 18h-4c0-4 4-3 4-6 0-1.5-2-2.5-4-1"/>
        </svg>
    </x-slot>
    <p class="px-[0.3rem] text-[1.25rem] font-semibold">@lang('lingua::lingua.translations.editor.headings.header-2')</p>
</flux:menu.item>
