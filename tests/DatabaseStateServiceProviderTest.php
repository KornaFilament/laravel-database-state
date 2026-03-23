<?php

use Database\States\FailedMigrationState;
use Database\States\MigrationTriggeredState;
use Database\States\NonMigrationState;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

afterEach(function () {
    File::deleteDirectory(database_path('states'));
});

it('registers the seed state command', function () {
    expect(Artisan::all())->toHaveKey('db:seed-state');
});

it('registers the make command', function () {
    expect(Artisan::all())->toHaveKey('make:db-state');
});

it('publishes the config file', function () {
    $this->artisan('vendor:publish', [
        '--tag' => 'database-state-config',
    ])->assertSuccessful();

    expect(config_path('database-state.php'))->toBeFile();
});

it('defaults run_after_migration to true', function () {
    expect(config('database-state.run_after_migration'))->toBeTrue();
});

it('seeds state after successful migration', function () {
    File::ensureDirectoryExists(database_path('states'));
    File::put(database_path('states/MigrationTriggeredState.php'), <<<'PHP'
        <?php

        namespace Database\States;

        class MigrationTriggeredState
        {
            public static bool $invoked = false;

            public function __invoke(): void
            {
                static::$invoked = true;
            }
        }
        PHP);

    require_once database_path('states/MigrationTriggeredState.php');

    Event::dispatch(new CommandFinished('migrate', new ArrayInput([]), new NullOutput, 0));

    expect(MigrationTriggeredState::$invoked)->toBeTrue();
});

it('does not seed state after failed migration', function () {
    File::ensureDirectoryExists(database_path('states'));
    File::put(database_path('states/FailedMigrationState.php'), <<<'PHP'
        <?php

        namespace Database\States;

        class FailedMigrationState
        {
            public static bool $invoked = false;

            public function __invoke(): void
            {
                static::$invoked = true;
            }
        }
        PHP);

    require_once database_path('states/FailedMigrationState.php');

    Event::dispatch(new CommandFinished('migrate', new ArrayInput([]), new NullOutput, 1));

    expect(FailedMigrationState::$invoked)->toBeFalse();
});

it('does not seed state for non-migration commands', function () {
    File::ensureDirectoryExists(database_path('states'));
    File::put(database_path('states/NonMigrationState.php'), <<<'PHP'
        <?php

        namespace Database\States;

        class NonMigrationState
        {
            public static bool $invoked = false;

            public function __invoke(): void
            {
                static::$invoked = true;
            }
        }
        PHP);

    require_once database_path('states/NonMigrationState.php');

    Event::dispatch(new CommandFinished('cache:clear', new ArrayInput([]), new NullOutput, 0));

    expect(NonMigrationState::$invoked)->toBeFalse();
});
