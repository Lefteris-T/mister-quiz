<?php

namespace Tests\Feature;

use App\Models\Question;
use Database\Seeders\QuestionSeeder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QuizQuestionPickTest extends TestCase
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

        $this->seed(QuestionSeeder::class);
    }

    public function test_the_category_list_matches_the_categories_in_the_data()
    {
        $this->assertEqualsCanonicalizing(
            Question::CATEGORIES,
            Question::distinct()->pluck('category')->all()
        );
    }

    public function test_a_quiz_has_four_different_questions_from_every_category()
    {
        $quiz = Question::pickForQuiz();

        $this->assertCount(20, $quiz);
        $this->assertCount(20, $quiz->pluck('id')->unique());

        foreach (Question::CATEGORIES as $category) {
            $this->assertSame(4, $quiz->where('category', $category)->count(), $category);
        }
    }

    public function test_the_number_of_questions_per_category_can_be_changed()
    {
        $quiz = Question::pickForQuiz(1);

        $this->assertCount(5, $quiz);

        foreach (Question::CATEGORIES as $category) {
            $this->assertSame(1, $quiz->where('category', $category)->count(), $category);
        }
    }

    public function test_a_category_with_fewer_questions_than_asked_does_not_crash()
    {
        $extraArtIds = Question::where('category', 'Art')->orderBy('id')->pluck('id')->slice(2)->all();
        Question::whereIn('id', $extraArtIds)->delete();

        $quiz = Question::pickForQuiz();

        $this->assertSame(2, $quiz->where('category', 'Art')->count());
        $this->assertCount(18, $quiz);

        foreach (['History', 'Geography', 'Science', 'Sports'] as $category) {
            $this->assertSame(4, $quiz->where('category', $category)->count(), $category);
        }
    }
}
