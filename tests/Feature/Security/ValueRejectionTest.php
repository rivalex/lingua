<?php

declare(strict_types=1);

use Livewire\Livewire;
use Rivalex\Lingua\Contracts\BaseTranslationSource;
use Rivalex\Lingua\Database\DatabaseRepository;
use Rivalex\Lingua\Enums\LinguaType;
use Rivalex\Lingua\Exceptions\UnsafeTranslationValue;
use Rivalex\Lingua\Livewire\Translation\Create;
use Rivalex\Lingua\Livewire\Translation\Row;
use Rivalex\Lingua\Models\Translation;
use Rivalex\Lingua\Support\HtmlSanitizer;
use Rivalex\Lingua\Support\TranslationValuePolicy;
use Rivalex\Lingua\Transfer\Format\CsvWriter;
use Rivalex\Lingua\Transfer\Format\FormatRegistry;
use Rivalex\Lingua\Transfer\ImportCommitService;
use Rivalex\Lingua\Transfer\ImportDiffService;
use Rivalex\Lingua\Transfer\RowMapper;
use Rivalex\Lingua\Transfer\TransferSchema;

beforeEach(function (): void {
    $this->textMessage = '[Lingua] The value contains HTML that is not allowed for a text translation.';
});

it('rejects a text value with an unsafe tag through the repository', function (): void {
    $repo = new DatabaseRepository(app(BaseTranslationSource::class));

    expect(fn () => $repo->create('app', 'unsafe_text', LinguaType::text, 'en', '<img src=x onerror=1>'))
        ->toThrow(UnsafeTranslationValue::class, $this->textMessage);

    expect(Translation::where('key', 'unsafe_text')->exists())->toBeFalse();
});

it('fails validation in Create for a text value with an unsafe tag', function (): void {
    Livewire::test(Create::class)
        ->set('group', 'app')
        ->set('key', 'unsafe_text_ui')
        ->set('translationType', 'text')
        ->set('textValue', '<img src=x onerror=1>')
        ->call('addNewTranslation')
        ->assertHasErrors(['textValue']);

    expect(Translation::where('key', 'unsafe_text_ui')->exists())->toBeFalse();
});

it('fails validation in Row for a text value with an unsafe tag', function (): void {
    $model = Translation::factory()->core()->create(['group' => 'app', 'key' => 'row_unsafe', 'type' => LinguaType::text, 'text' => ['en' => 'Safe']]);
    $identity = (new DatabaseRepository(app(BaseTranslationSource::class)))->toLine($model)->identity();

    Livewire::test(Row::class, ['translationIdentity' => $identity, 'currentLocale' => 'en'])
        ->set('value', '<img src=x onerror=1>')
        ->assertHasErrors(['value']);

    expect($model->fresh()->text['en'])->toBe('Safe');
});

it('rejects a markdown autolink and stores a markdown link unchanged', function (): void {
    $repo = new DatabaseRepository(app(BaseTranslationSource::class));

    expect(fn () => $repo->create('app', 'autolink', LinguaType::markdown, 'en', 'See <https://laravel.com>'))
        ->toThrow(UnsafeTranslationValue::class, '[Lingua] The value contains HTML that is not allowed for a markdown translation.');

    $created = $repo->create('app', 'md_link', LinguaType::markdown, 'en', '[Laravel](https://laravel.com)');

    expect($created->text['en'])->toBe('[Laravel](https://laravel.com)');
});

dataset('byte for byte values', [
    'spaced less-than' => ['a < b'],
    'allowed tag with placeholder and entity' => ['Hello <strong>:name</strong> &laquo;'],
    'less-than before a digit' => ['I <3 Laravel'],
    'complete tag with quoted greater-than' => ['<a title=">">x</a>'],
    'two spaces before equals' => ['<a title  ="x">y</a>'],
    'slash in an unquoted value' => ['<a title=a/b>y</a>'],
]);

it('stores a legitimate text value byte for byte', function (string $value): void {
    $repo = new DatabaseRepository(app(BaseTranslationSource::class));

    $created = $repo->create('app', 'plain_'.md5($value), LinguaType::text, 'en', $value);

    expect($created->text['en'])->toBe($value);
})->with('byte for byte values');

