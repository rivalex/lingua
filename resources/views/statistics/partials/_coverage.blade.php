<x-lingua::card :title="__('lingua::lingua.statistics.coverage.title')">

    <x-slot:actions>
        <flux:field variant="inline">
            <flux:label>
                <span class="whitespace-nowrap text-sm font-normal text-zinc-500 dark:text-zinc-400">{{ __('lingua::lingua.statistics.include_vendor') }}</span>
            </flux:label>
            <flux:switch
                :checked="$this->includeVendor"
                wire:change="toggleVendor"
            />
        </flux:field>
    </x-slot:actions>

    <div class="flex flex-col gap-4 p-6">

        {{-- Coverage legend --}}
        <div class="flex items-center gap-5 text-xs text-zinc-500 dark:text-zinc-400">
            <span class="flex items-center gap-1.5">
                <span class="inline-block h-2 w-2 rounded-full bg-green-500"></span>
                &ge;80%
            </span>
            <span class="flex items-center gap-1.5">
                <span class="inline-block h-2 w-2 rounded-full bg-amber-500"></span>
                &ge;50%
            </span>
            <span class="flex items-center gap-1.5">
                <span class="inline-block h-2 w-2 rounded-full bg-red-500"></span>
                &lt;50%
            </span>
        </div>

        {{-- Per-locale rows --}}
        <div class="flex flex-col gap-3">
            @forelse ($this->coverageStats as $stat)
                @php
                    /** @var \Rivalex\Lingua\Models\Language $lang */
                    $lang     = $stat['language'];
                    $pct      = $stat['percentage'];
                    $barColor = match (true) {
                        $pct >= 80 => 'bg-green-500',
                        $pct >= 50 => 'bg-amber-500',
                        default    => 'bg-red-500',
                    };
                    $pctText  = match (true) {
                        $pct >= 80 => 'text-green-700 dark:text-green-400',
                        $pct >= 50 => 'text-amber-700 dark:text-amber-400',
                        default    => 'text-red-700 dark:text-red-400',
                    };
                @endphp

                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">

                    {{-- Language label row --}}
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <livewire:lingua::selector.icon :locale="$lang->code" size="sm" square/>
                            <span class="text-sm font-medium text-zinc-900 dark:text-white">
                                {{ $lang->native }}
                            </span>
                            <span class="text-xs text-zinc-400 dark:text-zinc-500">({{ $lang->code }})</span>
                            @if ($lang->is_default)
                                <flux:badge color="violet" size="sm">{{ __('lingua::lingua.statistics.coverage.default_badge') }}</flux:badge>
                            @endif
                        </div>

                        <div class="flex items-center gap-3">
                            <span class="text-xs tabular-nums text-zinc-400 dark:text-zinc-500">
                                {{ $stat['translated'] }}/{{ $this->totalKeys }}
                            </span>
                            <span class="min-w-[2.5rem] text-right text-sm font-bold tabular-nums {{ $pctText }}">
                                {{ $pct }}%
                            </span>
                            @if ($stat['missing'] > 0)
                                <flux:button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    wire:click="toggleMissingKeys('{{ $lang->code }}')"
                                    :aria-expanded="$this->expandedLocale === $lang->code ? 'true' : 'false'"
                                >
                                    {{ __('lingua::lingua.statistics.coverage.missing_button', ['count' => $stat['missing']]) }}
                                    <flux:icon
                                        :name="$this->expandedLocale === $lang->code ? 'chevron-up' : 'chevron-down'"
                                        size="xs"
                                    />
                                </flux:button>
                            @endif
                        </div>
                    </div>

                    {{-- Progress bar --}}
                    <div
                        class="mt-3 h-1.5 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-700"
                        role="progressbar"
                        aria-valuenow="{{ (int) $pct }}"
                        aria-valuemin="0"
                        aria-valuemax="100"
                        aria-label="{{ $lang->native }} translation coverage: {{ $pct }}%"
                    >
                        <div
                            class="h-full rounded-full transition-all duration-300 {{ $barColor }}"
                            style="width: {{ $pct }}%"
                        ></div>
                    </div>

                    {{-- Missing keys panel --}}
                    @if ($this->expandedLocale === $lang->code)
                        @include('lingua::statistics.partials._missing', ['language' => $lang])
                    @endif

                </div>
            @empty
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('lingua::lingua.statistics.coverage.empty') }}</p>
            @endforelse
        </div>

    </div>

</x-lingua::card>
