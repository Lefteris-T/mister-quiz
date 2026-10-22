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
            'GET /quiz/1abc' => ['GET', '/quiz/1abc'],
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
            '/quiz/1abc' => ['/quiz/1abc'],
            '/quiz/999999' => ['/quiz/999999'],
        ];
    }

    /**
     * @dataProvider idsWithText
     */
    public function test_an_id_with_text_never_reaches_the_database($id)
    {
        // MySQL reads "1abc" as 1 and SQLite does not, so check that no query is made at all.
        DB::enableQueryLog();

        $this->assertNull((new Quiz())->resolveRouteBinding($id));
        $this->assertSame([], DB::getQueryLog());
    }

    /**
     * @dataProvider idsWithText
     */
    public function test_an_id_with_text_does_not_open_the_quiz($id)
    {
        $user = User::factory()->create();
        $quiz = Quiz::create(['user_id' => $user->id, 'completed' => 1]);

        $this->assertSame(1, (int) $quiz->id);

        $this->actingAs($user);

        $this->get('/quiz/' . rawurlencode($id) . '/results')->assertStatus(404);
        $this->post('/quiz/' . rawurlencode($id))->assertStatus(404);
    }

    /**
     * @dataProvider idsWithDigitsOnly
     */
    public function test_an_id_made_of_digits_still_finds_the_quiz($id)
    {
        $user = User::factory()->create();
        $quiz = Quiz::create(['user_id' => $user->id, 'completed' => 1]);

        $this->assertSame(1, (int) $quiz->id);

        $this->actingAs($user)->get('/quiz/' . $id . '/results')->assertOk();
    }

    public function idsWithText(): array
    {
        return [
            '1abc' => ['1abc'],
            '1.0' => ['1.0'],
            '+1' => ['+1'],
            '1e0' => ['1e0'],
            'space before 1' => [' 1'],
            'space after 1' => ['1 '],
            '0x1' => ['0x1'],
            'c' => ['c'],
            '-1' => ['-1'],
            '1.5' => ['1.5'],
        ];
    }

    public function idsWithDigitsOnly(): array
    {
        return [
            '1' => ['1'],
            '01' => ['01'],
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
