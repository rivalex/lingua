<?php

declare(strict_types=1);

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Rivalex\Lingua\Contracts\BaseTranslationSource;
use Rivalex\Lingua\Database\DatabaseRepository;
use Rivalex\Lingua\Enums\LinguaType;
use Rivalex\Lingua\Exceptions\UnsafeTranslationValue;
use Rivalex\Lingua\Models\Translation;
use Rivalex\Lingua\Storage\FileRepository;
use Rivalex\Lingua\Support\AtomicFileWriter;
use Rivalex\Lingua\Support\TranslationFileReader;
use Rivalex\Lingua\Support\TranslationValuePolicy;

const ATTRIBUTE_PAYLOAD = 'x" onmouseover="alert(1)';

beforeEach(function (): void {
    $this->attributeMessage = '[Lingua] The value of this translation is printed inside an HTML attribute and may not contain ", < or >.';
});

function renderSimplePagination(): string
{
    return (new Paginator(range(1, 5), 2, 1))->links('pagination::simple-bootstrap-5')->toHtml();
}

function renderTailwindPagination(): string
{
    return (new LengthAwarePaginator(range(1, 2), 6, 2, 2))->links('pagination::tailwind')->toHtml();
}

function renderBootstrapPagination(): string
{
    return (new LengthAwarePaginator(range(1, 2), 6, 2, 2))->links('pagination::bootstrap-5')->toHtml();
}

function parseAriaLabel(string $html): array
{
    $dom = new DOMDocument;
    @$dom->loadHTML($html);
    $nav = $dom->getElementsByTagName('nav')->item(0);

    return [$nav->getAttribute('aria-label'), $nav->hasAttribute('onmouseover')];
}

function previousLinkLabel(string $html): string
{
    $dom = new DOMDocument;
    @$dom->loadHTML($html);
    $link = (new DOMXPath($dom))->query('//a[@rel="prev" and @aria-label]')->item(0);

    return $link->getAttribute('aria-label');
}

function fileAttributeRepository(): array
{
    $dir = sys_get_temp_dir().'/lingua_attr_'.uniqid();
    mkdir($dir, 0755, true);

    return [new FileRepository(new AtomicFileWriter, new TranslationFileReader, $dir, app(BaseTranslationSource::class)), $dir];
}

function cleanupAttributeDirectory(string $dir): void
{
    foreach (glob($dir.'/en/*') ?: [] as $file) {
        unlink($file);
    }

    if (is_dir($dir.'/en')) {
        rmdir($dir.'/en');
    }

    foreach (glob($dir.'/*') ?: [] as $file) {
        unlink($file);
    }

    rmdir($dir);
}

it('rejects a value with an attribute breaking character for a key printed in an attribute (file mode)', function (): void {
    [$repo, $dir] = fileAttributeRepository();

    try {
        $line = $repo->create('single', 'Pagination Navigation', LinguaType::text, 'en', 'Navigation de pagination');

        // The value used to be stored encoded; it is now rejected.
        expect(fn () => $repo->setValue($line, 'en', ATTRIBUTE_PAYLOAD))
            ->toThrow(UnsafeTranslationValue::class, $this->attributeMessage);

        expect(fn () => $repo->create('pagination', 'previous', LinguaType::text, 'en', '<b>Prev</b>'))
            ->toThrow(UnsafeTranslationValue::class, $this->attributeMessage);

        $stored = json_decode(file_get_contents($dir.'/en.json'), true);
        expect($stored['Pagination Navigation'])->toBe('Navigation de pagination')
            ->and(file_exists($dir.'/en/pagination.php'))->toBeFalse();
    } finally {
        cleanupAttributeDirectory($dir);
    }
});

it('stores an accepted value byte for byte and renders it without double encoding (file mode)', function (): void {
    [$repo, $dir] = fileAttributeRepository();

    try {
        app()->setLocale('en');
        app('translator')->addJsonPath($dir);
        $line = $repo->create('single', 'Pagination Navigation', LinguaType::text, 'en', 'Navigation de pagination');

        foreach (['Navigation de pagination', "l'elenco", 'a & b'] as $value) {
            $line = $repo->setValue($line, 'en', $value);
            expect($line->text['en'])->toBe($value);

            app('translator')->setLoaded([]);

            expect(parseAriaLabel(renderSimplePagination())[0])->toBe($value)
                ->and(parseAriaLabel(renderTailwindPagination())[0])->toBe($value);
        }

        $previous = $repo->create('pagination', 'previous', LinguaType::text, 'en', "l'elenco");
        app('translator')->addLines(['pagination.previous' => $previous->text['en']], 'en');

        expect($previous->text['en'])->toBe("l'elenco")
            ->and(previousLinkLabel(renderBootstrapPagination()))->toBe("l'elenco");
    } finally {
        cleanupAttributeDirectory($dir);
    }
});

it('keeps an entity and leaves other keys alone (file mode)', function (): void {
    [$repo, $dir] = fileAttributeRepository();

    try {
        $entity = $repo->create('single', 'Pagination Navigation', LinguaType::text, 'en', '&laquo; Nav');
        $other = $repo->create('single', 'Other key', LinguaType::text, 'en', 'say "hi" & more');

        expect($entity->text['en'])->toBe('&laquo; Nav')
            ->and($other->text['en'])->toBe('say "hi" & more');
    } finally {
        cleanupAttributeDirectory($dir);
    }
});

