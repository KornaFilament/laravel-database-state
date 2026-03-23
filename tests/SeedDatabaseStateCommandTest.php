<?php

use Database\States\AlphaState;
use Database\States\BetaState;
use Database\States\TestState;
use Illuminate\Support\Facades\File;

afterEach(function () {
    File::deleteDirectory(database_path('states'));
});

it('runs all state classes', function () {
    File::ensureDirectoryExists(database_path('states'));
    File::put(database_path('states/TestState.php'), <<<'PHP'
        <?php

        namespace Database\States;

        class TestState
        {
            public static bool $invoked = false;

            public function __invoke(): void
            {
                static::$invoked = true;
            }
        }
        PHP);

    require_once database_path('states/TestState.php');

    $this->artisan('db:seed-state')
        ->assertSuccessful();

    expect(TestState::$invoked)->toBeTrue();
});

it('offers to create states directory when missing', function () {
    File::deleteDirectory(database_path('states'));

    $this->artisan('db:seed-state')
        ->expectsConfirmation("You don't have a `states` folder in your database folder. Do you want to create it?", 'yes')
        ->assertSuccessful();

    expect(database_path('states'))->toBeDirectory();
});

it('does not create directory when declined', function () {
    File::deleteDirectory(database_path('states'));

    $this->artisan('db:seed-state')
        ->expectsConfirmation("You don't have a `states` folder in your database folder. Do you want to create it?", 'no')
        ->assertSuccessful();

    expect(database_path('states'))->not->toBeDirectory();
});

it('succeeds with empty states directory', function () {
    File::ensureDirectoryExists(database_path('states'));

    $this->artisan('db:seed-state')
        ->assertSuccessful();
});

it('runs multiple state classes', function () {
    File::ensureDirectoryExists(database_path('states'));

    File::put(database_path('states/AlphaState.php'), <<<'PHP'
        <?php

        namespace Database\States;

        class AlphaState
        {
            public static bool $invoked = false;

            public function __invoke(): void
            {
                static::$invoked = true;
            }
        }
        PHP);

    File::put(database_path('states/BetaState.php'), <<<'PHP'
        <?php

        namespace Database\States;

        class BetaState
        {
            public static bool $invoked = false;

            public function __invoke(): void
            {
                static::$invoked = true;
            }
        }
        PHP);

    require_once database_path('states/AlphaState.php');
    require_once database_path('states/BetaState.php');

    $this->artisan('db:seed-state')
        ->assertSuccessful();

    expect(AlphaState::$invoked)->toBeTrue();
    expect(BetaState::$invoked)->toBeTrue();
});
