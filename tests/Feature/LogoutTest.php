<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $user = new User();

        $user->forceFill([
            'id' => 1,
            'username' => 'testuser',
            'email' => 'test@example.com',
        ]);

        $this->actingAs($user);
    }

    public function test_get_logout_is_rejected_and_keeps_user_authenticated()
    {
        $response = $this->get('/logout');

        $response->assertStatus(405);
        $this->assertAuthenticated();
    }

    public function test_post_logout_signs_user_out()
    {
        $response = $this->post('/logout');

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_post_logout_clears_the_session_and_changes_the_csrf_token()
    {
        $this->withSession(['quiz_in_progress' => 'some-data']);
        $tokenBefore = session()->token();

        $this->post('/logout');

        $this->assertNotSame($tokenBefore, session()->token());
        $this->assertNull(session('quiz_in_progress'));
    }
}