<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class HomeTest extends TestCase
{
    private function signedInUser(): User
    {
        $user = new User();

        $user->forceFill([
            'id' => 1,
            'username' => 'testuser',
            'email' => 'test@example.com',
        ]);

        return $user;
    }

    public function test_home_shows_the_guest_buttons_to_a_guest()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Login');
        $response->assertSee('Leaderboard');
        $response->assertSee('Start Quiz');
        $response->assertDontSee('Logout');
    }

    public function test_home_shows_the_username_and_logout_to_a_signed_in_user()
    {
        $response = $this->actingAs($this->signedInUser())->get('/');

        $response->assertStatus(200);
        $response->assertSee('testuser');
        $response->assertSee('Logout');
        $response->assertDontSee('>Login<', false);
    }

    public function test_the_profile_leaderboard_and_quiz_routes_have_their_names()
    {
        $this->assertSame(url('/profile'), route('profile'));
        $this->assertSame(url('/leaderboard'), route('leaderboard'));
        $this->assertSame(url('/quiz'), route('quiz'));
    }

    public function test_profile_sends_a_guest_to_the_login_page()
    {
        $this->get('/profile')->assertRedirect('/login');
    }

    public function test_leaderboard_is_open_to_everyone()
    {
        $this->get('/leaderboard')->assertStatus(200);
    }
}