dataset('truncated tags', [
    'bare tag name after a letter' => ['a<b'],
    'bare tag name' => ['<img'],
    'bare tag name after text' => ['x <img'],
    'bare tag name then newline' => ["<img\n"],
    'attribute without close' => ['<img src=x'],
    'attribute without value' => ['<img src='],
    'open double quote' => ['<b x="'],
    'quoted greater-than' => ['<b x=">'],
    'open href' => ['<a href="https://a'],
    'complete tag then quoted greater-than' => ['<b>a</b><b x=">'],
    'quote right after the tag name' => ['<a "=">'],
    'quote in an attribute name' => ['<a b"=">'],
    'quote in an attribute name after an unquoted value' => ["<img src=x a'='>"],
    'double quote in an unquoted value' => ['<a title=x"y><img src=">'],
    'single quote in an unquoted value' => ["<a title=x'y><img src='>"],
    'quote in an unquoted value after equals' => ['<a title=b="c><img src="x>'],
    'quote in an unquoted value then complete tag' => ['<a href=x"y>z</a><img src=">'],
    'slash in an unquoted value' => ['<a title=c/d="><img alt=\'">'],
    'two spaces before equals' => ['<a title  = ="><img alt=\'">'],
]);

it('rejects a truncated tag through the repository', function (string $value): void {
    $repo = new DatabaseRepository(app(BaseTranslationSource::class));

    expect(fn () => $repo->create('app', 'cut_'.md5($value), LinguaType::text, 'en', $value))
        ->toThrow(UnsafeTranslationValue::class, $this->textMessage);
})->with('truncated tags');

it('rejects a truncated tag through assertAcceptable', function (string $value): void {
    expect(TranslationValuePolicy::assertAcceptable(LinguaType::text, 'app', 'k', $value))->toBe($this->textMessage);
})->with('truncated tags');

it('cannot store a Showing and to pair that breaks out of the raw pagination attribute', function (): void {
    $repo = new DatabaseRepository(app(BaseTranslationSource::class));

    expect(fn () => $repo->create('single', 'Showing', LinguaType::text, 'en', "<img src=x a'='>"))
        ->toThrow(UnsafeTranslationValue::class, $this->textMessage);

    expect(Translation::where('group', 'single')->where('key', 'Showing')->exists())->toBeFalse();
});

it('stores an html value sanitized and keeps the allowed markup', function (): void {
    $repo = new DatabaseRepository(app(BaseTranslationSource::class));

    $created = $repo->create('app', 'html_ok', LinguaType::html, 'en', '<p>hi</p><img src=x onerror=1>');

    expect($created->text['en'])->not->toContain('onerror')
        ->and($created->text['en'])->toContain('<p>hi</p>');
});

it('removes a placeholder href from an html value', function (): void {
    $repo = new DatabaseRepository(app(BaseTranslationSource::class));

    $created = $repo->create('app', 'html_href', LinguaType::html, 'en', '<a href=":url">x</a>');

    expect($created->text['en'])->not->toContain('href');
});

it('counts what the sanitizer removes', function (): void {
    expect(HtmlSanitizer::sanitizeReport('<p>hi</p>')[1])->toBe(0)
        ->and(HtmlSanitizer::sanitizeReport('<img src=x onerror=1>')[1])->toBe(1)
        ->and(HtmlSanitizer::sanitizeReport('<script>x</script>')[1])->toBe(1)
        ->and(HtmlSanitizer::sanitizeReport('<body onload=x>y')[1])->toBeGreaterThan(0);
});

