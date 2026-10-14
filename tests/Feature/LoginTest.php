<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        DB::purge('sqlite');

        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());

        $this->artisan('migrate', ['--force' => true])
            ->assertExitCode(0);
    }

    public function test_valid_credentials_sign_the_user_in()
    {
        $user = User::create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'correct-password',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_returns_an_error_without_retaining_password()
    {
        User::create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors([
            'email' => __('auth.failed'),
        ]);
        $response->assertSessionHas(
            '_old_input.email',
            'test@example.com'
        );
        $response->assertSessionMissing('_old_input.password');

        $this->assertGuest();
    }
    public function test_unknown_email_returns_the_same_generic_error()
{
    $response = $this->from('/login')->post('/login', [
        'email' => 'unknown@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertRedirect('/login');
    $response->assertSessionHasErrors([
        'email' => __('auth.failed'),
    ]);
    $response->assertSessionHas(
        '_old_input.email',
        'unknown@example.com'
    );
    $response->assertSessionMissing('_old_input.password');

    $this->assertGuest();
}

public function test_missing_fields_are_rejected()
{
    $response = $this->from('/login')->post('/login', []);

    $response->assertRedirect('/login');
    $response->assertSessionHasErrors(['email', 'password']);

    $this->assertGuest();
}

public function test_invalid_email_is_rejected_without_retaining_password()
{
    $response = $this->from('/login')->post('/login', [
        'email' => 'not-an-email',
        'password' => 'some-password',
    ]);

    $response->assertRedirect('/login');
    $response->assertSessionHasErrors(['email']);
    $response->assertSessionHas('_old_input.email', 'not-an-email');
    $response->assertSessionMissing('_old_input.password');

    $this->assertGuest();
}
}