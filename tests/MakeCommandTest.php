<?php

use Illuminate\Support\Facades\File;

afterEach(function () {
    File::deleteDirectory(database_path('States'));
});

it('creates a state file', function () {
    $this->artisan('make:db-state', ['name' => 'UserRoles'])
        ->assertSuccessful();

    $path = database_path('States/UserRoles.php');

    expect($path)->toBeFile();
    expect(File::get($path))
        ->toContain('class UserRoles')
        ->toContain('namespace Database\States;')
        ->toContain('public function __invoke()');
});

it('does not overwrite existing file without force', function () {
    File::ensureDirectoryExists(database_path('States'));
    File::put(database_path('States/UserRoles.php'), 'original content');

    $this->artisan('make:db-state', ['name' => 'UserRoles'])
        ->assertSuccessful();

    expect(File::get(database_path('States/UserRoles.php')))->toBe('original content');
});

it('overwrites existing file with force option', function () {
    File::ensureDirectoryExists(database_path('States'));
    File::put(database_path('States/UserRoles.php'), 'original content');

    $this->artisan('make:db-state', ['name' => 'UserRoles', '--force' => true])
        ->assertSuccessful();

    expect(File::get(database_path('States/UserRoles.php')))->toContain('class UserRoles');
});

it('converts name to studly case', function () {
    $this->artisan('make:db-state', ['name' => 'user_roles'])
        ->assertSuccessful();

    $path = database_path('States/UserRoles.php');

    expect($path)->toBeFile();
    expect(File::get($path))->toContain('class UserRoles');
});

it('rejects reserved class names', function () {
    $this->artisan('make:db-state', ['name' => 'class'])
        ->assertSuccessful();

    expect(database_path('States/Class.php'))->not->toBeFile();
});

it('rejects invalid class names', function () {
    $this->artisan('make:db-state', ['name' => '123invalid'])
        ->assertSuccessful();

    expect(database_path('States/123invalid.php'))->not->toBeFile();
});
