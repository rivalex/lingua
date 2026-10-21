<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Models;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Rivalex\Lingua\Enums\SelectorMode;

/**
 * Model LinguaSetting
 *
 * Persists package-level settings in the lingua_settings table using a
 * key/value/type scheme. Values are stored as strings and cast back to
 * their original PHP type on retrieval.
 *
 * ### Known keys (use the public constants):
 * - `KEY_SHOW_FLAGS`   — selector.show_flags (bool)
 * - `KEY_SELECTOR_MODE` — selector.mode (string, validated against SelectorMode)
 *
 * ### Fallback priority:
 * 1. DB value (this model)
 * 2. $default parameter supplied by the caller (caller passes config() as fallback)
 *
 * ### Example usage:
 * ```php
 * // Read with config() as default
 * $show = LinguaSetting::get(LinguaSetting::KEY_SHOW_FLAGS, config('lingua.selector.show_flags', true));
 *
 * // Write
 * LinguaSetting::set(LinguaSetting::KEY_SHOW_FLAGS, true);
 * LinguaSetting::set(LinguaSetting::KEY_SELECTOR_MODE, 'modal');
 * ```
 */
final class LinguaSetting extends Model
{
    /** @var string */
    protected $table = 'lingua_settings';

    /** @var list<string> */
    protected $fillable = ['key', 'value', 'type'];

    // -------------------------------------------------------------------------
    // Known setting keys
    // -------------------------------------------------------------------------

    /** Whether the language selector should display flag icons. */
    public const string KEY_SHOW_FLAGS = 'selector.show_flags';

    /** Which rendering mode the language selector uses (sidebar|modal|dropdown|headless). */
    public const string KEY_SELECTOR_MODE = 'selector.mode';

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Retrieve a setting value, cast to its stored PHP type.
     *
     * Priority: DB row → $default parameter.
     * The caller should pass config('lingua.key', fallback) as $default so
     * that the full priority chain (DB → config → hardcoded) is honoured.
     *
     * @param  string  $key  Dot-notation setting key.
     * @param  mixed  $default  Fallback when no DB row exists.
     * @return mixed Value cast to the appropriate PHP type.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $row = self::where('key', $key)->first();

        if ($row === null) {
            return $default;
        }

        return self::castValue($row->value, $row->type);
    }

    /**
     * Persist a setting value to the database.
     *
     * The PHP type of $value is used to detect the storage type automatically.
     * For 'selector.mode', $value is validated against SelectorMode enum cases.
     *
     * @param  string  $key  Dot-notation setting key.
     * @param  mixed  $value  Value to store.
     *
     * @throws InvalidArgumentException When selector.mode receives an invalid value.
     */
    public static function set(string $key, mixed $value): void
    {
        if ($key === self::KEY_SELECTOR_MODE) {
            self::validateSelectorMode($value);
        }

        $type = self::detectType($value);

        self::updateOrCreate(
            ['key' => $key],
            ['value' => self::encodeValue($value, $type), 'type' => $type],
        );
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Detect the storage type string from a PHP value.
     */
    private static function detectType(mixed $value): string
    {
        return match (true) {
            is_bool($value) => 'bool',
            is_int($value) => 'int',
            is_array($value) => 'json',
            default => 'string',
        };
    }

    /**
     * Encode a PHP value to its string representation for storage.
     */
    private static function encodeValue(mixed $value, string $type): string
    {
        return match ($type) {
            'bool' => $value ? '1' : '0',
            'int' => (string) $value,
            'json' => json_encode($value, JSON_THROW_ON_ERROR),
            default => (string) $value,
        };
    }

    /**
     * Cast a stored string value back to its PHP type.
     */
    private static function castValue(?string $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'bool' => $value === '1',
            'int' => (int) $value,
            'json' => json_decode($value, true, 512, JSON_THROW_ON_ERROR),
            default => $value,
        };
    }

    /**
     * Validate that a value for KEY_SELECTOR_MODE is a known SelectorMode value.
     *
     * @throws InvalidArgumentException
     */
    private static function validateSelectorMode(mixed $value): void
    {
        $valid = array_column(SelectorMode::cases(), 'value');

        if (! in_array($value, $valid, strict: true)) {
            throw new InvalidArgumentException(
                sprintf('Invalid selector mode "%s". Valid values: %s.', $value, implode(', ', $valid)),
            );
        }
    }
}
