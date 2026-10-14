<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RegisterTest extends TestCase
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

    public function test_duplicate_email_is_rejected_without_creating_another_user()
    {
        $this->post('/register', [
            'username' => 'firstuser',
            'email' => 'test@example.com',
            'password' => 'test-password',
            'password_confirmation' => 'test-password',
        ])->assertRedirect('/');

        $user = User::where('email', 'test@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, User::count());

        Auth::logout();
        $this->assertGuest();

        $response = $this->from('/register')->post('/register', [
            'username' => 'seconduser',
            'email' => 'test@example.com',
            'password' => 'another-password',
            'password_confirmation' => 'another-password',
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors([
            'email' => 'The email has already been taken.',
        ]);
        $response->assertSessionHas('_old_input.username', 'seconduser');
        $response->assertSessionHas('_old_input.email', 'test@example.com');
        $response->assertSessionMissing('_old_input.password');
        $response->assertSessionMissing('_old_input.password_confirmation');

        $this->assertSame(1, User::count());
        $this->assertSame('firstuser', $user->fresh()->username);
        $this->assertGuest();
    }
}