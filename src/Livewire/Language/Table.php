<?php

declare(strict_types=1);

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

final class Table extends Component
{
    use WithPagination;

    #[Url('search', except: ''), Modelable]
    public string $search = '';

    #[On('refreshLanguages')]
    public function refreshLanguages(): void
    {
        $this->resetPage();
        $this->renderIsland('languagesRows');
    }

    #[Computed]
    public function languages()
    {
        // Bootstrap convenience: import from lang files when no language exists
        // yet. exists() avoids hydrating the whole table just to check emptiness.
        if (! Language::query()->exists()) {
            Translation::syncToDatabase();
        }

        $driver = DB::connection()->getDriverName();

        $like = match ($driver) {
            'pgsql' => 'ilike',
            default => 'LIKE',
        };

        // Escape LIKE wildcards with '!' and declare it via ESCAPE: backslash
        // escaping without an ESCAPE clause is MySQL/PG-only — SQLite and SQL
        // Server treat the backslash literally and the search silently breaks.
        $search = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $this->search);

        return Language::query()->active()
            ->when($this->search !== '',
                fn ($query) => $query->where(function ($inner) use ($like, $search) {
                    $grammar = $inner->getQuery()->getGrammar();
                    foreach (['code', 'regional', 'name', 'native'] as $column) {
                        $inner->orWhereRaw(
                            $grammar->wrap($column)." {$like} ? ESCAPE '!'",
                            ["%{$search}%"]
                        );
                    }
                }))->paginate(5);
    }

    public function render()
    {
        return view('lingua::language.table');
    }
}
