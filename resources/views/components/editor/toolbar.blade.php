@props([
    'type' => 'html'
    ])
<div class="toolbar flex w-full min-w-full flex-row flex-wrap items-center justify-between gap-1 rounded-t-lg border border-b-0 border-zinc-200 bg-zinc-50 p-1 dark:border-white/10 dark:bg-zinc-800 [&_[data-flux-separator]]:mx-1 [&_[data-flux-separator]]:my-[0.45rem]" x-ref="toolbar">
    <div class="flex flex-wrap items-start">
        @if(config('lingua.editor.headings'))
            <x-lingua::editor.headings/>
        @endif
        @if(config('lingua.editor.bold'))
            <x-lingua::editor.bold/>
        @endif
        @if(config('lingua.editor.italic'))
            <x-lingua::editor.italic/>
        @endif
        @if(config('lingua.editor.underline'))
            <x-lingua::editor.underline/>
        @endif
        @if(config('lingua.editor.strikethrough'))
            <x-lingua::editor.strikethrough/>
        @endif
        @if($type === 'html' && (config('lingua.editor.subscript') || config('lingua.editor.superscript')))
            <flux:separator vertical/>
            @if(config('lingua.editor.subscript'))
                <x-lingua::editor.subscript/>
            @endif
            @if(config('lingua.editor.superscript'))
                <x-lingua::editor.superscript/>
            @endif
        @endif
        @if(config('lingua.editor.blockquote') || config('lingua.editor.code-line') || config('lingua.editor.code-block'))
            <flux:separator vertical/>
            @if(config('lingua.editor.blockquote'))
                <x-lingua::editor.blockquote/>
            @endif
            @if(config('lingua.editor.code-line'))
                <x-lingua::editor.code-line/>
            @endif
            @if(config('lingua.editor.code-block'))
                <x-lingua::editor.code-block/>
            @endif
        @endif
        @if(config('lingua.editor.bullet') || config('lingua.editor.ordered'))
            <flux:separator vertical/>
            @if(config('lingua.editor.bullet'))
                <x-lingua::editor.bullet/>
            @endif
            @if(config('lingua.editor.ordered'))
                <x-lingua::editor.ordered/>
            @endif
        @endif
    </div>
    <div>
        @if(config('lingua.editor.clear'))
            <x-lingua::editor.clear/>
        @endif
        @if(config('lingua.editor.code-mode'))
            <x-lingua::editor.code-mode/>
        @endif
        <x-lingua::editor.redo/>
        <x-lingua::editor.undo/>
    </div>
</div>
