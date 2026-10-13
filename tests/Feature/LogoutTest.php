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
}