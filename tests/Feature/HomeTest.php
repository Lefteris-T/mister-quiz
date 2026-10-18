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

    public function test_home_shows_the_three_guest_buttons_and_hides_profile_and_logout()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('href="' . route('login') . '">Login</a>', false);
        $response->assertSee('href="' . route('leaderboard') . '">Leaderboard</a>', false);
        $response->assertSee('href="' . route('quiz') . '">Start Quiz</a>', false);
        $response->assertDontSee('Profile');
        $response->assertDontSee('Logout');
    }

    public function test_home_shows_the_four_buttons_to_a_signed_in_user_and_hides_login()
    {
        $response = $this->actingAs($this->signedInUser())->get('/');

        $response->assertStatus(200);
        $response->assertSee('href="' . route('profile') . '">Profile</a>', false);
        $response->assertSee('href="' . route('leaderboard') . '">Leaderboard</a>', false);
        $response->assertSee('href="' . route('quiz') . '">Start Quiz</a>', false);
        $response->assertSee('>Logout</button>', false);
        $response->assertDontSee('>Login<', false);
        $response->assertDontSee('testuser');
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
}
