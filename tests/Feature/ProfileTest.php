<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    private function signedInUser(array $attributes = []): User
    {
        $user = new User();

        $user->forceFill(array_merge([
            'id' => 1,
            'username' => 'testuser',
            'email' => 'test@example.com',
            'xp' => 0,
            'art' => '0/0',
            'geography' => '0/0',
            'history' => '0/0',
            'science' => '0/0',
            'sports' => '0/0',
        ], $attributes));

        return $user;
    }

    public function test_a_guest_is_sent_to_the_login_page()
    {
        $this->get('/profile')->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_the_profile_shows_the_username_email_xp_and_rank()
    {
        $user = $this->signedInUser(['xp' => 1500]);

        $this->actingAs($user)->get('/profile')
            ->assertOk()
            ->assertSee('testuser')
            ->assertSee('test@example.com')
            ->assertSee('1500 XP')
            ->assertSee('Average Quizer');
    }

    public function test_the_profile_shows_right_answered_and_percentage_for_every_category()
    {
        $user = $this->signedInUser([
            'art' => '3/4',
            'geography' => '0/5',
            'history' => '1/8',
            'science' => '2/3',
            'sports' => '8/8',
        ]);

        $response = $this->actingAs($user)->get('/profile')->assertOk();

        $response->assertSeeInOrder(['<td>Art</td>', '<td>3</td>', '<td>4</td>', '<td>75%</td>'], false);
        $response->assertSeeInOrder(['<td>Geography</td>', '<td>0</td>', '<td>5</td>', '<td>0%</td>'], false);
        $response->assertSeeInOrder(['<td>History</td>', '<td>1</td>', '<td>8</td>', '<td>13%</td>'], false);
        $response->assertSeeInOrder(['<td>Science</td>', '<td>2</td>', '<td>3</td>', '<td>67%</td>'], false);
        $response->assertSeeInOrder(['<td>Sports</td>', '<td>8</td>', '<td>8</td>', '<td>100%</td>'], false);
    }

    public function test_a_new_player_sees_zeros_and_the_first_rank_without_a_crash()
    {
        $this->actingAs($this->signedInUser())->get('/profile')
            ->assertOk()
            ->assertSee('0 XP')
            ->assertSee('Quiz Aprentice')
            ->assertSeeInOrder(['<td>Art</td>', '<td>0</td>', '<td>0</td>', '<td>0%</td>'], false);
    }

    public function test_there_is_no_profile_of_another_user()
    {
        $this->actingAs($this->signedInUser())->get('/profile/2')->assertNotFound();
    }

    public function test_a_username_with_html_cannot_change_the_page()
    {
        $user = $this->signedInUser(['username' => '<b>boss</b>']);

        $this->actingAs($user)->get('/profile')
            ->assertOk()
            ->assertDontSee('<b>boss</b>', false)
            ->assertSee('&lt;b&gt;boss&lt;/b&gt;', false);
    }
}
