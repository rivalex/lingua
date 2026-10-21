<section class="flex flex-col gap-3">

    <flux:heading size="lg" level="2">{{ __('lingua::lingua.statistics.coverage.title') }}</flux:heading>

    @forelse ($this->coverageStats as $stat)
        @php
            /** @var \Rivalex\Lingua\Models\Language $lang */
            $lang  = $stat['language'];
            $pct   = $stat['percentage'];
            $color = match (true) {
                $pct >= 80 => 'bg-green-500',
                $pct >= 50 => 'bg-amber-500',
                default    => 'bg-red-500',
            };
        @endphp

        <div class="flex flex-col gap-1 rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">

            {{-- Language label row --}}
            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <livewire:lingua::selector.icon :locale="$lang->code" size="sm" square/>
                    <span class="font-medium text-sm">
                        {{ $lang->native }} ({{ $lang->code }})
                    </span>
                    @if ($lang->is_default)
                        <flux:badge color="violet" size="sm">{{ __('lingua::lingua.statistics.coverage.default_badge') }}</flux:badge>
                    @endif
                </div>

                <div class="flex items-center gap-3 text-sm text-zinc-500 dark:text-zinc-400">
                    <span>{{ $stat['translated'] }}/{{ $this->totalKeys }}</span>
                    <span class="font-semibold tabular-nums">{{ $pct }}%</span>

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
                class="w-full rounded-full h-2 bg-zinc-200 dark:bg-zinc-700"
                role="progressbar"
                aria-valuenow="{{ (int) $pct }}"
                aria-valuemin="0"
                aria-valuemax="100"
                aria-label="{{ $lang->native }} translation coverage: {{ $pct }}%"
            >
                <div
                    class="h-2 rounded-full transition-all duration-300 {{ $color }}"
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

</section>
