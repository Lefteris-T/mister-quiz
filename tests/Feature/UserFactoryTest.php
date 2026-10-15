<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserFactoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        DB::purge('sqlite');

        $this->artisan('migrate', ['--force' => true])
            ->assertExitCode(0);
    }

    public function test_user_factory_creates_a_user_with_a_username()
    {
        $user = User::factory()->create();

        $this->assertNotEmpty($user->username);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'username' => $user->username,
        ]);
    }
}
