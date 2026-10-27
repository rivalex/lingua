<?php

declare(strict_types=1);

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Rivalex\Lingua\Contracts\TranslationRepository;
use Rivalex\Lingua\Enums\LinguaType;
use Rivalex\Lingua\Support\TranslationLine;
use Rivalex\Lingua\Transfer\Enums\TransferFilter;
use Rivalex\Lingua\Transfer\Enums\TransferScope;
use Rivalex\Lingua\Transfer\ExportService;
use Rivalex\Lingua\Transfer\Format\CsvReader;
use Rivalex\Lingua\Transfer\Format\FormatRegistry;
use Rivalex\Lingua\Transfer\RowMapper;
use Rivalex\Lingua\Transfer\TransferSchema;
use Symfony\Component\HttpFoundation\StreamedResponse;

// Helper to build a mock repository returning given lines
function mockRepo(array $lines): TranslationRepository
{
    $collection = Collection::make($lines);

    return new class($collection) implements TranslationRepository
    {
        public function __construct(private readonly Collection $lines) {}

        public function all(bool $includeVendor = true): Collection
        {
            if ($includeVendor) {
                return $this->lines;
            }

            return $this->lines->filter(fn (TranslationLine $l) => ! $l->isVendor)->values();
        }

        // Stub required interface methods
        public function create(string $group, string $key, LinguaType $type, string $locale, string $value, bool $isVendor = false, ?string $vendor = null): TranslationLine
        {
            throw new BadMethodCallException('not used');
        }

        public function setValue(TranslationLine $line, string $locale, string $value): TranslationLine
        {
            throw new BadMethodCallException('not used');
        }

        public function updateMeta(TranslationLine $line, string $group, string $key, LinguaType $type): TranslationLine
        {
            throw new BadMethodCallException('not used');
        }

        public function forgetLocale(TranslationLine $line, string $locale): void {}

        public function deleteKey(TranslationLine $line): void {}

        public function groups(): array
        {
            return [];
        }

        public function find(string $group, string $key, bool $isVendor, ?string $vendor): ?TranslationLine
        {
            return null;
        }

        public function paginate(string $locale, string $search, string $group, bool $onlyMissing, int $perPage): LengthAwarePaginator
        {
            throw new BadMethodCallException('not used');
        }

        public function counts(): array
        {
            return ['total' => 0, 'byLocale' => []];
        }

        public function localeStats(string $locale): array
        {
            return ['total' => 0, 'translated' => 0, 'missing' => 0, 'percentage' => 0];
        }

        public function findByKey(string $key): ?TranslationLine
        {
            return null;
        }

        public function byGroup(string $group, ?string $locale = null): Collection
        {
            return Collection::make();
        }

        public function vendor(string $vendor, ?string $locale = null): Collection
        {
            return Collection::make();
        }

        public function installLocale(string $locale): void {}
    };
}

function makeExportLine(
    string $group,
    string $key,
    array $text,
    bool $isVendor = false,
    ?string $vendor = null,
): TranslationLine {
    $groupKey = $isVendor && $vendor ? "{$vendor}::{$group}.{$key}" : "{$group}.{$key}";

    return new TranslationLine($group, $key, $groupKey, LinguaType::text, $text, $isVendor, $vendor);
}

// Export then parse the CSV back via capturing StreamedResponse output
function captureExportCsv(ExportService $service, TransferScope $scope, TransferFilter $filter, string $default, ?string $target, bool $vendor, array $allLocales): array
{
    $response = $service->export($scope, $filter, 'csv', $default, $target, $vendor, $allLocales);

    ob_start();
    $response->sendContent();
    $csv = ob_get_clean();

    $path = tempnam(sys_get_temp_dir(), 'lingua_export_test_');
    file_put_contents($path, $csv);
    $rows = iterator_to_array((new CsvReader)->read($path));
    @unlink($path);

    return $rows;
}

test('export returns a StreamedResponse', function (): void {
    $service = new ExportService(mockRepo([]), new FormatRegistry, new RowMapper);
    $response = $service->export(TransferScope::bilingual, TransferFilter::all, 'csv', 'en', 'it', false, ['en', 'it']);

    expect($response)->toBeInstanceOf(StreamedResponse::class);
});

test('export bilingual all: includes all rows', function (): void {
    $lines = [
        makeExportLine('auth', 'login', ['en' => 'Login', 'it' => 'Accedi']),
        makeExportLine('auth', 'logout', ['en' => 'Logout', 'it' => 'Esci']),
    ];
    $service = new ExportService(mockRepo($lines), new FormatRegistry, new RowMapper);

    $rows = captureExportCsv($service, TransferScope::bilingual, TransferFilter::all, 'en', 'it', false, ['en', 'it']);

    // 1 header + 2 data rows
    expect($rows)->toHaveCount(3);
});

test('export bilingual onlyMissing: excludes rows where target locale has value', function (): void {
    $lines = [
        makeExportLine('auth', 'login', ['en' => 'Login', 'it' => 'Accedi']),   // it = filled
        makeExportLine('auth', 'logout', ['en' => 'Logout']),                    // it = missing
    ];
    $service = new ExportService(mockRepo($lines), new FormatRegistry, new RowMapper);

    $rows = captureExportCsv($service, TransferScope::bilingual, TransferFilter::onlyMissing, 'en', 'it', false, ['en', 'it']);

    // 1 header + 1 row (only the missing one)
    expect($rows)->toHaveCount(2);

    $headers = $rows[0];
    $keyIdx = array_search(TransferSchema::KEY, $headers);
    expect($rows[1][$keyIdx])->toBe('auth.logout');
});

test('export vendor rows included when includeVendor=true', function (): void {
    $lines = [
        makeExportLine('messages', 'hello', ['en' => 'Hello']),
        makeExportLine('pagination', 'next', ['en' => 'Next'], true, 'spatie'),
    ];
    $service = new ExportService(mockRepo($lines), new FormatRegistry, new RowMapper);

    $rows = captureExportCsv($service, TransferScope::bilingual, TransferFilter::all, 'en', 'it', true, ['en', 'it']);

    expect($rows)->toHaveCount(3); // header + 2 rows
});

test('export vendor rows excluded when includeVendor=false', function (): void {
    $lines = [
        makeExportLine('messages', 'hello', ['en' => 'Hello']),
        makeExportLine('pagination', 'next', ['en' => 'Next'], true, 'spatie'),
    ];
    $service = new ExportService(mockRepo($lines), new FormatRegistry, new RowMapper);

    $rows = captureExportCsv($service, TransferScope::bilingual, TransferFilter::all, 'en', 'it', false, ['en', 'it']);

    expect($rows)->toHaveCount(2); // header + 1 row (vendor excluded)
});

test('export csv filename contains scope and locale in Content-Disposition header', function (): void {
    $service = new ExportService(mockRepo([]), new FormatRegistry, new RowMapper);
    $response = $service->export(TransferScope::bilingual, TransferFilter::all, 'csv', 'en', 'it', false, []);

    $disposition = $response->headers->get('Content-Disposition');
    expect($disposition)->toContain('bilingual')
        ->and($disposition)->toContain('it')
        ->and($disposition)->toContain('.csv');
});
