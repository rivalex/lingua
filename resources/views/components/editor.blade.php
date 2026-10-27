@props([
    'required' => false,
    'type' => 'text',
    'placeholder' => null,
    'disabled' => false,
    'label' => null,
    'helper' => true
])
<div @class([
    'flex flex-col gap-2',
    '[&_.toolbar]:border-red-500 [&_.tiptap]:border-red-500!' => $errors->has($attributes->wire('model')->value())
])>
    @if($errors->has($attributes->wire('model')->value()))
        <p class="inline-block pb-[0.2rem] text-[0.7rem] text-red-500">
            {{ $errors->first($attributes->wire('model')->value()) }}
        </p>
    @endif
    @if($type === 'text')
        <div data-flux-field>
            @php
                $uid = 'editor-' . rand();
            @endphp
            @if(!empty($label))
                <flux:label for="{{ $uid }}"
                    :badge="$required ? __('lingua::lingua.global.required') : null">{{ $label }}</flux:label>
            @endif
            <flux:textarea
                id="{{ $uid }}"
                {{ $attributes }}
                placeholder="{{ $placeholder }}"
                :disabled="$disabled"
            />
        </div>
    @else
        @php
            $wireModel = $attributes->wire('model');
        @endphp
        <div
            x-data="tiptap(@entangle($wireModel), @js($type), @js($placeholder), @js($disabled))"
            x-on:pointerdown.stop
            x-on:click.stop
            wire:ignore
            data-flux-field
            @class([
                '[&_.tiptap]:outline-none [&_.tiptap]:p-2 [&_.tiptap]:w-full [&_.tiptap]:min-h-[100px] [&_.tiptap]:max-h-[150px] [&_.tiptap]:overflow-y-auto [&_.tiptap]:text-[0.8rem] [&_.tiptap]:bg-white [&_.tiptap]:rounded-b-lg [&_.tiptap]:border [&_.tiptap]:border-zinc-200 [&_.tiptap]:shadow-sm dark:[&_.tiptap]:bg-white/10 dark:[&_.tiptap]:border-white/10 dark:[&_.tiptap]:text-zinc-300 dark:[&_.tiptap]:shadow-xs [&_p.is-editor-empty:first-child]:before:content-[attr(data-placeholder)] [&_p.is-editor-empty:first-child]:before:float-left [&_p.is-editor-empty:first-child]:before:h-0 [&_p.is-editor-empty:first-child]:before:text-zinc-400 [&_p.is-editor-empty:first-child]:before:pointer-events-none [&_.tiptap_p]:m-0 [&_.tiptap_ul]:mb-4 [&_.tiptap_ul]:list-disc [&_.tiptap_ul]:pl-6 [&_.tiptap_ol]:mb-4 [&_.tiptap_ol]:list-decimal [&_.tiptap_ol]:pl-6 [&_.tiptap_strong]:font-semibold [&_.tiptap_b]:font-semibold [&_.tiptap_em]:italic [&_.tiptap_u]:underline [&_.tiptap_sub]:align-sub [&_.tiptap_sub]:text-[length:smaller] [&_.tiptap_sup]:align-super [&_.tiptap_sup]:text-[length:smaller] [&_.tiptap_a]:text-accent-content [&_.tiptap_h1]:mb-2 [&_.tiptap_h1]:text-[1.5rem] [&_.tiptap_h1]:font-semibold [&_.tiptap_h2]:text-[1.25rem] [&_.tiptap_h2]:font-semibold [&_.tiptap_h3]:text-[1rem] [&_.tiptap_h4]:text-[0.875rem] [&_.tiptap_h5]:text-[0.8rem] [&_.tiptap_h6]:text-[0.75rem] [&_.tiptap_hr]:border [&_.tiptap_hr]:border-zinc-200 [&_.tiptap_blockquote]:border-l-2 [&_.tiptap_blockquote]:border-zinc-200 [&_.tiptap_blockquote]:pl-2 [&_.tiptap_pre]:my-2 [&_.tiptap_pre]:rounded-md [&_.tiptap_pre]:bg-zinc-100 [&_.tiptap_pre]:p-2 dark:[&_.tiptap_pre]:bg-white/10 [&_.tiptap_code]:px-1 [&_.tiptap_code]:py-0.5',
                'error' => $errors->has($attributes->wire('model')->value())
            ])
        >
            @if(!empty($label))
                <flux:label x-bind:for="id"
                    :badge="$required ? __('lingua::lingua.global.required') : null">{{ $label }}</flux:label>
            @endif
            <ui-lingua-editor @if(config('lingua.editor.code-mode')) x-cloak x-show="!sourceView" @endif
                x-on:pointerdown.stop
                x-on:click.stop
                {{ $attributes->whereDoesntStartWith('wire:model')->class('') }}>
                <!-- Toolbar -->
                <x-lingua::editor.toolbar :type="$type"/>
                <!-- Editor -->
                <div x-ref="editor" :id="id"></div>
            </ui-lingua-editor>
            @if(config('lingua.editor.code-mode'))
                <div x-cloak x-show="sourceView">
                    <div class="toolbar flex w-full min-w-full flex-wrap items-center gap-1 rounded-t-lg border border-b-0 border-zinc-200 bg-zinc-50 p-1 dark:border-white/10 dark:bg-zinc-800 [&_[data-flux-separator]]:mx-1 [&_[data-flux-separator]]:my-[0.45rem]" x-ref="toolbar">
                        <p class="px-[0.35rem] font-mono text-[0.7rem] font-semibold text-zinc-500 dark:text-zinc-800">{{ __('lingua::lingua.translations.editor.code-mode') }}</p>
                        <flux:spacer vertical/>
                        <x-lingua::editor.code-mode @click="toggleSourceCode()"/>
                    </div>
                    <textarea name="editor-source-view" x-ref="sourceView" class="w-full min-h-[100px] max-h-[150px] overflow-y-auto rounded-b-lg border border-zinc-200 bg-white p-2 text-[0.8rem] shadow-sm outline-none dark:border-white/10 dark:bg-white/10 dark:text-zinc-300 dark:shadow-xs"
                              :id="'source-view' + id"
                              placeholder="{{ $placeholder }}"
                              @if($disabled) disabled="disabled"@endif
                              @blur="value = setEditorMode()"></textarea>
                </div>
            @endif
        </div>
    @endif
    @if($helper)
        <div class="inline-block pt-[0.2rem] text-[0.7rem] text-accent-content">
            @lang('lingua::lingua.translations.editor.helper_' . $type)
        </div>
    @endif
</div>
