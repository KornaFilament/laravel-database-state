<?php

namespace pxlrbt\LaravelDatabaseState\Tests;

use Illuminate\Support\Facades\File;

class MakeCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        File::deleteDirectory(database_path('States'));

        parent::tearDown();
    }

    public function test_it_creates_a_state_file(): void
    {
        $this->artisan('make:db-state', ['name' => 'UserRoles'])
            ->assertSuccessful();

        $path = database_path('States/UserRoles.php');

        $this->assertFileExists($path);
        $this->assertStringContainsString('class UserRoles', File::get($path));
        $this->assertStringContainsString('namespace Database\States;', File::get($path));
        $this->assertStringContainsString('public function __invoke()', File::get($path));
    }

    public function test_it_does_not_overwrite_existing_file_without_force(): void
    {
        // Arrange
        File::ensureDirectoryExists(database_path('States'));
        File::put(database_path('States/UserRoles.php'), 'original content');

        // Act
        $this->artisan('make:db-state', ['name' => 'UserRoles'])
            ->assertSuccessful();

        // Assert
        $this->assertEquals('original content', File::get(database_path('States/UserRoles.php')));
    }

    public function test_it_overwrites_existing_file_with_force_option(): void
    {
        // Arrange
        File::ensureDirectoryExists(database_path('States'));
        File::put(database_path('States/UserRoles.php'), 'original content');

        // Act
        $this->artisan('make:db-state', ['name' => 'UserRoles', '--force' => true])
            ->assertSuccessful();

        // Assert
        $this->assertStringContainsString('class UserRoles', File::get(database_path('States/UserRoles.php')));
    }

    public function test_it_converts_name_to_studly_case(): void
    {
        $this->artisan('make:db-state', ['name' => 'user_roles'])
            ->assertSuccessful();

        $this->assertFileExists(database_path('States/UserRoles.php'));
        $this->assertStringContainsString('class UserRoles', File::get(database_path('States/UserRoles.php')));
    }

    public function test_it_rejects_reserved_class_names(): void
    {
        $this->artisan('make:db-state', ['name' => 'class'])
            ->assertSuccessful();

        $this->assertFileDoesNotExist(database_path('States/Class.php'));
    }

    public function test_it_rejects_invalid_class_names(): void
    {
        $this->artisan('make:db-state', ['name' => '123invalid'])
            ->assertSuccessful();

        $this->assertFileDoesNotExist(database_path('States/123invalid.php'));
    }
}
