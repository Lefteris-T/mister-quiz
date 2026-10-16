<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GuestOnlyRoutesTest extends TestCase
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

    private function signedInUser(): User
    {
        $user = User::create([
            'username' => 'player',
            'email' => 'player@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        $this->actingAs($user);

        return $user;
    }

    public function test_a_signed_in_user_is_sent_home_from_the_login_and_register_pages()
    {
        $this->signedInUser();

        $this->get('/login')->assertRedirect('/');
        $this->get('/register')->assertRedirect('/');
    }

    public function test_a_signed_in_user_cannot_log_in_again_or_register_another_account()
    {
        $user = $this->signedInUser();

        $this->post('/login', [
            'email' => 'other@example.com',
            'password' => 'other-password',
        ])->assertRedirect('/');

        $this->post('/register', [
            'username' => 'second',
            'email' => 'second@example.com',
            'password' => 'second-password',
            'password_confirmation' => 'second-password',
        ])->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, User::count());
    }

    public function test_a_guest_still_sees_the_login_and_register_forms()
    {
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
    }
}
