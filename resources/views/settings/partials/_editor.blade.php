{{-- Text Formatting --}}
<x-lingua::card.row :title="__('lingua::lingua.settings.editor.formatting')">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <flux:switch wire:model.live="editor.bold"
                     :label="__('lingua::lingua.settings.editor.bold')"
                     :description="__('lingua::lingua.settings.editor.bold_description')"/>
        <flux:switch wire:model.live="editor.italic"
                     :label="__('lingua::lingua.settings.editor.italic')"
                     :description="__('lingua::lingua.settings.editor.italic_description')"/>
        <flux:switch wire:model.live="editor.underline"
                     :label="__('lingua::lingua.settings.editor.underline')"
                     :description="__('lingua::lingua.settings.editor.underline_description')"/>
        <flux:switch wire:model.live="editor.strikethrough"
                     :label="__('lingua::lingua.settings.editor.strikethrough')"
                     :description="__('lingua::lingua.settings.editor.strikethrough_description')"/>
        <flux:switch wire:model.live="editor.subscript"
                     :label="__('lingua::lingua.settings.editor.subscript')"
                     :description="__('lingua::lingua.settings.editor.subscript_description')"/>
        <flux:switch wire:model.live="editor.superscript"
                     :label="__('lingua::lingua.settings.editor.superscript')"
                     :description="__('lingua::lingua.settings.editor.superscript_description')"/>
    </div>
</x-lingua::card.row>

{{-- Structure --}}
<x-lingua::card.row :title="__('lingua::lingua.settings.editor.structure')">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <flux:switch wire:model.live="editor.headings"
                     :label="__('lingua::lingua.settings.editor.headings')"
                     :description="__('lingua::lingua.settings.editor.headings_description')"/>
        <flux:switch wire:model.live="editor.blockquote"
                     :label="__('lingua::lingua.settings.editor.blockquote')"
                     :description="__('lingua::lingua.settings.editor.blockquote_description')"/>
        <flux:switch wire:model.live="editor.bullet"
                     :label="__('lingua::lingua.settings.editor.bullet')"
                     :description="__('lingua::lingua.settings.editor.bullet_description')"/>
        <flux:switch wire:model.live="editor.ordered"
                     :label="__('lingua::lingua.settings.editor.ordered')"
                     :description="__('lingua::lingua.settings.editor.ordered_description')"/>
    </div>
</x-lingua::card.row>

{{-- Advanced --}}
<x-lingua::card.row :title="__('lingua::lingua.settings.editor.advanced')">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <flux:switch wire:model.live="editor.code-line"
                     :label="__('lingua::lingua.settings.editor.code-line')"
                     :description="__('lingua::lingua.settings.editor.code-line_description')"/>
        <flux:switch wire:model.live="editor.code-block"
                     :label="__('lingua::lingua.settings.editor.code-block')"
                     :description="__('lingua::lingua.settings.editor.code-block_description')"/>
        <flux:switch wire:model.live="editor.code-mode"
                     :label="__('lingua::lingua.settings.editor.code-mode')"
                     :description="__('lingua::lingua.settings.editor.code-mode_description')"/>
        <flux:switch wire:model.live="editor.clear"
                     :label="__('lingua::lingua.settings.editor.clear')"
                     :description="__('lingua::lingua.settings.editor.clear_description')"/>
    </div>
</x-lingua::card.row>
