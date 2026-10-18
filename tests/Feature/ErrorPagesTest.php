<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    public function test_a_wrong_method_shows_the_405_page()
    {
        $this->get('/quiz/c')
            ->assertStatus(405)
            ->assertSee('<title>Method Not Allowed</title>', false)
            ->assertSee('405')
            ->assertSee('Method Not Allowed');
    }

    public function test_every_wrong_method_shows_the_same_405_page()
    {
        $responses = [
            $this->post('/'),
            $this->post('/profile'),
            $this->put('/quiz/1'),
            $this->delete('/quiz/1'),
        ];

        foreach ($responses as $response) {
            $response->assertStatus(405)
                ->assertSee('<title>Method Not Allowed</title>', false)
                ->assertDontSee('is not supported for this route');
        }
    }

    public function test_the_405_page_looks_like_the_404_page()
    {
        $notFound = $this->get('/nonexistent');
        $notAllowed = $this->get('/quiz/c');

        $notFound->assertStatus(404)->assertSee('<title>Not Found</title>', false);

        $normalise = function (string $html) {
            return preg_replace(['/404|405/', '/Not Found|Method Not Allowed/'], ['CODE', 'TEXT'], $html);
        };

        $this->assertSame($normalise($notFound->getContent()), $normalise($notAllowed->getContent()));
    }

    public function test_the_405_page_replaces_the_developer_page_in_debug_mode()
    {
        config(['app.debug' => true]);

        $this->get('/quiz/c')
            ->assertStatus(405)
            ->assertSee('<title>Method Not Allowed</title>', false)
            ->assertDontSee('Supported methods')
            ->assertDontSee('is not supported for this route');
    }

    public function test_a_missing_form_token_shows_the_419_page()
    {
        // Laravel skips the token check while the environment is "testing".
        $this->app['env'] = 'production';

        $this->post('/login', ['email' => 'a@b.c', 'password' => 'x'])
            ->assertStatus(419)
            ->assertSee('<title>Page Expired</title>', false);
    }

    public function test_a_server_error_shows_the_500_page_without_details()
    {
        config(['app.debug' => false]);

        Route::get('/__server-error', function () {
            throw new \RuntimeException('secret detail in /var/www/html/app');
        });

        $this->get('/__server-error')
            ->assertStatus(500)
            ->assertSee('<title>Server Error</title>', false)
            ->assertDontSee('secret detail')
            ->assertDontSee('/var/www/html')
            ->assertDontSee('RuntimeException');
    }

    public function test_the_500_page_does_not_need_the_database()
    {
        config([
            'app.debug' => false,
            'database.default' => 'mysql',
            'database.connections.mysql.host' => 'no-such-host',
        ]);

        Route::get('/__database-error', function () {
            DB::table('users')->count();
        });

        $this->get('/__database-error')
            ->assertStatus(500)
            ->assertSee('<title>Server Error</title>', false);
    }
}