it('reports a rejected import row as a row error and writes the other rows', function (): void {
    $repo = new DatabaseRepository(app(BaseTranslationSource::class));
    $registry = new FormatRegistry;
    $mapper = new RowMapper;
    $diffService = new ImportDiffService($repo, $registry, $mapper);
    $commitService = new ImportCommitService($repo, $registry, $mapper, $diffService);

    $headers = [
        TransferSchema::KEY,
        TransferSchema::TYPE,
        TransferSchema::sourceHeader('en'),
        TransferSchema::targetHeader('it'),
        TransferSchema::VENDOR,
    ];
    $row = fn (string $key, string $value): array => [
        TransferSchema::KEY => $key,
        TransferSchema::TYPE => 'text',
        TransferSchema::sourceHeader('en') => $value,
        TransferSchema::targetHeader('it') => $value,
        TransferSchema::VENDOR => '',
    ];

    $path = tempnam(sys_get_temp_dir(), 'lingua_reject_');
    (new CsvWriter)->write($path, $headers, [
        $row('app.bad_row', '<img src=x onerror=1>'),
        $row('app.good_row', 'I <3 Laravel'),
    ]);

    $diff = $diffService->diff($path, 'csv', 'it', false);
    $commitService->commit($path, 'csv', 'it', false);
    @unlink($path);

    expect($diff->errors)->toHaveCount(1)
        ->and(Translation::where('group', 'app')->where('key', 'bad_row')->exists())->toBeFalse()
        ->and(Translation::where('group', 'app')->where('key', 'good_row')->firstOrFail()->text['it'])->toBe('I <3 Laravel');
});

dataset('accepted markup', [
    'strong with placeholder' => ['Hello <strong>:name</strong>'],
    'self-closing br' => ['Line one<br/>two'],
    'spaced self-closing br' => ['Line<br />two'],
    'code' => ['Install <code>openspout/openspout</code>'],
    'href with entity' => ['<a href="https://laravel.com?a=1&amp;b=2">x</a>'],
    'href with bare ampersand' => ['<a href="/docs?a=1&b=2">x</a>'],
    'nested' => ['<p><em>a</em> <strong>b</strong></p>'],
]);

dataset('rejected markup', [
    'slash instead of space before attribute' => ['<img/onerror=alert(1) src=x>'],
    'slash before event handler' => ['<b/onmouseover=alert(1)>x</b>'],
    'slash before href' => ['<a/href="javascript:alert(1)">x</a>'],
    'colon entity' => ['<a href="javascript&colon;alert(1)">x</a>'],
    'numeric entity without semicolon' => ['<a href="javascript&#58alert(1)">x</a>'],
    'tab entity in scheme' => ['<a href="java&Tab;script:alert(1)">x</a>'],
    'orphan end tag' => ['</main>'],
    'orphan end tag after text' => ['x</div>'],
    'unclosed div' => ['<div>unclosed'],
    'unclosed anchor' => ['<a href="https://evil.example/">rest'],
    'mismatched end tag' => ['<b>x</i>'],
    'crossed tags' => ['<b><i>x</b></i>'],
    'trailing less-than' => ['Showing <'],
    'comment' => ['<!-- c -->'],
    'processing instruction' => ['<?php echo 1 ?>'],
    'duplicate href' => ['<a href="/a" href="javascript:1">x</a>'],
    'duplicate unquoted attribute' => ['<a title=x title=y>z</a>'],
    'style attribute' => ['<span style="x">y</span>'],
    'custom element' => ['<custom>x</custom>'],
]);

it('stores accepted markup byte for byte', function (string $value, string $type): void {
    $repo = new DatabaseRepository(app(BaseTranslationSource::class));

    $created = $repo->create('app', 'ok_'.$type.md5($value), LinguaType::from($type), 'en', $value);

    expect($created->text['en'])->toBe($value);
})->with('accepted markup')->with(['text', 'markdown']);

it('rejects rejected markup for text and markdown', function (string $value, string $type): void {
    $repo = new DatabaseRepository(app(BaseTranslationSource::class));

    expect(fn () => $repo->create('app', 'bad_'.$type.md5($value), LinguaType::from($type), 'en', $value))
        ->toThrow(UnsafeTranslationValue::class);

    expect(TranslationValuePolicy::assertAcceptable(LinguaType::from($type), 'app', 'k', $value))->not->toBeNull();
})->with('rejected markup')->with(['text', 'markdown']);

it('rejects markup with its own scanner without asking the sanitizer', function (string $value): void {
    expect(TranslationValuePolicy::scanRejects($value))->toBeTrue();
})->with('rejected markup');
