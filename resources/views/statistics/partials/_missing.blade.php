<div
    class="mt-3 overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700"
    wire:key="missing-{{ $language->code }}"
>
    <div class="flex items-center gap-2 border-b border-zinc-200 bg-zinc-50 px-4 py-2.5 dark:border-zinc-700 dark:bg-zinc-800">
        <span class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
            {{ __('lingua::lingua.statistics.missing.title', ['language' => $language->native]) }}
        </span>
        <flux:badge size="sm" color="red">{{ $this->missingKeys->count() }}</flux:badge>
    </div>

    @if ($this->missingKeys->isEmpty())
        <p class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">{{ __('lingua::lingua.statistics.missing.empty') }}</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800/50">
                    <tr>
                        <th scope="col" class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-zinc-400 dark:text-zinc-500">{{ __('lingua::lingua.statistics.missing.group') }}</th>
                        <th scope="col" class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-zinc-400 dark:text-zinc-500">{{ __('lingua::lingua.statistics.missing.key') }}</th>
                        <th scope="col" class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wide text-zinc-400 dark:text-zinc-500">{{ __('lingua::lingua.statistics.missing.action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($this->missingKeys as $missing)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50" wire:key="missing-row-{{ $language->code }}-{{ $loop->index }}">
                            <td class="px-4 py-2">
                                <code class="rounded bg-zinc-100 px-1.5 py-0.5 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">{{ $missing['group'] }}</code>
                            </td>
                            <td class="px-4 py-2">
                                <code class="rounded bg-zinc-100 px-1.5 py-0.5 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">{{ $missing['key'] }}</code>
                            </td>
                            <td class="px-4 py-2 text-right">
                                {{--
                                    The lingua.translations route uses `q` as the URL alias for the search
                                    parameter (#[Url(as: 'q')] in the Translations component).
                                    Passing group_key narrows the search to this specific translation.
                                --}}
                                @if(config('lingua.links.translations.enabled', true))
                                    @if(config('lingua.navigate', false))
                                    <a
                                        href="{{ route(config('lingua.links.translations.route', 'lingua.translations'), ['locale' => $language->code, 'q' => $missing['group_key']]) }}"
                                        wire:navigate
                                        class="text-xs font-medium text-violet-600 hover:text-violet-700 hover:underline dark:text-violet-400 dark:hover:text-violet-300 whitespace-nowrap"
                                    >
                                        {{ __('lingua::lingua.statistics.missing.translate') }}
                                    </a>
                                    @else
                                    <a
                                        href="{{ route(config('lingua.links.translations.route', 'lingua.translations'), ['locale' => $language->code, 'q' => $missing['group_key']]) }}"
                                        class="text-xs font-medium text-violet-600 hover:text-violet-700 hover:underline dark:text-violet-400 dark:hover:text-violet-300 whitespace-nowrap"
                                    >
                                        {{ __('lingua::lingua.statistics.missing.translate') }}
                                    </a>
                                    @endif
                                @else
                                <span class="text-xs text-zinc-400 dark:text-zinc-500 whitespace-nowrap">
                                    {{ __('lingua::lingua.statistics.missing.translate') }}
                                </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
