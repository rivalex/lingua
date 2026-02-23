@props([
    'textToCopy',
    'showTooltip' => true
    ])
<div x-data="{
		textToCopy: @js($textToCopy),
		copied: false,
		keyCopied() {
			this.copied = true;
			setTimeout(() => this.copied = false, 1000);
		},
		decodeHtml(html) {
            var txt = document.createElement('textarea');
            txt.innerHTML = html;
            return txt.value;
        }
	}" {{ $attributes->class(['flex flex-row items-center']) }}
>
    <button tabindex="-1" class="w-full cursor-pointer" type="button" id="{{ rand() }}"
            x-on:click.stop="await navigator.clipboard.writeText(decodeHtml(textToCopy)); keyCopied();">
        <div class="flex flex-row items-start gap-2 select-none text-start">
            @if($showTooltip)
                <flux:tooltip :content="__('rivalex::lingua.global.click_to_copy')">
                    <div class="flex">
                        <div x-cloak x-show="!copied">
                            <flux:icon.clipboard-document class="text-sky-600 dark:text-sky-400 size-4"/>
                        </div>
                        <div x-cloak x-show="copied">
                            <flux:icon.check class="text-green-700 dark:text-green-500 size-4"/>
                        </div>
                    </div>
                </flux:tooltip>
            @else
                <div class="flex">
                    <div x-cloak x-show="!copied">
                        <flux:icon.clipboard-document class="text-sky-600 dark:text-sky-400 size-4"/>
                    </div>
                    <div x-cloak x-show="copied">
                        <flux:icon.check class="text-green-700 dark:text-green-500 size-4"/>
                    </div>
                </div>
            @endif
            <div>
                {{ $slot }}
            </div>
        </div>
    </button>
</div>

