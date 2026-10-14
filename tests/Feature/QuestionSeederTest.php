<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QuestionSeederTest extends TestCase
{
    public function test_it_seeds_an_empty_database_without_duplicates()
    {
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        DB::purge('sqlite');

        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(
            ':memory:',
            DB::connection()->getDatabaseName()
        );

        $this->artisan('migrate', ['--force' => true])
            ->assertExitCode(0);

        $this->artisan('db:seed')->assertExitCode(0);

        $this->assertQuestionData();

        $questionIds = DB::table('question')->orderBy('id')->pluck('id')->all();
        $answerIds = DB::table('answer')->orderBy('id')->pluck('id')->all();

        $this->artisan('db:seed')->assertExitCode(0);

        $this->assertQuestionData();

        $this->assertSame(
            $questionIds,
            DB::table('question')->orderBy('id')->pluck('id')->all()
        );

        $this->assertSame(
            $answerIds,
            DB::table('answer')->orderBy('id')->pluck('id')->all()
        );
    }

    private function assertQuestionData(): void
    {
        $this->assertSame(60, DB::table('question')->count());
        $this->assertSame(240, DB::table('answer')->count());

        $categories = DB::table('question')
            ->distinct()
            ->pluck('category')
            ->all();

        $this->assertEqualsCanonicalizing(
            ['Art', 'History', 'Geography', 'Science', 'Sports'],
            $categories
        );

        foreach ($categories as $category) {
            $this->assertSame(
                12,
                DB::table('question')->where('category', $category)->count()
            );
        }

        foreach (DB::table('question')->pluck('id') as $questionId) {
            $this->assertSame(
                4,
                DB::table('answer')->where('question_id', $questionId)->count()
            );

            $this->assertSame(
                1,
                DB::table('answer')
                    ->where('question_id', $questionId)
                    ->where('correct', 1)
                    ->count()
            );
        }
    }
}