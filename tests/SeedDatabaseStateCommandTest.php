<?php

namespace pxlrbt\LaravelDatabaseState\Tests;

use Database\States\AlphaState;
use Database\States\BetaState;
use Database\States\TestState;
use Illuminate\Support\Facades\File;

class SeedDatabaseStateCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        File::deleteDirectory(database_path('states'));

        parent::tearDown();
    }

    public function test_it_runs_all_state_classes(): void
    {
        // Arrange
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

        // Act
        $this->artisan('db:seed-state')
            ->assertSuccessful();

        // Assert
        $this->assertTrue(TestState::$invoked);
    }

    public function test_it_offers_to_create_states_directory_when_missing(): void
    {
        // Arrange
        File::deleteDirectory(database_path('states'));

        // Act & Assert
        $this->artisan('db:seed-state')
            ->expectsConfirmation("You don't have a `states` folder in your database folder. Do you want to create it?", 'yes')
            ->assertSuccessful();

        $this->assertDirectoryExists(database_path('states'));
    }

    public function test_it_does_not_create_directory_when_declined(): void
    {
        // Arrange
        File::deleteDirectory(database_path('states'));

        // Act & Assert
        $this->artisan('db:seed-state')
            ->expectsConfirmation("You don't have a `states` folder in your database folder. Do you want to create it?", 'no')
            ->assertSuccessful();

        $this->assertDirectoryDoesNotExist(database_path('states'));
    }

    public function test_it_succeeds_with_empty_states_directory(): void
    {
        // Arrange
        File::ensureDirectoryExists(database_path('states'));

        // Act & Assert
        $this->artisan('db:seed-state')
            ->assertSuccessful();
    }

    public function test_it_runs_multiple_state_classes_in_order(): void
    {
        // Arrange
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

        // Act
        $this->artisan('db:seed-state')
            ->assertSuccessful();

        // Assert
        $this->assertTrue(AlphaState::$invoked);
        $this->assertTrue(BetaState::$invoked);
    }
}
