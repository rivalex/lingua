<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Livewire;

use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Rivalex\Lingua\Contracts\TranslationRepository;
use Rivalex\Lingua\Models\Language;

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
        $this->availableGroups = app(TranslationRepository::class)->groups();
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
        $route = config('lingua.links.translations.route', 'lingua.translations');
        $this->redirect(route($route, $params), (bool) config('lingua.navigate', false));
    }

    public function updatedGroup(): void
    {
        $this->resetPage();
        $this->dispatch('updateTranslationGroup', $this->group);
    }

    #[Computed]
    public function translations()
    {
        return app(TranslationRepository::class)->paginate(
            locale: $this->currentLocale,
            search: $this->search,
            group: $this->group,
            onlyMissing: $this->showOnlyMissing,
            perPage: $this->perPage,
        );
    }

    public function render()
    {
        $view = view('lingua::translations');
        $layout = config('lingua.layout');

        return $layout ? $view->layout($layout) : $view;
    }
}
