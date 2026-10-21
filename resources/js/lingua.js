import Alpine from "alpinejs";
import ui from "@alpinejs/ui";
import focus from "@alpinejs/focus";
import {Editor} from "@tiptap/core";
import StarterKit from "@tiptap/starter-kit";
import { Placeholder } from '@tiptap/extensions'
import {Markdown} from '@tiptap/markdown'
import Typography from '@tiptap/extension-typography'
import Subscript from "@tiptap/extension-subscript";
import Superscript from "@tiptap/extension-superscript";
import DOMPurify from "dompurify";

const registerLinguaComponents = (AlpineInstance) => {
    if (AlpineInstance.__rivalexLinguaRegistered) {
        return;
    }
    AlpineInstance.__rivalexLinguaRegistered = true;
    AlpineInstance.plugin(ui, focus);
    AlpineInstance.data('tiptap', (content, type, placeholder, disabled) => {
        let editor

        return {
            id: '',
            value: content ?? '',
            type: type,
            content: '',
            placeholder: placeholder ?? '',
            disabled: disabled ?? false,
            updatedAt: Date.now(),
            canRedo: false,
            canUndo: false,
            sourceView: false,
            setContent() {
                if (this.type === 'markdown') {
                    editor.commands.setContent(this.value ?? '', {contentType: 'markdown'})
                } else {
                    editor.commands.setContent(DOMPurify.sanitize(this.value ?? ''), {contentType: 'html'})
                }
            },

            getContent(editor) {
                const _html = editor.getHTML()
                if (this.type === 'markdown') {
                    this.value = editor.getMarkdown()
                } else {
                    this.value = DOMPurify.sanitize(_html).trim()
                }
            },

            setUndoRedoState(editor) {
                this.canRedo = editor.can().redo()
                this.canUndo = editor.can().undo()
            },

            init() {
                const _this = this
                this.id = _this.$id('lingua-editor')
                editor = new Editor({
                    element: _this.$refs.editor,
                    content: '',
                    editable: !_this.disabled ?? true,
                    contentType: _this.type,
                    extensions: [
                        StarterKit.configure({
                            history: {
                                delay: 1000,
                                maxHistorySize: 50,
                                userOnly: true
                            },
                            trailingNode: {
                                node: 'paragraph',
                                notAfter: ['paragraph', 'bulletList', 'orderedList', 'codeBlock', 'code'],
                            },
                            blockquote: {
                                nested: true,
                            },
                            heading: {
                                levels: [1, 2, 3, 4, 5, 6],
                            }
                        }),
                        Markdown.configure({
                            indentation: {style: 'tab', size: 1},
                            markedOptions: {gfm: true, breaks: true}
                        }),
                        Placeholder.configure({
                            emptyEditorClass: 'is-editor-empty',
                            showOnlyWhenEditable: true,
                            showOnlyCurrent: true,
                            placeholder: _this.placeholder ?? ''
                        }),
                        Typography,
                        Subscript,
                        Superscript
                    ],
                    onCreate() {
                        _this.updatedAt = Date.now()
                    },
                    onUpdate() {
                        _this.updatedAt = Date.now()
                    },
                    onSelectionUpdate() {
                        _this.updatedAt = Date.now()
                    },
                    editorProps: {
                        attributes: {
                            class: 'focus:outline-none',
                        },
                    },
                })
                this.setContent()
                editor.on('update', ({editor}) => {
                    this.setUndoRedoState(editor)
                })
                editor.on('blur', ({editor, event}) => {
                    this.getContent(editor)
                })

                this.$watch('value', (content) => {
                    const currentContent = this.type === 'markdown' ? editor.getMarkdown() : editor.getHTML()
                    if (content === currentContent) return
                    editor.commands.setContent(content ?? '', {contentType: _this.type})
                })
            },
            isLoaded() {
                return editor
            },
            isActive(type, opts = {}) {
                return editor.isActive(type, opts)
            },
            toggleHeading({level}) {
                editor.chain().focus().toggleHeading({level}).run()
            },
            toggleBold() {
                editor.chain().focus().toggleBold().run()
            },
            toggleItalic() {
                editor.chain().focus().toggleItalic().run()
            },
            toggleUnderline() {
                editor.chain().focus().toggleUnderline().run()
            },
            toggleStrike() {
                editor.chain().focus().toggleStrike().run()
            },
            toggleSubscript() {
                editor.chain().focus().toggleSubscript().run()
            },
            toggleSuperscript() {
                editor.chain().focus().toggleSuperscript().run()
            },
            toggleBlockquote() {
                editor.chain().focus().toggleBlockquote().run()
            },
            toggleCode() {
                editor.chain().focus().toggleCode().run()
            },
            toggleCodeBlock() {
                editor.chain().focus().toggleCodeBlock().run()
            },
            toggleBulletList() {
                editor.chain().focus().toggleBulletList().run()
            },
            toggleOrderedList() {
                editor.chain().focus().toggleOrderedList().run()
            },
            setLink(href, text) {
                editor.chain().focus().setLink({href, text}).run()
            },
            removeStyles() {
                editor.chain().focus().unsetAllMarks().run()
                editor.chain().focus().clearNodes().run()
            },
            undo() {
                editor.chain().focus().undo().run()
            },
            redo() {
                editor.chain().focus().redo().run()
            },
            toggleSourceCode() {
                this.sourceView = !this.sourceView
                if (this.sourceView) {
                    this.$refs.sourceView.value = this.setSourceMode()
                } else {
                    editor.commands.clearContent()
                    editor.commands.setContent(this.setEditorMode(), {contentType: this.type})
                    this.getContent(editor)
                }
            },
            setSourceMode() {
                const _html = editor.getHTML();
                if (this.type === 'markdown') {
                    return editor.getMarkdown()
                } else {
                    return DOMPurify.sanitize(_html)
                }
            },
            setEditorMode() {
                let _source = this.$refs.sourceView.value;
                if (this.type === 'markdown') {
                    return _source
                } else {
                    return DOMPurify.sanitize(_source)
                }
            }
        }
    });

    AlpineInstance.data('autocomplete', (model, options, disabled) => {
       return {
           id: '',
           value: model,
           query: '',
           options: options ?? [],
           disabled: disabled ?? false,
           get filteredOptions() {
               return this.query === ''
                   ? this.options
                   : this.options.filter((option) => {
                       return option.name.toLowerCase().includes(this.query.toLowerCase())
                   })
           },
           init() {
               this.id = this.$id('autocomplete')
           }
       }
    });

    AlpineInstance.data('message', (targetEvent, delay) => {
        return {
            targetEvent: targetEvent,
            delay: delay ?? 2000,
            shown: false,
            timeout: null,
            showMessage() {
                clearTimeout(this.timeout);
                this.shown = true;
                this.timeout = setTimeout(() => { this.shown = false }, this.delay);
            },
            init() {
                if (this.targetEvent) {
                    this.$wire.on(this.targetEvent, () => { this.showMessage() })
                }
            }
        }
    });
};

const AlpineInstance = window.Alpine ?? Alpine;

if (!window.Alpine) {
    window.Alpine = AlpineInstance;
    registerLinguaComponents(AlpineInstance);
    AlpineInstance.start();
} else {
    registerLinguaComponents(AlpineInstance);
}
