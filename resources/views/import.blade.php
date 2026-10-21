<div class="flex flex-col gap-4">

    @include('lingua::transfer.partials._alerts')

    @unless($previewed)

        {{-- Import configuration card --}}
        <x-lingua::card
            :title="__('lingua::lingua.transfer.tabs.import')"
            :subtitle="__('lingua::lingua.transfer.subtitle')"
            icon="arrow-up-tray">

            {{-- Target locale --}}
            <x-lingua::card.row
                :title="__('lingua::lingua.transfer.import.locale.title')"
                :description="__('lingua::lingua.transfer.import.locale.subtitle')">
                <flux:select wire:model="targetLocale" :placeholder="__('lingua::lingua.transfer.import.locale.placeholder')" class="max-w-xs">
                    @foreach($this->languages as $language)
                        <option value="{{ $language->code }}">{{ $language->native }} ({{ $language->code }})</option>
                    @endforeach
                </flux:select>
            </x-lingua::card.row>

            {{-- File upload --}}
            <x-lingua::card.row
                :title="__('lingua::lingua.transfer.import.file.title')"
                :description="__('lingua::lingua.transfer.import.file.subtitle')">
                <div class="flex flex-col gap-1">
                    <flux:input type="file" wire:model="file" accept=".csv,.txt,.json,.xlsx,.ods" class="max-w-xs" />
                    @error('file')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </x-lingua::card.row>

            {{-- Vendor update --}}
            <x-lingua::card.row
                :title="__('lingua::lingua.transfer.import.vendor.title')"
                :description="__('lingua::lingua.transfer.import.vendor.hint')">
                <flux:switch
                    wire:model="vendorUpdateEnabled"
                    :label="__('lingua::lingua.transfer.import.vendor.toggle')"
                />
            </x-lingua::card.row>

            {{-- Footer action --}}
            <div class="flex justify-end border-t border-zinc-100 px-6 py-4 dark:border-zinc-800">
                <flux:button wire:click="preview" variant="primary" icon="eye">
                    {{ __('lingua::lingua.transfer.import.preview_button') }}
                </flux:button>
            </div>

        </x-lingua::card>

    @else

        {{-- Summary KPI tiles --}}
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
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

        {{-- Preview detail card --}}
        <x-lingua::card
            :title="__('lingua::lingua.transfer.import.preview.title')"
            :subtitle="__('lingua::lingua.transfer.import.preview.subtitle')"
            icon="clipboard-document-list">

            {{-- Changes table --}}
            @if(count($changes) > 0)
                <div class="px-6 py-4">
                    <h4 class="mb-3 text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                        {{ __('lingua::lingua.transfer.import.preview.changes_heading') }}
                    </h4>
                    <div class="max-h-56 overflow-y-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                        <table class="w-full text-sm">
                            <thead class="sticky top-0 bg-zinc-50 dark:bg-zinc-800">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Key</th>
                                    <th class="px-3 py-2 text-right text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                @foreach($changes as $change)
                                    <tr wire:key="change-{{ $loop->index }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                        <td class="px-3 py-1.5 font-mono text-zinc-700 dark:text-zinc-300">{{ $change['key'] }}</td>
                                        <td class="px-3 py-1.5 text-right">
                                            <flux:badge
                                                color="{{ str_contains($change['action'], 'create') ? 'green' : 'sky' }}"
                                                size="sm">
                                                {{ $change['action'] }}
                                            </flux:badge>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- Skipped rows table --}}
            @if(count($skipped) > 0)
                <div class="border-t border-zinc-100 px-6 py-4 dark:border-zinc-800">
                    <h4 class="mb-3 text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                        {{ __('lingua::lingua.transfer.import.preview.skipped_heading') }}
                    </h4>
                    <div class="max-h-40 overflow-y-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                        <table class="w-full text-sm">
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                @foreach($skipped as $skip)
                                    <tr wire:key="skip-{{ $loop->index }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                        <td class="px-3 py-1.5 font-mono text-zinc-500 dark:text-zinc-400">{{ $skip['key'] ?: '(empty)' }}</td>
                                        <td class="px-3 py-1.5 text-right text-xs text-zinc-400 dark:text-zinc-500">{{ $skip['reason'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- Footer actions --}}
            <div class="flex items-center justify-end gap-3 border-t border-zinc-100 px-6 py-4 dark:border-zinc-800">
                <flux:button wire:click="resetImport" variant="ghost" icon="x-mark">
                    {{ __('lingua::lingua.transfer.import.cancel_button') }}
                </flux:button>
                <flux:button wire:click="confirm" variant="primary" icon="check">
                    {{ __('lingua::lingua.transfer.import.confirm_button') }}
                </flux:button>
            </div>

        </x-lingua::card>

    @endunless

</div>
