<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Livewire\Translation;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Rivalex\Lingua\Contracts\TranslationRepository;
use Rivalex\Lingua\Enums\LinguaType;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Support\TranslationIdentityRules;
use Rivalex\Lingua\Support\TranslationLine;
use Rivalex\Lingua\Support\TranslationValuePolicy;
use Rivalex\Lingua\Traits\AuthorizesLingua;
use Rivalex\Lingua\Traits\Modals;

final class Update extends Component
{
    use AuthorizesLingua;
    use Modals;

    #[Locked]
    public string $translationIdentity;

    #[Locked]
    public string $currentLocale;

    #[Validate]
    public ?string $group = null;

    #[Validate]
    public ?string $key = null;

    #[Validate]
    public string $textValue = '';

    #[Validate]
    public string $htmlValue = '';

    #[Validate]
    public string $mdValue = '';

    #[Locked]
    public array $translationsTypes = [];

    public string $translationType = LinguaType::text->value;

    #[Locked]
    public array $groups = [];

    #[Locked]
    public bool $required = false;

    #[Locked]
    public bool $locked = false;

    #[Locked]
    public bool $isVendor = false;

    #[Locked]
    public bool $fileMode = false;

    /**
     * Re-fetch the TranslationLine from the repository using the stable identity.
     */
    #[Computed]
    public function line(): TranslationLine
    {
        $parts = explode('|', $this->translationIdentity, 4);
        $isVendor = ($parts[2] ?? '0') === '1';
        $vendor = (($parts[3] ?? '') !== '') ? $parts[3] : null;

        return app(TranslationRepository::class)->find($parts[0], $parts[1], $isVendor, $vendor)
            ?? new TranslationLine(
                group: $parts[0],
                key: $parts[1],
                groupKey: $parts[0].'.'.$parts[1],
                type: LinguaType::text,
                text: [],
                isVendor: $isVendor,
                vendor: $vendor,
            );
    }

    public function rules(): array
    {
        $isDefault = $this->currentLocale === linguaDefaultLocale();

        // Only a changed identity is validated (group and key together, like the repository):
        // rows synchronized from host files keep their names.
        $normalizedGroup = Str::of($this->group ?? $this->line->group)->squish()->trim()->toString();
        $normalizedKey = Str::of($this->key ?? $this->line->key)->squish()->trim()->toString();
        $identityUnchanged = $normalizedGroup === $this->line->group && $normalizedKey === $this->line->key;

        $groupRule = function (string $attribute, mixed $value, Closure $fail) use ($identityUnchanged, $normalizedGroup): void {
            if ($identityUnchanged) {
                return;
            }
            $error = TranslationIdentityRules::groupError($normalizedGroup);
            if ($error === null) {
                $groups = app(TranslationRepository::class)->groups();
                $error = in_array($normalizedGroup, $groups, true) ? null : TranslationIdentityRules::groupCaseError($normalizedGroup, $groups);
            }
            if ($error !== null) {
                $fail($error);
            }
        };
        $keyRule = function (string $attribute, mixed $value, Closure $fail) use ($identityUnchanged, $normalizedGroup, $normalizedKey): void {
            if ($identityUnchanged) {
                return;
            }
            $error = TranslationIdentityRules::keyError($normalizedGroup, $normalizedKey);
            if ($error === null && ! $this->line->isVendor) {
                // The row's own current key is not a conflict when it stays in the same group.
                $existingKeys = array_values(array_filter(
                    TranslationIdentityRules::existingKeys(app(TranslationRepository::class), $normalizedGroup, false, null),
                    fn (string $existing) => $normalizedGroup !== $this->line->group || $existing !== $this->line->key,
                ));
                $error = TranslationIdentityRules::collisionError($normalizedGroup, $normalizedKey, $existingKeys);
            }
            if ($error !== null) {
                $fail($error);
            }
        };
        $valueRule = fn (LinguaType $type): Closure => function (string $attribute, mixed $value, Closure $fail) use ($type, $normalizedGroup, $normalizedKey): void {
            // Only the field of the selected type is written; the others hold a copy of the same value.
            if (! linguaIsFileMode() && $this->translationType !== $type->value) {
                return;
            }
            $error = TranslationValuePolicy::assertAcceptable($type, $normalizedGroup, $normalizedKey, (string) $value);
            if ($error !== null) {
                $fail($error);
            }
        };

        if (linguaIsFileMode()) {
            return [
                'currentLocale' => 'string',
                'group' => ['required', 'string', $groupRule],
                'key' => ['required', 'string', 'min:2', $keyRule],
                'textValue' => [Rule::requiredIf($isDefault), 'nullable', 'string', $valueRule(LinguaType::text)],
            ];
        }

        return [
            'currentLocale' => 'string',
            'group' => ['required', 'string', $groupRule],
            'key' => [
                'required',
                'string',
                'min:2',
                $keyRule,
                Rule::unique('language_lines', 'key')
                    ->where('group', $this->group ?? $this->line->group)
                    ->where('is_vendor', $this->line->isVendor)
                    ->ignore($this->line->id),
            ],
            'translationType' => ['required', Rule::enum(LinguaType::class)],
            'textValue' => [
                Rule::requiredIf($this->translationType === 'text' && $isDefault),
                'nullable',
                'string',
                $valueRule(LinguaType::text),
            ],
            'htmlValue' => [
                Rule::requiredIf($this->translationType === 'html' && $isDefault),
                'nullable',
                'string',
                $valueRule(LinguaType::html),
            ],
            'mdValue' => [
                Rule::requiredIf($this->translationType === 'markdown' && $isDefault),
                'nullable',
                'string',
                $valueRule(LinguaType::markdown),
            ],
        ];
    }

