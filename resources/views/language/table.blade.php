<flux:table :paginate="$this->languages">
	<flux:table.columns>
		<flux:table.column style="width: 15%">@lang('lingua::lingua.languages.table.language')</flux:table.column>
		<flux:table.column>@lang('lingua::lingua.languages.table.status')</flux:table.column>
		<flux:table.column style="width: 10%" align="center">
			<flux:icon.cog/>
		</flux:table.column>
	</flux:table.columns>

	<flux:table.rows>
        @island(name: 'languagesRows', always: true)
            @foreach ($this->languages as $language)
                <livewire:lingua::language.row :language-id="$language->id" :key="$language->id" lazy/>
            @endforeach
        @endisland
	</flux:table.rows>
</flux:table>
