<?php

namespace Rivalex\Lingua\Livewire\Language;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use LaravelLang\Locales\Facades\Locales;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Rivalex\Lingua\Facades\Lingua;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\Translation;
use Rivalex\Lingua\Traits\Modals;

/**
 * Livewire component for creating and adding new languages to the application.
 *
 * This component handles the display and processing of a modal form that allows
 * administrators to add new languages to the system. It integrates with Laravel Lang
 * to fetch available locales, creates language records in the database, and triggers
 * Artisan commands to install language files.
 */
class Create extends Component
{
    use Modals;

    /**
     * List of available languages that can be added to the application.
     *
     * Each array element contains:
     * - code: The language locale code (e.g., 'es', 'fr')
     * - label: The English name of the language
     * - description: The native name of the language
     *
     * @var array<int, array{code: string, label: string, description: string}>
     */
    public array $availableLanguages = [];

    /**
     * The selected language code to be added.
     *
     * This property is bound to the form input and validated to ensure
     * a language is selected before creation.
     */
    #[Validate('required|string')]
    public string $language = '';

    /**
     * Initialize the component when it is mounted.
     *
     * Sets the modal identifier and populates the available languages list
     * by fetching all languages that are not currently installed in the system.
     */
    public function mount(): void
    {
        $this->modalName = 'language-create-modal';
        $this->setDefaults();
    }

    /**
     * Reset and populate the available languages array.
     *
     * Fetches all languages that are not currently installed in the system
     * using Laravel Lang's Locales facade. Each language's code, English name,
     * and native name are stored in the availableLanguages array. Silently
     * skips any locales that throw errors during info retrieval.
     */
    protected function setDefaults(): void
    {
        $this->reset('availableLanguages');

        foreach (Lingua::notInstalled() as $locale) {
            try {
                $lang = Lingua::info($locale);
            } catch (\Throwable) {
                continue;
            }
            $this->availableLanguages[] = [
                'code' => $lang->code,
                'label' => $lang->locale->name,
                'description' => $lang->native,
            ];
        }
    }

    /**
     * Refresh the available languages list when the 'refreshLanguages' event is dispatched.
     *
     * This method listens for the 'refreshLanguages' Livewire event and repopulates
     * the availableLanguages array to reflect any changes in installed languages.
     * Typically called after a language is added or removed.
     */
    #[On('refreshLanguages')]
    public function refreshLanguages(): void
    {
        $this->setDefaults();
    }

    /**
     * Add a new language to the application.
     *
     * This method performs the following operations:
     * 1. Validates the selected language code
     * 2. Retrieves language information from Laravel Lang
     * 3. Executes the 'lang:add' Artisan command to install language files
     * 4. Creates a new Language record in the database with all locale details
     * 5. Synchronizes translations to the database
     * 6. Dispatches success events and refreshes the language list
     * 7. Closes the modal and resets the form
     *
     * On failure, logs the error, dispatches a failure event, and closes the modal.
     */
    public function addNewLanguage(): void
    {
        $this->validate();
        try {
            $newLanguage = Lingua::info(locale: $this->language);
            Lingua::addLanguage(locale: $this->language);
            app(Language::class)->create([
                'code' => $newLanguage->code,
                'regional' => $newLanguage->regional,
                'type' => $newLanguage->type,
                'name' => $newLanguage->locale->name,
                'native' => $newLanguage->native,
                'direction' => $newLanguage->direction,
                'is_default' => false,
            ]);
            app(Translation::class)->syncToDatabase();
            $this->dispatch('refreshLanguages');
            $this->dispatch('language_added');
            $this->reset('language');
            $this->closeModal();
        } catch (\Exception $e) {
            $this->reset('language');
            $this->closeModal();
            $this->addError('addLanguageError', $e->getMessage());
            $this->dispatch('language_added_fail');
            Log::error('Add language failed! {error}', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Render the component's view.
     *
     * Returns the Blade view for the language creation modal form.
     *
     * @return View
     */
    public function render()
    {
        return view('lingua::language.create');
    }
}
