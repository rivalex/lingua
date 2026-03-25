<?php

namespace Rivalex\Lingua\Livewire\Language;

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Component;
use Rivalex\Lingua\Facades\Lingua;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\Translation;
use Rivalex\Lingua\Traits\ModalsConfirm;

/**
 * Delete Language Livewire Component
 *
 * This component handles the deletion of a language from the system.
 * It manages the deletion process including:
 * - Removing language files via Artisan command
 * - Cleaning up translations in the database
 * - Updating language records and reordering
 * - Providing confirmation modal functionality via ModalsConfirm trait
 */
class Delete extends Component
{
    use ModalsConfirm;

    /**
     * The language instance to be deleted
     */
    public Language $language;

    /**
     * Initialize the component
     *
     * Sets up the modal name using the language code and prepares the confirmation
     * message by formatting and sanitizing the translated confirmation text.
     */
    public function mount(): void
    {
        $this->modalName = 'language-delete-modal-'.$this->language->code;
        $this->confirm = Str::of(__('lingua::lingua.languages.delete.confirm',
            ['language' => $this->language->name]))
            ->upper()->squish()->trim();
    }

    /**
     * Delete the language from the system
     *
     * This method performs the following operations:
     * 1. Validates the confirmation input
     * 2. Removes language files using Artisan command
     * 3. Removes translations for the language from database
     * 4. Deletes the language record
     * 5. Reorders remaining languages
     * 6. Closes the modal and dispatches refresh event
     *
     * On failure, logs the error and dispatches a failure event.
     *
     * @throws \Throwable
     */
    public function deleteLanguage(): void
    {
        $this->validate();
        try {
            $locale = $this->language->code;
            Lingua::removeLanguage($locale);
            $translations = Translation::whereNotNull('text->'.$locale)->get();
            foreach ($translations as $translation) {
                $translation->forgetTranslation($locale);
            }
            $this->language->delete();
            app(Language::class)->reorderLanguages();
            app(Translation::class)->syncToDatabase();
            $this->close();
            $this->dispatch('refreshLanguages');
        } catch (\Throwable $e) {
            $this->close();
            $this->dispatch('languages_sorted_fail');
            $this->addError('deleteLanguageError', $e->getMessage());
            Log::error('Languages delete failed! {error}', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Render the component
     *
     * Returns the delete language confirmation view.
     *
     * @return View|Factory
     */
    public function render()
    {
        return view('lingua::language.delete');
    }
}