    public function mount(): void
    {
        $this->fileMode = linguaIsFileMode();
        $this->isVendor = $this->line->isVendor;
        $this->setDefaults();
    }

    #[On('updateTranslationModal.{translationIdentity}')]
    public function setDefaults(): void
    {
        $this->reset('group', 'key', 'textValue', 'htmlValue', 'mdValue');
        $this->getGroupsList();
        unset($this->line);
        $line = $this->line;
        $this->group = $line->group;
        $this->key = $line->key;
        $this->required = $this->currentLocale === linguaDefaultLocale();
        $this->locked = $this->fileMode || $this->currentLocale !== linguaDefaultLocale();
        $this->textValue = $line->value($this->currentLocale);

        if ($this->fileMode) {
            $this->translationType = LinguaType::text->value;
        } else {
            $this->htmlValue = $line->value($this->currentLocale);
            $this->mdValue = $line->value($this->currentLocale);
            $this->translationType = $line->type->value;
        }
    }

    protected function getGroupsList(): void
    {
        $this->reset('groups', 'translationsTypes');
        foreach (app(TranslationRepository::class)->groups() as $group) {
            $this->groups[] = ['id' => $group, 'name' => $group, 'disabled' => false];
        }
        if (! $this->fileMode) {
            $this->translationsTypes = LinguaType::selectValues();
        }
    }

    public function updateTranslation(): void
    {
        $this->abortUnlessKnown();

        // The UI lock is enforced here: identity and type are editable only on the default locale in database mode.
        if ($this->currentLocale !== linguaDefaultLocale() || linguaIsFileMode()) {
            $this->group = $this->line->group;
            $this->key = $this->line->key;
            $this->translationType = $this->line->type->value;
        }

        $this->validate();
        try {
            $repo = app(TranslationRepository::class);

            if (linguaIsFileMode()) {
                $line = $repo->setValue($this->line, $this->currentLocale, $this->textValue);
                $this->translationIdentity = $line->identity();
            } else {
                $translationValue = match ($this->translationType) {
                    'text' => $this->textValue,
                    'html' => $this->htmlValue,
                    'markdown' => $this->mdValue,
                    default => ''
                };

                // One transaction: a rejected value rolls the meta change back.
                $line = DB::transaction(function () use ($repo, $translationValue) {
                    $line = $repo->updateMeta(
                        $this->line,
                        Str::of($this->group)->squish()->trim()->toString(),
                        Str::of($this->key)->squish()->trim()->toString(),
                        LinguaType::from($this->translationType),
                    );

                    return $repo->setValue($line, $this->currentLocale, $translationValue);
                });
                $this->translationIdentity = $line->identity();
            }
            unset($this->line);
            $this->setDefaults();
            $this->dispatch('refreshTranslationRow.'.$this->translationIdentity);
            $this->dispatch($this->line->groupKey.'_updated');
            $this->dispatch('translation_updated');
            $this->closeModal();
        } catch (\Throwable $e) {
            $this->closeModal();
            $this->dispatch('translation_update_fail');
            Log::error('Translation update failed! {error}', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Abort with a 404 when the locale or the translation identity does not exist.
     * The synthetic line built by line() is for rendering only, never for writes.
     */
    protected function abortUnlessKnown(): void
    {
        abort_unless(Language::where('code', $this->currentLocale)->exists(), 404);

        $parts = explode('|', $this->translationIdentity, 4);
        $vendor = (($parts[3] ?? '') !== '') ? $parts[3] : null;
        abort_if(
            app(TranslationRepository::class)->find($parts[0], $parts[1] ?? '', ($parts[2] ?? '0') === '1', $vendor) === null,
            404,
        );
    }

    public function render()
    {
        return view('lingua::translation.update');
    }
}
