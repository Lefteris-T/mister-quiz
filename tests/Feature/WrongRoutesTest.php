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

    /**
     * @dataProvider unknownAddresses
     */
    public function test_unknown_addresses_return_404($url)
    {
        $this->get($url)->assertStatus(404);
    }

    public function unknownAddresses(): array
    {
        return [
            '/nonexistent' => ['/nonexistent'],
            '/profile/2' => ['/profile/2'],
            '/leaderboard/1' => ['/leaderboard/1'],
            '/quiz/1/answers' => ['/quiz/1/answers'],
            '/Quiz' => ['/Quiz'],
        ];
    }

    /**
     * @dataProvider wrongMethods
     */
    public function test_wrong_methods_return_405($method, $url)
    {
        $this->call($method, $url)->assertStatus(405);
    }

    public function wrongMethods(): array
    {
        return [
            'GET /quiz/c' => ['GET', '/quiz/c'],
            'GET /quiz/1' => ['GET', '/quiz/1'],
            'PUT /quiz/1' => ['PUT', '/quiz/1'],
            'DELETE /quiz/1' => ['DELETE', '/quiz/1'],
            'POST /' => ['POST', '/'],
            'POST /quiz' => ['POST', '/quiz'],
            'POST /profile' => ['POST', '/profile'],
            'POST /leaderboard' => ['POST', '/leaderboard'],
        ];
    }

    /**
     * @dataProvider badQuizIds
     */
    public function test_bad_quiz_ids_return_404_without_creating_a_quiz($url)
    {
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());

        $this->actingAs(User::factory()->create());

        $this->post($url)->assertStatus(404);
        $this->assertSame(0, Quiz::count());
    }

    public function badQuizIds(): array
    {
        return [
            '/quiz/c' => ['/quiz/c'],
            '/quiz/0' => ['/quiz/0'],
            '/quiz/-1' => ['/quiz/-1'],
            '/quiz/1.5' => ['/quiz/1.5'],
            '/quiz/999999' => ['/quiz/999999'],
        ];
    }

    /**
     * @dataProvider authPosts
     */
    public function test_auth_posts_without_a_form_token_return_419($url)
    {
        // Laravel skips CSRF verification in the testing environment.
        $this->app['env'] = 'production';

        $this->post($url)->assertStatus(419);
    }

    public function authPosts(): array
    {
        return [
            '/login' => ['/login'],
            '/register' => ['/register'],
            '/logout' => ['/logout'],
        ];
    }
}
