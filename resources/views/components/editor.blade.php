@props([
    'required' => false,
    'type' => 'text',
    'placeholder' => null,
    'disabled' => false,
    'label' => null,
    'helper' => true
])
<div @class([
    'translate-editor-wrapper',
    'invalid' => $errors->has($attributes->wire('model')->value())
])>
    @if($errors->has($attributes->wire('model')->value()))
        <p class="translate-editor-error">
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
                    :badge="$required ? __('rivalex::lingua.global.required') : null">{{ $label }}</flux:label>
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
                'translate-editor',
                'error' => $errors->has($attributes->wire('model')->value())
            ])
        >
            @if(!empty($label))
                <flux:label x-bind:for="id"
                    :badge="$required ? __('rivalex::lingua.global.required') : null">{{ $label }}</flux:label>
            @endif
            <ui-translate-editor @if(config('lingua.editor.code-mode')) x-cloak x-show="!sourceView" @endif
                x-on:pointerdown.stop
                x-on:click.stop
                {{ $attributes->whereDoesntStartWith('wire:model')->class('') }}>
                <!-- Toolbar -->
                <x-lingua::editor.toolbar :type="$type"/>
                <!-- Editor -->
                <div x-ref="editor" :id="id"></div>
            </ui-translate-editor>
            @if(config('lingua.editor.code-mode'))
                <div x-cloak x-show="sourceView">
                    <div class="toolbar" x-ref="toolbar">
                        <p class="source-code-label">{{ __('rivalex::lingua.translations.editor.code-mode') }}</p>
                        <flux:spacer vertical/>
                        <x-lingua::editor.code-mode @click="toggleSourceCode()"/>
                    </div>
                    <textarea name="editor-source-view" x-ref="sourceView" class="editor-source"
                              :id="'source-view' + id"
                              placeholder="{{ $placeholder }}"
                              @if($disabled) disabled="disabled"@endif
                              @blur="value = setEditorMode()"></textarea>
                </div>
            @endif
        </div>
    @endif
    @if($helper)
        <div class="translate-editor-helper">
            @lang('rivalex::lingua.translations.editor.helper_' . $type)
        </div>
    @endif
</div>

{{--@assets--}}
{{--@once--}}
{{--    <script type="module" src="{{ lt_asset('js/translate-editor.min.js') }}"></script>--}}
{{--@endonce--}}
{{--@endassets--}}
