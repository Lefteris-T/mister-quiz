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

    public function test_registration_creates_and_signs_in_a_user()
    {
        $response = $this->post('/register', [
            'username' => 'newuser',
            'email' => 'new@example.com',
            'password' => 'correct-password',
            'password_confirmation' => 'correct-password',
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHasNoErrors();

        $this->assertSame(1, User::count());

        $user = User::where('email', 'new@example.com')->firstOrFail();

        $this->assertSame('newuser', $user->username);
        $this->assertTrue(
            \Illuminate\Support\Facades\Hash::check(
                'correct-password',
                $user->password
            )
        );

        $this->assertSame(0, (int) $user->xp);

        foreach (['art', 'history', 'geography', 'science', 'sports'] as $category) {
            $this->assertSame('0/0', $user->{$category}, $category);
        }

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_registration_is_rejected()
    {
        $valid = [
            'username' => 'newuser',
            'email' => 'new@example.com',
            'password' => 'correct-password',
            'password_confirmation' => 'correct-password',
        ];

        $cases = [
            'missing username' => ['username', ''],
            'missing email' => ['email', ''],
            'invalid email' => ['email', 'not-an-email'],
            'missing password' => ['password', ''],
            'missing confirmation' => ['password_confirmation', ''],
            'mismatched confirmation' => ['password_confirmation', 'different'],
            'overlong username' => ['username', str_repeat('a', 256)],
        ];

        foreach ($cases as $label => [$field, $value]) {
            $data = array_replace($valid, [$field => $value]);

            // The confirmed rule reports confirmation errors on password.
            $errorField = $field === 'password_confirmation'
                ? 'password'
                : $field;

            $response = $this->from('/register')->post('/register', $data);

            $response->assertRedirect('/register');
            $response->assertSessionHasErrors([$errorField]);

            if ($data['username'] !== '') {
                $response->assertSessionHas(
                    '_old_input.username',
                    $data['username']
                );
            }

            if ($data['email'] !== '') {
                $response->assertSessionHas(
                    '_old_input.email',
                    $data['email']
                );
            }

            $response->assertSessionMissing('_old_input.password');
            $response->assertSessionMissing('_old_input.password_confirmation');

            $this->assertSame(0, User::count(), $label);
            $this->assertGuest();
        }
    }
}