it('rejects a value with an attribute breaking character for a key printed in an attribute (database mode)', function (): void {
    $repo = new DatabaseRepository(app(BaseTranslationSource::class));

    $line = $repo->create('single', 'Pagination Navigation', LinguaType::text, 'en', 'Navigation de pagination');

    // The value used to be stored encoded; it is now rejected.
    expect(fn () => $repo->setValue($line, 'en', ATTRIBUTE_PAYLOAD))
        ->toThrow(UnsafeTranslationValue::class, $this->attributeMessage);

    expect(fn () => $repo->create('pagination', 'next', LinguaType::text, 'en', 'Next >'))
        ->toThrow(UnsafeTranslationValue::class, $this->attributeMessage);

    expect(Translation::where('key', 'Pagination Navigation')->firstOrFail()->text['en'])->toBe('Navigation de pagination')
        ->and(Translation::where('group', 'pagination')->where('key', 'next')->get()->map(fn (Translation $line) => $line->text['en'] ?? null)->contains('Next >'))->toBeFalse()
        ->and(TranslationValuePolicy::assertAcceptable(LinguaType::text, 'single', 'Pagination Navigation', ATTRIBUTE_PAYLOAD))->toBe($this->attributeMessage)
        ->and(TranslationValuePolicy::assertAcceptable(LinguaType::html, 'pagination', 'next', '<b>Next</b>'))->toBe($this->attributeMessage);
});

it('stores an accepted value byte for byte and leaves other keys alone (database mode)', function (): void {
    $repo = new DatabaseRepository(app(BaseTranslationSource::class));

    $line = $repo->create('single', 'Pagination Navigation', LinguaType::text, 'en', 'Navigation de pagination');
    $updated = $repo->setValue($line, 'en', "l'elenco &laquo;");
    $other = $repo->create('single', 'Other key', LinguaType::text, 'en', 'say "hi" & more');

    expect($updated->text['en'])->toBe("l'elenco &laquo;")
        ->and(Translation::where('key', 'Other key')->firstOrFail()->text['en'])->toBe('say "hi" & more')
        ->and($other->text['en'])->toBe('say "hi" & more');
});

it('rejects a value with an attribute breaking character for the JSON shadow of a pagination key (file mode)', function (): void {
    [$repo, $dir] = fileAttributeRepository();

    try {
        expect(fn () => $repo->create('single', 'pagination.previous', LinguaType::text, 'en', ATTRIBUTE_PAYLOAD))
            ->toThrow(UnsafeTranslationValue::class, $this->attributeMessage);

        $line = $repo->create('single', 'pagination.previous', LinguaType::text, 'en', 'Previous');

        expect(fn () => $repo->setValue($line, 'en', ATTRIBUTE_PAYLOAD))
            ->toThrow(UnsafeTranslationValue::class, $this->attributeMessage);

        expect(json_decode(file_get_contents($dir.'/en.json'), true)['pagination.previous'])->toBe('Previous');
    } finally {
        cleanupAttributeDirectory($dir);
    }
});

it('rejects a value with an attribute breaking character for the JSON shadow of a pagination key (database mode)', function (): void {
    $repo = new DatabaseRepository(app(BaseTranslationSource::class));

    expect(fn () => $repo->create('single', 'pagination.previous', LinguaType::text, 'en', ATTRIBUTE_PAYLOAD))
        ->toThrow(UnsafeTranslationValue::class, $this->attributeMessage);

    $line = $repo->create('single', 'pagination.next', LinguaType::text, 'en', 'Next');

    expect(fn () => $repo->setValue($line, 'en', ATTRIBUTE_PAYLOAD))
        ->toThrow(UnsafeTranslationValue::class, $this->attributeMessage);

    expect(Translation::where('group', 'single')->where('key', 'pagination.next')->firstOrFail()->text['en'])->toBe('Next');
});

it('checks an html value after sanitizing it, so an encoded quote is rejected', function (): void {
    $repo = new DatabaseRepository(app(BaseTranslationSource::class));
    $payload = '&quot; onmouseover=&quot;alert(1)';

    expect(fn () => $repo->create('single', 'pagination.previous', LinguaType::html, 'en', $payload))
        ->toThrow(UnsafeTranslationValue::class, $this->attributeMessage);

    expect(fn () => TranslationValuePolicy::normalize(LinguaType::html, $payload, 'pagination', 'previous'))
        ->toThrow(UnsafeTranslationValue::class, $this->attributeMessage)
        ->and(TranslationValuePolicy::assertAcceptable(LinguaType::html, 'pagination', 'previous', $payload))->toBe($this->attributeMessage);
});

it('accepts an html value with an entity for a pagination key', function (): void {
    $repo = new DatabaseRepository(app(BaseTranslationSource::class));

    $line = $repo->create('single', 'pagination.next', LinguaType::html, 'en', 'Next &raquo;');

    expect(TranslationValuePolicy::assertAcceptable(LinguaType::html, 'pagination', 'next', 'Next &raquo;'))->toBeNull()
        ->and($line->text['en'])->toContain('Next');
});
