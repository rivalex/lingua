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
                    const safeContent = _this.type === 'markdown' ? (content ?? '') : DOMPurify.sanitize(content ?? '')
                    editor.commands.setContent(safeContent, {contentType: _this.type})
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

    // Module-level: persists across factory re-calls caused by Livewire round-trips
    const _linguaSelectInitials = new Map();

    AlpineInstance.data('linguaSelect', (initial, opts) => {
        const _selectId = opts.selectId;
        let _initRafId = null;

        return {
            value: initial,
            open: false,
            query: '',
            activeIndex: -1,
            typeAheadBuffer: '',
            typeAheadTimer: null,
            options: [],
            multiple: opts.multiple ?? false,
            searchable: opts.searchable ?? false,
            clearable: opts.clearable ?? false,
            emptyText: opts.emptyText ?? '',
            selectedSuffix: opts.selectedSuffix ?? 'selected',
            clearLabel: opts.clearLabel ?? 'Clear',
            selectId: opts.selectId ?? 'ls',
            listboxId: opts.listboxId ?? 'ls-lb',
            disabled: opts.disabled ?? false,
            invalid: opts.invalid ?? false,
            placeholder: opts.placeholder ?? '',
            _observer: null,
            _scrollResize: null,
            _outsideClose: null,

            get filtered() {
                if (!this.query) return this.options;
                const q = this.query.toLowerCase();
                return this.options.filter(o => o.search.toLowerCase().includes(q));
            },

            get activeId() {
                if (this.activeIndex < 0 || !this.open) return null;
                return this.selectId + '-opt-' + this.activeIndex;
            },

            init() {
                if (this.multiple && this.value === null) {
                    this.value = [];
                }
                // Snapshot initial value once per selectId - survives Livewire factory re-calls
                this.$nextTick(() => {
                    if (!_linguaSelectInitials.has(_selectId)) {
                        _linguaSelectInitials.set(
                            _selectId,
                            this.multiple
                                ? (Array.isArray(this.value) ? [...this.value] : [])
                                : this.value,
                        );
                    }
                });
                this.initOptions();
                if (this.$refs.optionSlot) {
                    this._observer = new MutationObserver(() => this.initOptionsDebounced());
                    this._observer.observe(this.$refs.optionSlot, { childList: true, subtree: true });
                }
            },

            destroy() {
                this._observer?.disconnect();
                cancelAnimationFrame(_initRafId);
                _linguaSelectInitials.delete(_selectId);
                // Ensure popover is removed from top layer if component tears down mid-open
                const pop = this.$refs.popover;
                if (pop?.hidePopover && pop.matches(':popover-open')) pop.hidePopover();
                if (this._scrollResize) {
                    window.removeEventListener('scroll', this._scrollResize, { capture: true });
                    window.removeEventListener('resize', this._scrollResize);
                }
                if (this._outsideClose) {
                    document.removeEventListener('pointerdown', this._outsideClose, { capture: true });
                }
            },

            initOptionsDebounced() {
                cancelAnimationFrame(_initRafId);
                _initRafId = requestAnimationFrame(() => this.initOptions());
            },

            initOptions() {
                const items = this.$refs.optionSlot?.querySelectorAll('[data-lingua-option]');
                if (!items) return;
                this.options = Array.from(items).map(el => ({
                    value: this.parseValue(el.dataset.value),
                    search: el.dataset.search || el.textContent.trim(),
                    selectedLabel: el.dataset.selectedLabel || el.dataset.search || el.textContent.trim(),
                    disabled: el.dataset.disabled === 'true',
                    html: el.innerHTML,
                }));
            },

            parseValue(v) {
                try { return JSON.parse(v); } catch { return v; }
            },

            hasValue() {
                if (this.multiple) return Array.isArray(this.value) && this.value.length > 0;
                return this.value !== null && this.value !== '' && this.value !== undefined;
            },

            isSelected(opt) {
                if (this.multiple) {
                    return Array.isArray(this.value) && this.value.some(v => String(v) === String(opt.value));
                }
                return this.value !== null && this.value !== undefined && String(this.value) === String(opt.value);
            },

            triggerText() {
                if (this.multiple) {
                    const count = Array.isArray(this.value) ? this.value.length : 0;
                    if (count === 0) return this.placeholder;
                    return count + ' ' + this.selectedSuffix;
                }
                if (!this.hasValue()) return this.placeholder;
                const opt = this.options.find(o => String(o.value) === String(this.value));
                return opt ? opt.selectedLabel : String(this.value);
            },

            toggleSelect() {
                if (this.disabled) return;
                this.open ? this.closeSelect() : this.openSelect();
            },

            openSelect() {
                if (this.disabled) return;
                // Promote to top layer (Popover API) so position:fixed resolves to the
                // viewport regardless of transformed/overflow ancestors (e.g. Flux modals).
                // x-show sets inline display:none when closed; showPopover() only removes
                // the UA :not(:popover-open) rule - it cannot override an inline display:none.
                // Clear Alpine's inline style first so the element becomes visible.
                const pop = this.$refs.popover;
                if (pop) pop.style.removeProperty('display');
                if (pop?.showPopover && !pop.matches(':popover-open')) pop.showPopover();
                // Position before revealing - no layout-jump flash
                this.positionPopover();
                this.open = true;
                if (!this.multiple && this.hasValue()) {
                    const idx = this.filtered.findIndex(o => String(o.value) === String(this.value));
                    this.activeIndex = idx !== -1 ? idx : 0;
                } else {
                    this.activeIndex = 0;
                }
                if (this.searchable) {
                    this.$nextTick(() => this.$refs.searchInput?.focus());
                }
                // Re-position on scroll/resize (captures modal scroll container too).
                this._scrollResize = () => this.positionPopover();
                window.addEventListener('scroll', this._scrollResize, { capture: true, passive: true });
                window.addEventListener('resize', this._scrollResize, { passive: true });
                // Outside-close: replace click.outside (removed because teleport breaks DOM containment).
                this._outsideClose = (e) => {
                    const trigger = this.$refs.trigger;
                    const popover = this.$refs.popover;
                    if (!trigger?.contains(e.target) && !popover?.contains(e.target)) {
                        this.closeSelect(false);
                    }
                };
                document.addEventListener('pointerdown', this._outsideClose, { capture: true });
            },

            closeSelect(returnFocus = true) {
                this.open = false;
                this.query = '';
                const pop = this.$refs.popover;
                if (pop?.hidePopover && pop.matches(':popover-open')) pop.hidePopover();
                this.activeIndex = -1;
                if (this._scrollResize) {
                    window.removeEventListener('scroll', this._scrollResize, { capture: true });
                    window.removeEventListener('resize', this._scrollResize);
                    this._scrollResize = null;
                }
                if (this._outsideClose) {
                    document.removeEventListener('pointerdown', this._outsideClose, { capture: true });
                    this._outsideClose = null;
                }
                if (returnFocus) {
                    this.$nextTick(() => this.$refs.trigger?.focus());
                }
            },

            selectOption(opt) {
                if (opt.disabled) return;
                if (this.multiple) {
                    const str = String(opt.value);
                    const current = Array.isArray(this.value) ? this.value : [];
                    if (current.some(v => String(v) === str)) {
                        this.value = current.filter(v => String(v) !== str);
                    } else {
                        this.value = [...current, opt.value];
                    }
                } else {
                    this.value = opt.value;
                    this.query = '';
                    this.closeSelect();
                }
            },

            clearValue() {
                // Always resolve to an empty value - never null.
                // Single-select entangled to a typed PHP string would throw
                // TypeError when Livewire hydrates null into a string property.
                this.value = this.multiple ? [] : '';
                this.query = '';
            },

            move(delta) {
                const count = this.filtered.length;
                if (count === 0) return;
                let next = this.activeIndex + delta;
                let attempts = 0;
                while (attempts < count) {
                    if (next < 0) next = count - 1;
                    else if (next >= count) next = 0;
                    if (!this.filtered[next]?.disabled) break;
                    next += delta > 0 ? 1 : -1;
                    attempts++;
                }
                this.activeIndex = next;
                this.$nextTick(() => this.scrollToActive());
            },

            moveHome() {
                for (let i = 0; i < this.filtered.length; i++) {
                    if (!this.filtered[i].disabled) { this.activeIndex = i; break; }
                }
                this.$nextTick(() => this.scrollToActive());
            },

            moveEnd() {
                for (let i = this.filtered.length - 1; i >= 0; i--) {
                    if (!this.filtered[i].disabled) { this.activeIndex = i; break; }
                }
                this.$nextTick(() => this.scrollToActive());
            },

            scrollToActive() {
                if (!this.$refs.listbox) return;
                const items = this.$refs.listbox.querySelectorAll('[role="option"]');
                if (items[this.activeIndex]) {
                    items[this.activeIndex].scrollIntoView({ block: 'nearest' });
                }
            },

            typeAheadChar(char) {
                clearTimeout(this.typeAheadTimer);
                this.typeAheadBuffer += char.toLowerCase();
                const buf = this.typeAheadBuffer;
                const idx = this.filtered.findIndex(o => !o.disabled && o.search.toLowerCase().startsWith(buf));
                if (idx !== -1) this.activeIndex = idx;
                this.typeAheadTimer = setTimeout(() => { this.typeAheadBuffer = ''; }, 500);
            },

            handleTriggerKeydown(event) {
                const { key, ctrlKey, metaKey, altKey } = event;
                switch (key) {
                    case 'Enter':
                    case ' ':
                        event.preventDefault();
                        if (!this.open) { this.openSelect(); break; }
                        if (this.activeIndex >= 0 && this.filtered[this.activeIndex]) {
                            this.selectOption(this.filtered[this.activeIndex]);
                        }
                        break;
                    case 'ArrowDown':
                        event.preventDefault();
                        if (!this.open) { this.openSelect(); break; }
                        this.move(1);
                        break;
                    case 'ArrowUp':
                        event.preventDefault();
                        if (!this.open) { this.openSelect(); break; }
                        this.move(-1);
                        break;
                    case 'Home':
                        if (this.open) { event.preventDefault(); this.moveHome(); }
                        break;
                    case 'End':
                        if (this.open) { event.preventDefault(); this.moveEnd(); }
                        break;
                    case 'Escape':
                        if (this.open) { event.preventDefault(); this.closeSelect(); }
                        break;
                    case 'Tab':
                        if (this.open) this.closeSelect();
                        break;
                    default:
                        if (key.length === 1 && !ctrlKey && !metaKey && !altKey) {
                            if (!this.open) this.openSelect();
                            this.typeAheadChar(key);
                        }
                }
            },

            handleSearchKeydown(event) {
                switch (event.key) {
                    case 'ArrowDown':
                        event.preventDefault(); this.move(1); break;
                    case 'ArrowUp':
                        event.preventDefault(); this.move(-1); break;
                    case 'Home':
                        event.preventDefault(); this.moveHome(); break;
                    case 'End':
                        event.preventDefault(); this.moveEnd(); break;
                    case 'Enter':
                        event.preventDefault();
                        if (this.activeIndex >= 0 && this.filtered[this.activeIndex]) {
                            this.selectOption(this.filtered[this.activeIndex]);
                        }
                        break;
                    case 'Escape':
                        event.preventDefault();
                        if (this.query) { this.query = ''; } else { this.closeSelect(); }
                        break;
                    case 'Tab':
                        this.closeSelect(); break;
                }
            },

            positionPopover() {
                const popover = this.$refs.popover;
                const trigger = this.$refs.trigger;
                if (!popover || !trigger) return;
                const tr = trigger.getBoundingClientRect();
                // Neutralize UA [popover] default margin/inset so our coords are exact.
                popover.style.inset = 'auto';
                popover.style.margin = '0';
                popover.style.position = 'fixed';
                popover.style.width = tr.width + 'px';
                popover.style.minWidth = tr.width + 'px';
                popover.style.left = tr.left + 'px';
                const spaceBelow = window.innerHeight - tr.bottom;
                const spaceAbove = tr.top;
                if (spaceBelow < 250 && spaceAbove > spaceBelow) {
                    popover.style.top = 'auto';
                    popover.style.bottom = (window.innerHeight - tr.top + 4) + 'px';
                } else {
                    popover.style.top = (tr.bottom + 4) + 'px';
                    popover.style.bottom = 'auto';
                }
            },
        };
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

// Lingua runs on Livewire's Alpine instance and never starts its own: a second Alpine
// (or a second copy of its plugins) on the host page would conflict with Livewire's.
if (window.Alpine) {
    registerLinguaComponents(window.Alpine);
} else {
    document.addEventListener('alpine:init', () => registerLinguaComponents(window.Alpine), { once: true });
    document.addEventListener('DOMContentLoaded', () => {
        if (!window.Alpine) {
            console.error('[Lingua] Alpine.js was not found. Load Livewire\'s scripts on this page.');
        }
    }, { once: true });
}
