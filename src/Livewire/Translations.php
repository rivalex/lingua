<?php

namespace Rivalex\Lingua\Livewire;

use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\Translation;

#[Title('Translations Manager')]
class Translations extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';
    #[Url(as: 'p', except: 10)]
    public int $perPage = 10;
    #[Url(as: 'g', except: '')]
    public string $group = '';
    #[Url(as: 'm', except: false)]
    public bool $showOnlyMissing = false;

    public Language $language;
    public array $availableLocale = [];
    public string $currentLocale;
    public array $availableGroups = [];
    public array $queryString = [];

    public function mount(?string $locale = null): void
    {
        $this->search = request('q', '');
        $this->perPage = request('p', 10);
        $this->group = request('g', '');
        $this->showOnlyMissing = request('m', false);

        $this->language = Language::where('code', $locale ?? defaultLocale())->first();
        $this->currentLocale = $this->language->code ?? $locale ?? app()->currentLocale();
        $this->setDefaults();
        $this->queryString = request()->query();
    }

    #[On('refreshTranslationsTableDefaults')]
    public function setDefaults(): void
    {
        $this->availableLocale = Language::query()->active()->pluck('native', 'code')->toArray();
        $this->availableGroups = Translation::orderBy('group')->groupBy('group')->pluck('group')->toArray();
    }

    public function updatedCurrentLocale(): void
    {
        $this->reset('showOnlyMissing');
        if ($this->search) {
            $this->queryString['s'] = $this->search;
        }
        if ($this->group) {
            $this->queryString['g'] = $this->group;
        }
        $this->redirect(route('lingua.translations',
            array_merge(['locale' => $this->currentLocale], $this->queryString)), true);
    }

    public function updatedGroup(): void
    {
        $this->resetPage();
        $this->dispatch('updateTranslationGroup', $this->group);
    }

    #[Computed]
    public function translations()
    {
        $locale = $this->currentLocale;
        $defaultLocale = defaultLocale();

        return Translation::query()
                          ->when(!empty($this->search),
                              fn($q) => $q->where(fn($query) => $query->whereLike('group_key', "%{$this->search}%")
                                                                      ->orWhereLike('text->' . $defaultLocale,
                                                                          "%{$this->search}%")
                                                                      ->orWhereLike('text->' . $locale,
                                                                          "%{$this->search}%"))
                          )
                          ->when($this->showOnlyMissing, fn($q) => $q->whereNull('text->' . $locale))
                          ->when($this->group, fn($q) => $q->where('group', '=', $this->group))
                          ->paginate($this->perPage);
    }

	public function render()
	{
		return view('lingua::translations');
	}
}
