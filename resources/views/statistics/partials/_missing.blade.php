<div
    class="mt-3 rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden"
    wire:key="missing-{{ $language->code }}"
>
    <div class="flex items-center gap-2 px-4 py-2 bg-zinc-50 dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700">
        <flux:heading size="sm" level="3">
            {{ __('lingua::lingua.statistics.missing.title', ['language' => $language->native]) }}
        </flux:heading>
        <flux:badge size="sm" color="red">{{ $this->missingKeys->count() }}</flux:badge>
    </div>

    @if ($this->missingKeys->isEmpty())
        <p class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">{{ __('lingua::lingua.statistics.missing.empty') }}</p>
    @else
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700">
                <tr>
                    <th scope="col" class="px-4 py-2 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('lingua::lingua.statistics.missing.group') }}</th>
                    <th scope="col" class="px-4 py-2 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('lingua::lingua.statistics.missing.key') }}</th>
                    <th scope="col" class="px-4 py-2 text-right font-medium text-zinc-600 dark:text-zinc-300">{{ __('lingua::lingua.statistics.missing.action') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @foreach ($this->missingKeys as $missing)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50" wire:key="missing-row-{{ $language->code }}-{{ $loop->index }}">
                        <td class="px-4 py-2">
                            <code class="text-xs bg-zinc-100 dark:bg-zinc-700 px-1.5 py-0.5 rounded">{{ $missing['group'] }}</code>
                        </td>
                        <td class="px-4 py-2">
                            <code class="text-xs bg-zinc-100 dark:bg-zinc-700 px-1.5 py-0.5 rounded">{{ $missing['key'] }}</code>
                        </td>
                        <td class="px-4 py-2 text-right">
                            {{--
                                The lingua.translations route uses `q` as the URL alias for the search
                                parameter (#[Url(as: 'q')] in the Translations component).
                                Passing group_key narrows the search to this specific translation.
                            --}}
                            <a
                                href="{{ route('lingua.translations', ['locale' => $language->code, 'q' => $missing['group_key']]) }}"
                                wire:navigate
                                class="text-xs text-violet-600 dark:text-violet-400 hover:underline whitespace-nowrap"
                            >
                                {{ __('lingua::lingua.statistics.missing.translate') }}
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
