<?php

use Livewire\Attributes\Modelable;
use Livewire\WithPagination;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\Translation;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component {

	use WithPagination;

	#[Url('search', except: ''), Modelable]
	public string $search = '';

	public bool $syncDatabase = true;
	public int $totalStrings;

	#[On('refreshLanguages')]
	public function refreshLanguages(): void
	{
		$this->resetPage();
		$this->renderIsland('languagesRows');
	}

	#[Computed]
	public function languages()
	{
		if (Language::query()->active()->get()->isEmpty()) {
			Translation::syncToDatabase();
		}

		$driver = DB::connection()->getDriverName();

		$like = match ($driver) {
			'pgsql' => "ilike",
			default => "LIKE",
		};

		return Language::query()->active()
		               ->when(!empty($this->search),
			               fn($query) => $query->whereAny(['code', 'regional', 'name', 'native'],
				               $like,
				               "%$this->search%"))->paginate(5);
	}
};
?>

@placeholder
<flux:table>
	<flux:table.columns>
		<flux:table.column style="width: 15%">@lang('rivalex::lingua.languages.table.language')</flux:table.column>
		<flux:table.column>@lang('rivalex::lingua.languages.table.status')</flux:table.column>
		<flux:table.column style="width: 10%" align="center">
			<flux:icon.cog/>
		</flux:table.column>
	</flux:table.columns>

	<flux:table.rows>
		@foreach (range(1, 5) as $line)
			<flux:table.row>
				<flux:table.cell>
					<flux:skeleton.group animate="shimmer" class="flex items-center gap-4">
						<div class="flex-1">
							<flux:skeleton.line/>
							<flux:skeleton.line class="w-1/2"/>
						</div>
					</flux:skeleton.group>
				</flux:table.cell>
				<flux:table.cell>
					<flux:skeleton.group animate="shimmer">
						<flux:skeleton.line class="w-1/4"/>
						<flux:skeleton.line/>
					</flux:skeleton.group>
				</flux:table.cell>
				<flux:table.cell align="center">
					<flux:skeleton animate="shimmer" class="size-10 rounded-md"/>
				</flux:table.cell>
			</flux:table.row>
		@endforeach
	</flux:table.rows>
</flux:table>
@endplaceholder

<flux:table :paginate="$this->languages">
	<flux:table.columns>
		<flux:table.column style="width: 15%">@lang('rivalex::lingua.languages.table.language')</flux:table.column>
		<flux:table.column>@lang('rivalex::lingua.languages.table.status')</flux:table.column>
		<flux:table.column style="width: 10%" align="center">
			<flux:icon.cog/>
		</flux:table.column>
	</flux:table.columns>

	@island(name: 'languagesRows', always: true)
	<flux:table.rows>
		@foreach ($this->languages as $language)
			<livewire:lingua::language.row :language-id="$language->id" :key="$language->id" lazy/>
		@endforeach
	</flux:table.rows>
	@endisland
</flux:table>
