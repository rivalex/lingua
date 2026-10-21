{{--
    Headless Language Selector
    ──────────────────────────────────────────────────────────────────────────────
    Zero CSS. Zero framework markup. Pure semantic HTML.

    This component intentionally applies NO classes or inline styles.
    All visual styling is left entirely to the consumer via data-lingua-* attributes.

    CSS TARGETING API (data attributes)
    ─────────────────────────────────────
    [data-lingua-selector]       Root <nav> element
    [data-lingua-list]           The <ul> language list
    [data-lingua-item]           Each <li> language entry
    [data-lingua-active]         The <li> of the currently active language
    [data-lingua-button]         The <button> inside each <li>
    [data-lingua-name]           Language English display name <span>
    [data-lingua-native]         Language native name <span>
    [data-lingua-code]           Language ISO code <span>

    SLOTS
    ──────────────────────────────────────────────────────────────────────────────
    $item($language)
        Replaces the default button markup inside each <li>.
        Receives the Language model instance as a slot argument.
        Default: <button> with name, native, and code spans.

    $current($language)
        Replaces the default rendering for the currently selected language item.
        Receives the Language model instance as a slot argument.
        Default: falls through to the $item slot rendering.

    DESIGN NOTE — no $trigger slot
    ──────────────────────────────────────────────────────────────────────────────
    The list is always present in the DOM. Show/hide is the consumer's
    responsibility (CSS display, Alpine x-show, etc.). A trigger button
    is not provided because headless consumers choose their own UX pattern.
--}}
<nav role="navigation" aria-label="{{ __('lingua::lingua.selector.menu_title') }}" data-lingua-selector>
    <ul data-lingua-list>
        @foreach($this->languages as $language)
            @php $isActive = $language->code === $currentLocale; @endphp
            <li wire:key="headless_{{ $language->code }}"
                data-lingua-item
                @if($isActive) data-lingua-active aria-current="true" @endif>
                @if($isActive && isset($current))
                    {{ $current($language) }}
                @elseif(isset($item))
                    {{ $item($language) }}
                @else
                    <button type="button"
                            wire:click="changeLocale('{{ $language->code }}')"
                            data-lingua-button>
                        <span data-lingua-name>{{ $language->name }}</span>
                        <span data-lingua-native>{{ $language->native }}</span>
                        <span data-lingua-code>{{ $language->code }}</span>
                    </button>
                @endif
            </li>
        @endforeach
    </ul>
</nav>
