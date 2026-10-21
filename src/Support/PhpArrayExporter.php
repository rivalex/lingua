<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Support;

/**
 * Renders a PHP array as a source-code string suitable for lang/*.php files.
 */
final class PhpArrayExporter
{
    /**
     * Export an array to a PHP code string.
     *
     * @param  array<mixed, mixed>  $array  Array to export
     * @param  string  $indent  Current indentation level
     * @return string PHP code representation of the array
     */
    public static function export(array $array, string $indent = ''): string
    {
        $content = "[\n";
        foreach ($array as $key => $value) {
            $content .= $indent.'    '.var_export($key, true).' => ';
            if (is_array($value)) {
                $content .= self::export($value, $indent.'    ');
            } else {
                $content .= var_export($value, true);
            }
            $content .= ",\n";
        }
        $content .= $indent.']';

        return $content;
    }
}
