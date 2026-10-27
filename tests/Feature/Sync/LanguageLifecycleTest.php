<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Rivalex\Lingua\Database\Seeders\LinguaSeeder;
use Rivalex\Lingua\Facades\Lingua;
use Rivalex\Lingua\Livewire\Language\Delete;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\LinguaSetting;
use Rivalex\Lingua\Models\Translation;

beforeEach(function () {
    $this->lifecycleDir = sys_get_temp_dir().'/lingua_lifecycle_'.str_replace('.', '_', uniqid('', true));
    $this->lifecycleBundle = $this->lifecycleDir.'_bundle';
    File::ensureDirectoryExists($this->lifecycleDir.'/en');
    File::ensureDirectoryExists($this->lifecycleDir.'/it');
    File::ensureDirectoryExists($this->lifecycleBundle);
    file_put_contents($this->lifecycleDir.'/en/ui.php', '<?php return ["hello" => "Hello"];');
    file_put_contents($this->lifecycleDir.'/it/ui.php', '<?php return ["hello" => "Ciao"];');
    config([
        'lingua.lang_dir' => $this->lifecycleDir,
        'lingua.base_translations_path' => $this->lifecycleBundle,
    ]);
    Translation::query()->delete();
});

afterEach(function () {
    File::deleteDirectory($this->lifecycleDir);
    File::deleteDirectory($this->lifecycleBundle);
});

it('creates a language found in the lang files on sync', function () {
    Translation::syncToDatabase();

    expect(Language::where('code', 'it')->exists())->toBeTrue();
});

it('does not recreate a removed language from the lang files', function () {
    Translation::syncToDatabase();

    Lingua::removeLanguage('it');
    Translation::syncToDatabase();

    expect(Language::where('code', 'it')->exists())->toBeFalse()
        ->and(LinguaSetting::removedLocales())->toBe(['it']);
});

it('recreates a removed language when it is added again and clears the record', function () {
    Translation::syncToDatabase();
    Lingua::removeLanguage('it');

    Lingua::addLanguage('it');

    expect(Language::where('code', 'it')->exists())->toBeTrue()
        ->and(LinguaSetting::removedLocales())->toBe([]);

    Translation::syncToDatabase();

    expect(Language::where('code', 'it')->exists())->toBeTrue();
});

it('removes a language with lingua:remove in file mode without the language_lines table', function () {
    config(['lingua.storage.driver' => 'file']);
    Language::factory()->create(['code' => 'it', 'regional' => 'it_IT', 'is_default' => false]);
    Schema::drop('language_lines');

    $this->artisan('lingua:remove', ['locale' => 'it'])->assertExitCode(0);

    expect(Language::where('code', 'it')->exists())->toBeFalse();
});

it('fails lingua:remove for the default language', function () {
    $this->artisan('lingua:remove', ['locale' => Language::default()->code])->assertExitCode(1);
});

it('keeps exactly one default language when the seeder runs after the default changed', function () {
    $it = Language::factory()->create(['code' => 'it', 'regional' => 'it_IT', 'is_default' => false]);
    Language::setDefault($it);

    $this->seed(LinguaSeeder::class);

    expect(Language::where('is_default', true)->count())->toBe(1)
        ->and(Language::where('is_default', true)->first()->code)->toBe('it');
});

it('dispatches language_delete_fail when the delete component fails', function () {
    $it = Language::factory()->create(['code' => 'it', 'regional' => 'it_IT', 'is_default' => false]);
    $this->mock(Language::class, function ($mock) {
        $mock->shouldReceive('reorderLanguages')->once()->andThrow(new Exception('Error reordering languages.'));
    });

    $component = Livewire::test(Delete::class, ['language' => $it]);
    $component
        ->set('control', $component->get('confirm'))
        ->call('deleteLanguage')
        ->assertDispatched('language_delete_fail');
});
