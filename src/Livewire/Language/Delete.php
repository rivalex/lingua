<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Livewire\Language;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Component;
use Rivalex\Lingua\Contracts\TranslationRepository;
use Rivalex\Lingua\Facades\Lingua;
use Rivalex\Lingua\Models\Language;
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
final class Delete extends Component
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
            ->upper()->squish()->trim()->toString();
    }

    /**
     * Delete the language from the system
     *
     * This method performs the following operations:
     * 1. Validates the confirmation input
     * 2. Removes the locale's translation values from the repository
     * 3. Deletes the Language record and unprojects notification keys
     *    (both via Lingua::removeLanguage)
     * 4. Reorders remaining languages
     * 5. Closes the modal and dispatches refresh event
     *
     * No syncToDatabase() afterwards: re-syncing would re-import the locale
     * from lang/{locale} files and recreate the Language record, silently
     * undoing the removal.
     *
     * On failure, logs the error and dispatches a failure event.
     *
     * @throws \Throwable
     */
    public function deleteLanguage(): void
    {
        $this->validateConfirmControl();
        try {
            $locale = $this->language->code;
            $repo = app(TranslationRepository::class);
            $repo->all()
                ->filter(fn ($line) => array_key_exists($locale, $line->text))
                ->each(fn ($line) => $repo->forgetLocale($line, $locale));
            // removeLanguage() unprojects notification keys AND deletes the
            // Language record — no second delete on the in-memory model.
            Lingua::removeLanguage($locale);
            app(Language::class)->reorderLanguages();
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
     */
    public function render()
    {
        return view('lingua::language.delete');
    }
}
