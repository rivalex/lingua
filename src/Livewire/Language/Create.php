<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Livewire\Language;

use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
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
final class Create extends Component
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
    #[Validate('required|string|regex:/^[a-zA-Z]{2,8}([_\-][a-zA-Z0-9]{1,8})*$/')]
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
            $lang = Lingua::info($locale);

            if ($lang === null) {
                continue;
            }

            $this->availableLanguages[] = [
                'code' => $lang->code,
                'label' => $lang->name,
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
     * Add a new language to the application (DB-native, no filesystem writes).
     *
     * 1. Validates the selected locale code
     * 2. Delegates record creation to Lingua::addLanguage()
     * 3. Synchronizes translations to the database
     * 4. Dispatches success events and refreshes the language list
     *
     * On failure, logs the error and dispatches a failure event.
     */
    public function addNewLanguage(): void
    {
        $this->validate();
        try {
            Lingua::addLanguage(locale: $this->language);
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
