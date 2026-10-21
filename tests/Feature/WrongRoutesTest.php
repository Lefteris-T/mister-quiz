<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WrongRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_addresses_return_404()
    {
        foreach (['/nonexistent', '/profile/2', '/leaderboard/1', '/quiz/1/answers', '/Quiz'] as $url) {
            $this->get($url)->assertStatus(404);
        }
    }

    public function test_wrong_methods_return_405()
    {
        foreach (['/quiz/c', '/quiz/1'] as $url) {
            $this->get($url)->assertStatus(405);
        }

        $this->put('/quiz/1')->assertStatus(405);
        $this->delete('/quiz/1')->assertStatus(405);

        foreach (['/', '/quiz', '/profile', '/leaderboard'] as $url) {
            $this->post($url)->assertStatus(405);
        }
    }

    public function test_bad_quiz_ids_return_404_without_creating_a_quiz()
    {
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());

        $this->actingAs(User::factory()->create());

        foreach (['/quiz/c', '/quiz/0', '/quiz/-1', '/quiz/1.5', '/quiz/999999'] as $url) {
            $this->post($url)->assertStatus(404);
            $this->assertSame(0, Quiz::count());
        }
    }

    public function test_auth_posts_without_a_form_token_return_419()
    {
        // Laravel skips CSRF verification in the testing environment.
        $this->app['env'] = 'production';

        foreach (['/login', '/register', '/logout'] as $url) {
            $this->post($url)->assertStatus(419);
        }
    }
}
