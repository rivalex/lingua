<?php

use Illuminate\Contracts\Console\Kernel;
use Livewire\Livewire;
use Rivalex\Lingua\Livewire\Language\Create;
use Rivalex\Lingua\Livewire\Language\Delete;
use Rivalex\Lingua\Livewire\Language\SetDefault;
use Rivalex\Lingua\Livewire\Language\Sort;
use Rivalex\Lingua\Livewire\Language\Table;
use Rivalex\Lingua\Livewire\Languages;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\Translation;

it('can reach the `LANGUAGES` component page', function () {
    Livewire::test(Languages::class)
        ->assertStatus(200);
});

it('can reach the `LANGUAGE SORT` component', function () {
    Livewire::test(Sort::class)
        ->assertStatus(200);
});

it('can reach the `LANGUAGE TABLE` component', function () {
    Livewire::test(Table::class)
        ->assertStatus(200);
});

it('can reach the `ADD LANGUAGE` component', function () {
    Livewire::test(Create::class)
        ->assertStatus(200);
});

it('can reach the `REMOVE LANGUAGE` component', function () {
    $language = Language::first();
    Livewire::test(Delete::class, ['language' => $language])
        ->assertStatus(200);
});

it('can reach the `SET DEFAULT` component', function () {
    $language = Language::first();
    Livewire::test(SetDefault::class, ['language' => $language])
        ->assertStatus(200);
});

it('can `Sync` translations to `LOCAL FILES`', function () {
    Livewire::test(Languages::class)
        ->assertStatus(200)
        ->call('syncToLocal')
        ->assertHasNoErrors()
        ->assertDispatched('synced_local')
        ->assertDispatched('refreshLanguages');
});

it('catch `Sync local files ERRORS` for `Translation::syncToLocal()`', function () {
    $this->mock(Translation::class, function ($mock) {
        $mock->shouldReceive('syncToLocal')
            ->once()
            ->andThrow(new Exception('Error syncing translations to local files.'));
    });

    Livewire::test(Languages::class)
        ->call('syncToLocal')
        ->assertHasErrors(['syncToLocalError'])
        ->assertDispatched('synced_local_fail');
});

it('catch `Sync local files ERRORS` for `Artisan::call(\'optimize:clear\')`', function () {
    $this->mock(Translation::class, function ($mock) {
        $mock->shouldReceive('syncToLocal')
            ->once()
            ->andReturnNull();
    });

    $originalKernel = app(Kernel::class);
    Artisan::swap(
        Mockery::mock(Kernel::class, function ($mock) {
            $mock->shouldReceive('call')
                ->once()
                ->with('optimize:clear')
                ->andThrow(new Exception('Artisan command failed.'));
        })
    );

    try {
        Livewire::test(Languages::class)
            ->call('syncToLocal')
            ->assertHasErrors(['syncToLocalError'])
            ->assertDispatched('synced_local_fail');
    } finally {
        Artisan::swap($originalKernel);
    }
});

it('can `Sync` translations to `DATABASE`', function () {
    Livewire::test(Languages::class)
        ->assertStatus(200)
        ->call('syncToDatabase')
        ->assertHasNoErrors()
        ->assertDispatched('synced_database')
        ->assertDispatched('refreshLanguages');
});

it('catch `Sync database ERRORS` for `Artisan::call(\'optimize:clear\')`', function () {
    $this->mock(Translation::class, function ($mock) {
        $mock->shouldReceive('syncToDatabase')
            ->once()
            ->andReturnNull();
    });

    $originalKernel = app(Kernel::class);
    Artisan::swap(
        Mockery::mock(Kernel::class, function ($mock) {
            $mock->shouldReceive('call')
                ->once()
                ->with('optimize:clear')
                ->andThrow(new Exception('Artisan command failed.'));
        })
    );

    try {
        Livewire::test(Languages::class)
            ->call('syncToDatabase')
            ->assertHasErrors(['syncToDatabaseError'])
            ->assertDispatched('synced_database_fail');
    } finally {
        Artisan::swap($originalKernel);
    }
});

it('catch `Sync database ERRORS` for `Translation::syncToDatabase()`', function () {
    $this->mock(Translation::class, function ($mock) {
        $mock->shouldReceive('syncToDatabase')
            ->once()
            ->andThrow(new Exception('Error syncing translations to database.'));
    });

    Livewire::test(Languages::class)
        ->call('syncToDatabase')
        ->assertHasErrors(['syncToDatabaseError'])
        ->assertDispatched('synced_database_fail');
});

it('can `Update` translations from `Laravel Lang`', function () {
    Livewire::test(Languages::class)
        ->assertStatus(200)
        ->call('updateLanguages')
        ->assertHasNoErrors()
        ->assertDispatched('lang_updated')
        ->assertDispatched('refreshLanguages');
});

it('catch `Update translations ERRORS` for `Artisan::call(\'lang:update\')`', function () {
    $originalKernel = app(Kernel::class);
    Artisan::swap(
        Mockery::mock(Kernel::class, function ($mock) {
            $mock->shouldReceive('call')
                ->once()
                ->with('lang:update')
                ->andThrow(new Exception('Artisan command failed.'));
        })
    );

    try {
        Livewire::test(Languages::class)
            ->call('updateLanguages')
            ->assertHasErrors(['updateLanguagesError'])
            ->assertDispatched('lang_updated_fail');
    } finally {
        Artisan::swap($originalKernel);
    }
});

it('catch `Update translations ERRORS` for `Translation::syncToDatabase()`', function () {
    $this->mock(Translation::class, function ($mock) {
        $mock->shouldReceive('syncToDatabase')
            ->once()
            ->andThrow(new Exception('Error syncing translations to database.'));
    });

    Livewire::test(Languages::class)
        ->call('updateLanguages')
        ->assertHasErrors(['updateLanguagesError'])
        ->assertDispatched('lang_updated_fail');
});

it('catch `Update translations ERRORS` for `Artisan::call(\'optimize:clear\')`', function () {
    $this->mock(Translation::class, function ($mock) {
        $mock->shouldReceive('syncToDatabase')
            ->once()
            ->andReturnNull();
    });

    $originalKernel = app(Kernel::class);
    Artisan::swap(
        Mockery::mock(Kernel::class, function ($mock) {
            $mock->shouldReceive('call')
                ->once()
                ->with('lang:update')
                ->andReturnNull();
            $mock->shouldReceive('call')
                ->once()
                ->with('optimize:clear')
                ->andThrow(new Exception('Artisan command failed.'));
        })
    );

    try {
        Livewire::test(Languages::class)
            ->call('updateLanguages')
            ->assertHasErrors(['updateLanguagesError'])
            ->assertDispatched('lang_updated_fail');
    } finally {
        Artisan::swap($originalKernel);
    }
});
