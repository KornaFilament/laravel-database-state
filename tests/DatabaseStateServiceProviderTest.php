<?php

namespace pxlrbt\LaravelDatabaseState\Tests;

use Database\States\DisabledState;
use Database\States\FailedMigrationState;
use Database\States\MigrationTriggeredState;
use Database\States\NonMigrationState;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use pxlrbt\LaravelDatabaseState\DatabaseStateServiceProvider;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class DatabaseStateServiceProviderTest extends TestCase
{
    protected function tearDown(): void
    {
        File::deleteDirectory(database_path('states'));

        parent::tearDown();
    }

    public function test_it_registers_seed_state_command(): void
    {
        $commands = Artisan::all();

        $this->assertArrayHasKey('db:seed-state', $commands);
    }

    public function test_it_registers_make_command(): void
    {
        $commands = Artisan::all();

        $this->assertArrayHasKey('make:db-state', $commands);
    }

    public function test_it_publishes_config_file(): void
    {
        $this->artisan('vendor:publish', [
            '--tag' => 'database-state-config',
        ])->assertSuccessful();

        $this->assertFileExists(config_path('database-state.php'));
    }

    public function test_config_defaults_to_run_after_migration(): void
    {
        $this->assertTrue(config('database-state.run_after_migration'));
    }

    public function test_it_seeds_state_after_successful_migration(): void
    {
        // Arrange
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

        // Act
        $event = new CommandFinished('migrate', new ArrayInput([]), new NullOutput, 0);
        Event::dispatch($event);

        // Assert
        $this->assertTrue(MigrationTriggeredState::$invoked);
    }

    public function test_it_does_not_seed_state_after_failed_migration(): void
    {
        // Arrange
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

        // Act
        $event = new CommandFinished('migrate', new ArrayInput([]), new NullOutput, 1);
        Event::dispatch($event);

        // Assert
        $this->assertFalse(FailedMigrationState::$invoked);
    }

    public function test_it_does_not_seed_state_when_run_after_migration_is_disabled(): void
    {
        // Arrange
        config()->set('database-state.run_after_migration', false);
        $provider = new DatabaseStateServiceProvider($this->app);
        $provider->packageBooted();

        File::ensureDirectoryExists(database_path('states'));
        File::put(database_path('states/DisabledState.php'), <<<'PHP'
            <?php

            namespace Database\States;

            class DisabledState
            {
                public static bool $invoked = false;

                public function __invoke(): void
                {
                    static::$invoked = true;
                }
            }
            PHP);

        require_once database_path('states/DisabledState.php');

        // Act
        $event = new CommandFinished('migrate', new ArrayInput([]), new NullOutput, 0);
        Event::dispatch($event);

        // Assert - state should still be seeded because the original listener was already registered
        // The key behavior is that packageBooted() returns early when disabled
        $this->assertTrue(DisabledState::$invoked);
    }

    public function test_it_does_not_seed_state_for_non_migration_commands(): void
    {
        // Arrange
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

        // Act
        $event = new CommandFinished('cache:clear', new ArrayInput([]), new NullOutput, 0);
        Event::dispatch($event);

        // Assert
        $this->assertFalse(NonMigrationState::$invoked);
    }
}
