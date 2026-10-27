<x-lingua::card :title="__('lingua::lingua.statistics.breakdown.title')">

    @if ($this->groupBreakdown->isEmpty())
        <div class="p-6">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('lingua::lingua.statistics.breakdown.empty') }}</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                            {{ __('lingua::lingua.statistics.breakdown.group') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                            {{ __('lingua::lingua.statistics.breakdown.total') }}
                        </th>
                        @foreach ($this->languages as $lang)
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400" title="{{ $lang->native }}">
                                {{ $lang->code }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($this->groupBreakdown as $group => $data)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <td class="px-6 py-3">
                                <code class="rounded bg-zinc-100 px-1.5 py-0.5 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">{{ $group }}</code>
                            </td>
                            <td class="px-6 py-3 tabular-nums text-zinc-500 dark:text-zinc-400">{{ $data['total'] }}</td>
                            @foreach ($this->languages as $lang)
                                @php
                                    $count = $data['locales'][$lang->code] ?? 0;
                                    $full  = $count === $data['total'];
                                @endphp
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium tabular-nums {{ $full ? 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-400' : 'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-400' }}">
                                        {{ $count }}/{{ $data['total'] }}
                                    </span>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

</x-lingua::card>
