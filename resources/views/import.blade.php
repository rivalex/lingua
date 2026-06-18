<div class="flex flex-col gap-6">

    {{-- Success message --}}
    @if($successMessage)
        <flux:callout variant="success" icon="check-circle">
            <flux:callout.heading>{{ __('lingua::lingua.transfer.import.success') }}</flux:callout.heading>
            <flux:callout.text>{{ $successMessage }}</flux:callout.text>
        </flux:callout>
    @endif

    {{-- Error message --}}
    @if($errorMessage)
        <flux:callout variant="danger" icon="exclamation-triangle">
            <flux:callout.heading>{{ __('lingua::lingua.transfer.import.error') }}</flux:callout.heading>
            <flux:callout.text>{{ $errorMessage }}</flux:callout.text>
        </flux:callout>
    @endif

    @unless($previewed)
        {{-- Target locale --}}
        <x-lingua::card
            :title="__('lingua::lingua.transfer.import.locale.title')"
            :subtitle="__('lingua::lingua.transfer.import.locale.subtitle')"
            icon="language">
            <flux:select wire:model="targetLocale" :placeholder="__('lingua::lingua.transfer.import.locale.placeholder')">
                @foreach($this->languages as $language)
                    <flux:option value="{{ $language->code }}">{{ $language->native }} ({{ $language->code }})</flux:option>
                @endforeach
            </flux:select>
        </x-lingua::card>

        {{-- File upload --}}
        <x-lingua::card
            :title="__('lingua::lingua.transfer.import.file.title')"
            :subtitle="__('lingua::lingua.transfer.import.file.subtitle')"
            icon="document-arrow-up">
            <flux:input type="file" wire:model="file" accept=".csv,.txt,.json,.xlsx,.ods" />
            @error('file')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </x-lingua::card>

        {{-- Vendor update toggle --}}
        <x-lingua::card
            :title="__('lingua::lingua.transfer.import.vendor.title')"
            :subtitle="__('lingua::lingua.transfer.import.vendor.subtitle')"
            icon="puzzle-piece">
            <flux:switch
                wire:model="vendorUpdateEnabled"
                :label="__('lingua::lingua.transfer.import.vendor.toggle')"
            />
            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('lingua::lingua.transfer.import.vendor.hint') }}
            </p>
        </x-lingua::card>

        {{-- Preview button --}}
        <div class="flex justify-end">
            <flux:button wire:click="preview" variant="primary" icon="eye">
                {{ __('lingua::lingua.transfer.import.preview_button') }}
            </flux:button>
        </div>

    @else
        {{-- Preview results --}}
        <x-lingua::card
            :title="__('lingua::lingua.transfer.import.preview.title')"
            :subtitle="__('lingua::lingua.transfer.import.preview.subtitle')"
            icon="clipboard-document-list">

            {{-- Summary counts --}}
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 mb-6">
                <x-lingua::stat
                    :value="$createCount"
                    :label="__('lingua::lingua.transfer.import.preview.creates')"
                    icon="plus-circle"
                />
                <x-lingua::stat
                    :value="$updateCount"
                    :label="__('lingua::lingua.transfer.import.preview.updates')"
                    icon="pencil-square"
                />
                <x-lingua::stat
                    :value="$skipCount"
                    :label="__('lingua::lingua.transfer.import.preview.skips')"
                    icon="minus-circle"
                />
                <x-lingua::stat
                    :value="$errorCount"
                    :label="__('lingua::lingua.transfer.import.preview.errors')"
                    icon="exclamation-circle"
                />
            </div>

            {{-- Changes list (capped at 200) --}}
            @if(count($changes) > 0)
                <div class="mb-4">
                    <h4 class="mb-2 text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                        {{ __('lingua::lingua.transfer.import.preview.changes_heading') }}
                    </h4>
                    <div class="max-h-48 overflow-y-auto rounded border border-zinc-200 dark:border-zinc-700">
                        <table class="w-full text-sm">
                            @foreach($changes as $change)
                                <tr wire:key="change-{{ $loop->index }}" class="border-b border-zinc-100 dark:border-zinc-800 last:border-0">
                                    <td class="px-3 py-1 font-mono text-zinc-700 dark:text-zinc-300">{{ $change['key'] }}</td>
                                    <td class="px-3 py-1 text-right">
                                        <flux:badge
                                            color="{{ str_contains($change['action'], 'create') ? 'green' : 'sky' }}"
                                            size="sm">
                                            {{ $change['action'] }}
                                        </flux:badge>
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                </div>
            @endif

            {{-- Skipped list --}}
            @if(count($skipped) > 0)
                <div class="mb-4">
                    <h4 class="mb-2 text-sm font-semibold text-zinc-500 dark:text-zinc-400">
                        {{ __('lingua::lingua.transfer.import.preview.skipped_heading') }}
                    </h4>
                    <div class="max-h-32 overflow-y-auto rounded border border-zinc-200 dark:border-zinc-700">
                        <table class="w-full text-sm">
                            @foreach($skipped as $skip)
                                <tr wire:key="skip-{{ $loop->index }}" class="border-b border-zinc-100 dark:border-zinc-800 last:border-0">
                                    <td class="px-3 py-1 font-mono text-zinc-500 dark:text-zinc-400">{{ $skip['key'] ?: '(empty)' }}</td>
                                    <td class="px-3 py-1 text-right text-xs text-zinc-400">{{ $skip['reason'] }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                </div>
            @endif
        </x-lingua::card>

        {{-- Confirm / Cancel buttons --}}
        <div class="flex items-center justify-end gap-3">
            <flux:button wire:click="resetImport" variant="ghost" icon="x-mark">
                {{ __('lingua::lingua.transfer.import.cancel_button') }}
            </flux:button>
            <flux:button wire:click="confirm" variant="primary" icon="check">
                {{ __('lingua::lingua.transfer.import.confirm_button') }}
            </flux:button>
        </div>
    @endif

</div>
