<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Livewire;

use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
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

    public ?Language $language = null;

    public array $availableLocale = [];

    public string $currentLocale;

    public array $availableGroups = [];

    public function mount(?string $locale = null): void
    {
        $this->search = request('q', '');
        $this->perPage = max(1, min((int) request('p', 10), 100));
        $this->group = request('g', '');
        $this->showOnlyMissing = request('m', false);

        $this->language = Language::where('code', $locale ?? linguaDefaultLocale())->first();
        // Fall back to default locale when the requested locale does not exist in DB.
        $this->currentLocale = $this->language?->code ?? linguaDefaultLocale();
        $this->setDefaults();
    }

    protected function setDefaults(): void
    {
        $this->availableLocale = Language::query()->active()->pluck('native', 'code')->toArray();
        $this->availableGroups = Translation::orderBy('group')->groupBy('group')->pluck('group')->toArray();
    }

    #[On('refreshTranslationsTableDefaults')]
    public function refreshTranslationsTableDefaults(): void
    {
        $this->setDefaults();
        $this->forceRender();
    }

    public function updatedCurrentLocale(): void
    {
        $this->reset('showOnlyMissing');
        $params = array_filter([
            'locale' => $this->currentLocale,
            'q' => $this->search ?: null,
            'g' => $this->group ?: null,
        ]);
        $this->redirect(route('lingua.translations', $params), true);
    }

    public function updatedGroup(): void
    {
        $this->resetPage();
        $this->dispatch('updateTranslationGroup', $this->group);
    }

    #[Computed]
    public function translations()
    {
        // Validate locale format before interpolating into JSON column paths to prevent SQL injection.
        // currentLocale is a Livewire public property and can be tampered via the network layer.
        $safeLocale = preg_match('/^[a-zA-Z]{2,8}([_-][a-zA-Z0-9]{1,8})*$/', $this->currentLocale)
            ? $this->currentLocale
            : linguaDefaultLocale();

        $defaultLocale = linguaDefaultLocale();

        return Translation::query()
            ->when(! empty($this->search),
                fn ($q) => $q->where(fn ($query) => $query->whereLike('group_key', "%{$this->search}%")
                    ->orWhereLike('text->'.$defaultLocale, "%{$this->search}%")
                    ->orWhereLike('text->'.$safeLocale, "%{$this->search}%"))
            )
            ->when($this->showOnlyMissing, fn ($q) => $q->whereNull('text->'.$safeLocale))
            ->when($this->group, fn ($q) => $q->where('group', '=', $this->group))
            ->paginate($this->perPage);
    }

    public function render()
    {
        return view('lingua::translations');
    }
}
