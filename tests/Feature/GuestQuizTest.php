<?php

namespace Tests\Feature;

use Tests\TestCase;

class GuestQuizTest extends TestCase
{
    public function test_the_start_quiz_button_leads_to_the_quiz_route()
    {
        $this->get('/')->assertSee('href="' . route('quiz') . '"', false);
    }

    public function test_a_guest_opening_the_quiz_is_redirected_to_the_login_page()
    {
        $this->get('/quiz')->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_the_guest_ends_up_on_the_login_page_and_sees_no_quiz()
    {
        $this->followingRedirects()
            ->get('/quiz')
            ->assertOk()
            ->assertSee('Login form')
            ->assertDontSee('Undefined variable');
    }
}
