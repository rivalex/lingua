<section class="flex flex-col gap-3 mt-4">

    <flux:heading size="lg" level="2">{{ __('lingua::lingua.statistics.breakdown.title') }}</flux:heading>

    @if ($this->groupBreakdown->isEmpty())
        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('lingua::lingua.statistics.breakdown.empty') }}</p>
    @else
        <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('lingua::lingua.statistics.breakdown.group') }}</th>
                        <th scope="col" class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('lingua::lingua.statistics.breakdown.total') }}</th>
                        @foreach ($this->languages as $lang)
                            <th scope="col" class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300" title="{{ $lang->native }}">
                                {{ $lang->code }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach ($this->groupBreakdown as $group => $data)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <td class="px-4 py-2">
                                <code class="text-xs bg-zinc-100 dark:bg-zinc-700 px-1.5 py-0.5 rounded">{{ $group }}</code>
                            </td>
                            <td class="px-4 py-2 tabular-nums text-zinc-600 dark:text-zinc-400">{{ $data['total'] }}</td>
                            @foreach ($this->languages as $lang)
                                @php $count = $data['locales'][$lang->code] ?? 0; @endphp
                                <td class="px-4 py-2 tabular-nums {{ $count === $data['total'] ? 'text-green-600 dark:text-green-400' : 'text-red-500 dark:text-red-400' }}">
                                    {{ $count }}/{{ $data['total'] }}
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

</section>
