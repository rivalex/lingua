<?php

namespace Rivalex\Lingua\Livewire\Language;

use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Modelable;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\Translation;

class Table extends Component
{
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
            'pgsql' => 'ilike',
            default => 'LIKE',
        };

        return Language::query()->active()
            ->when(! empty($this->search),
                fn ($query) => $query->whereAny(['code', 'regional', 'name', 'native'],
                    $like,
                    "%$this->search%"))->paginate(5);
    }

    public function render()
    {
        return view('lingua::language.table');
    }
}
