<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LeaderboardTest extends TestCase
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

    private function player(string $username, int $xp, array $attributes = []): User
    {
        return User::factory()->create(array_merge(['username' => $username, 'xp' => $xp], $attributes));
    }

    /** The usernames in the table, from the first row to the last. */
    private function usernames($response): array
    {
        preg_match_all('~<tr>\s*<td>(.*?)</td>~s', $response->getContent(), $matches);

        return $matches[1];
    }

    public function test_the_leaderboard_is_open_to_guests_and_to_signed_in_players()
    {
        $user = $this->player('anna', 10);

        $this->get('/leaderboard')->assertOk();
        $this->actingAs($user)->get('/leaderboard')->assertOk();
    }

    public function test_every_row_has_the_username_the_xp_and_the_total_right_answers()
    {
        $this->player('anna', 120, [
            'art' => '3/4',
            'geography' => '2/4',
            'history' => '0/4',
            'science' => '1/4',
            'sports' => '4/4',
        ]);

        $this->get('/leaderboard')
            ->assertSee('<th>Username</th>', false)
            ->assertSee('<th>XP</th>', false)
            ->assertSee('<th>Total right answers</th>', false)
            ->assertSeeInOrder(['<td>anna</td>', '<td>120</td>', '<td>10</td>'], false);
    }

    public function test_the_players_are_ordered_by_xp_from_high_to_low()
    {
        $this->player('anna', 120);
        $this->player('zoe', 300);
        $this->player('maria', 500);

        $this->assertSame(['maria', 'zoe', 'anna'], $this->usernames($this->get('/leaderboard')));
    }

    public function test_equal_xp_is_ordered_by_username_from_a_to_z()
    {
        $this->player('maria', 120);
        $this->player('zoe', 300);
        $this->player('anna', 120);

        $this->assertSame(['zoe', 'anna', 'maria'], $this->usernames($this->get('/leaderboard')));
    }

    public function test_the_same_name_and_the_same_xp_are_ordered_by_id()
    {
        $first = $this->player('maria', 120, ['art' => '1/4']);
        $second = $this->player('maria', 120, ['art' => '2/4']);
        $this->player('anna', 120, ['art' => '3/4']);

        $this->assertLessThan($second->id, $first->id);

        $this->get('/leaderboard')->assertSeeInOrder([
            '<td>anna</td>', '<td>120</td>', '<td>3</td>',
            '<td>maria</td>', '<td>120</td>', '<td>1</td>',
            '<td>maria</td>', '<td>120</td>', '<td>2</td>',
        ], false);
    }

    public function test_the_query_sorts_by_xp_then_username_then_id()
    {
        // SQLite returns equal rows in insertion order anyway, so check the query itself:
        // MySQL gives no such guarantee.
        $this->player('anna', 10);

        DB::enableQueryLog();
        User::topPlayers();
        $sql = DB::getQueryLog()[0]['query'];
        DB::disableQueryLog();

        $this->assertStringContainsString('order by "xp" desc, "username" asc, "id" asc limit 10', $sql);
    }

    public function test_the_order_is_the_same_on_every_reload()
    {
        foreach (['kate', 'bob', 'dora', 'adam', 'eve', 'carl'] as $name) {
            $this->player($name, 800);
        }

        $first = $this->usernames($this->get('/leaderboard'));

        $this->assertSame($first, $this->usernames($this->get('/leaderboard')));
        $this->assertSame(['adam', 'bob', 'carl', 'dora', 'eve', 'kate'], $first);
    }

    public function test_only_the_top_ten_players_are_shown()
    {
        foreach (range(1, 12) as $number) {
            $this->player('player' . str_pad($number, 2, '0', STR_PAD_LEFT), $number * 10);
        }

        $names = $this->usernames($this->get('/leaderboard'));

        $this->assertCount(10, $names);
        $this->assertSame('player12', $names[0]);
        $this->assertSame('player03', $names[9]);
        $this->assertNotContains('player02', $names);
        $this->assertNotContains('player01', $names);
    }

    public function test_players_with_the_same_xp_at_the_border_do_not_add_an_eleventh_row()
    {
        foreach (range(1, 6) as $number) {
            $this->player('top' . $number, 1000 + $number);
        }

        foreach (['d', 'a', 'c', 'b', 'e'] as $name) {
            $this->player('tie-' . $name, 800);
        }

        $names = $this->usernames($this->get('/leaderboard'));

        $this->assertCount(10, $names);
        $this->assertSame(['tie-a', 'tie-b', 'tie-c', 'tie-d'], array_slice($names, 6));
        $this->assertNotContains('tie-e', $names);
    }

    public function test_fewer_than_ten_players_show_only_their_rows()
    {
        $this->player('anna', 30);
        $this->player('bob', 20);
        $this->player('carl', 0);

        $response = $this->get('/leaderboard')->assertOk()->assertDontSee('No players yet.');

        $this->assertSame(['anna', 'bob', 'carl'], $this->usernames($response));
    }

    public function test_without_players_a_short_message_replaces_the_table()
    {
        $this->get('/leaderboard')
            ->assertOk()
            ->assertSee('No players yet.')
            ->assertDontSee('<table', false);
    }

    public function test_the_page_shows_no_email_and_no_password()
    {
        $this->player('anna', 10, ['email' => 'anna@example.com']);

        $this->get('/leaderboard')
            ->assertDontSee('anna@example.com')
            ->assertDontSee('password');
    }

    public function test_a_username_with_html_cannot_change_the_page()
    {
        $this->player('<b>boss</b>', 10);

        $this->get('/leaderboard')
            ->assertDontSee('<td><b>boss</b></td>', false)
            ->assertSee('&lt;b&gt;boss&lt;/b&gt;', false);
    }

    public function test_greek_usernames_are_shown_correctly()
    {
        $this->player('Ελένη', 10);

        $this->get('/leaderboard')->assertSee('<td>Ελένη</td>', false);
    }

    public function test_a_link_leads_back_to_the_homepage()
    {
        $this->get('/leaderboard')->assertSee('href="' . route('home') . '"', false);
    }
}
