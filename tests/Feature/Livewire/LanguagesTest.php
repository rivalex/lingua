<?php

use Livewire\Livewire;
use Rivalex\Lingua\Livewire\Language\Create;
use Rivalex\Lingua\Livewire\Language\Delete;
use Rivalex\Lingua\Livewire\Language\SetDefault;
use Rivalex\Lingua\Livewire\Language\Sort;
use Rivalex\Lingua\Livewire\Language\Table;
use Rivalex\Lingua\Livewire\Languages;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\Translation;

it('can reach the LANGUAGES component page', function () {
    Livewire::test(Languages::class)
            ->assertStatus(200);
});

it('can reach the LANGUAGE SORT component', function () {
    Livewire::test(Sort::class)
        ->assertStatus(200);
});

it('can reach the LANGUAGE TABLE component', function () {
    Livewire::test(Table::class)
            ->assertStatus(200);
});

it('can reach the ADD LANGUAGE component', function () {
    Livewire::test(Create::class)
            ->assertStatus(200);
});

it('can reach the REMOVE LANGUAGE component', function () {
    $language = Language::first();
    Livewire::test(Delete::class, ['language' => $language])
            ->assertStatus(200);
});

it('can reach the SET DEFAULT component', function () {
    $language = Language::first();
    Livewire::test(SetDefault::class, ['language' => $language])
            ->assertStatus(200);
});

it('can Sync translations to LOCAL FILES', function () {
    Livewire::test(Languages::class)
        ->assertStatus(200)
        ->call('syncToLocal')
        ->assertHasNoErrors()
        ->assertDispatched('synced_local')
        ->assertDispatched('refreshLanguages');
});

it('can catch Sync to LOCAL FILES ERRORS', function () {
    $this->mock(Translation::class, function ($mock) {
        $mock->shouldReceive('syncToLocal')
               ->once()
               ->andThrow(new Exception('error'));
    });

    Livewire::test(Languages::class)
            ->call('syncToLocal')
            ->assertHasErrors('syncToLocal')
            ->assertDispatched('synced_local_fail');
});

it('can Sync translations to DATABASE', function () {
    Livewire::test(Languages::class)
            ->assertStatus(200)
            ->call('syncToDatabase')
            ->assertHasNoErrors()
            ->assertDispatched('synced_database')
            ->assertDispatched('refreshLanguages');
});

it('can catch Sync to DATABASE ERRORS', function () {
    $this->mock(Translation::class, function ($mock) {
        $mock->shouldReceive('syncToDatabase')
               ->once()
               ->andThrow(new Exception('error'));
    });

    Livewire::test(Languages::class)
            ->call('syncToDatabase')
            ->assertHasErrors('syncToDatabase')
            ->assertDispatched('synced_database_fail');
});

it('can Update translations from LaravelLang', function () {
    Livewire::test(Languages::class)
            ->assertStatus(200)
            ->call('updateLanguages')
            ->assertHasNoErrors()
            ->assertDispatched('lang_updated')
            ->assertDispatched('refreshLanguages');
});

it('can catch Update translations ERRORS', function () {
    $this->mock(Translation::class, function ($mock) {
        $mock->shouldReceive('syncToDatabase')
             ->once()
             ->andThrow(new Exception('error'));
    });

    Livewire::test(Languages::class)
            ->call('updateLanguages')
            ->assertHasErrors('updateLanguages')
            ->assertDispatched('lang_updated_fail');
});
