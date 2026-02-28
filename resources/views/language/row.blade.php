<flux:table.row>
	<flux:table.cell class="flex items-center gap-2">
        <livewire:lingua::selector.icon :locale="languageCode($language->code)" size="lg"/>
		<div class="flex flex-col gap-0.5">
			<flux:link variant="ghost" wire:navigate
			           href="{{ route('lingua.translations', ['locale' => $language->code]) }}">
				<p class="font-bold text-lg">{{ $language->native }}</p>
			</flux:link>
			<p class="italic text-xs">{{ $language->name }}</p>
		</div>
	</flux:table.cell>

	<flux:table.cell>
		<div class="flex flex-col gap-2">
			<div class="flex flex-row text-xs gap-2 items-center">
				@if($language->is_default)
					<p class="font-black">@lang('lingua::lingua.languages.table.row.default_language')</p>
					<flux:separator vertical/>
					<p>@lang('lingua::lingua.languages.table.row.strings_total', ['count' => $language->total_strings])</p>
				@else
					<p>@lang('lingua::lingua.languages.table.row.strings_translated', ['count' => $language->translated_strings])</p>
					<flux:separator vertical/>
					<p>@lang('lingua::lingua.languages.table.row.strings_missing', ['count' => $language->missing_strings])</p>
					<flux:separator vertical/>
					<p>{{ $language->completion_percentage }}%</p>
					<flux:spacer/>
					<div wire:sort:ignore>
						<livewire:lingua::language.set-default :$language
						                                          :key="'setDefaultLanguage_'. $language->code"/>
					</div>
				@endif
			</div>

			<div class="overflow-hidden rounded-full bg-gray-200 dark:bg-white/10">
				<div @class([
                            'h-2 rounded-full',
                            'bg-orange-500' => (!$language->is_default && $language->missing_strings > 0),
                            'bg-sky-500' => (!$language->is_default && $language->missing_strings === 0),
                            'bg-green-500' => $language->is_default
                        ])
				     style="width: {{ $language->completion_percentage }}%"></div>
			</div>
		</div>
	</flux:table.cell>

	<flux:table.cell align="center" wire:sort:ignore>
		@if(!$language->is_default)
			<livewire:lingua::language.delete :$language/>
		@endif
	</flux:table.cell>
</flux:table.row>
