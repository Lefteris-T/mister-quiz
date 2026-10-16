<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Database\Seeders\QuestionSeeder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QuizPersistenceTest extends TestCase
{
    private const NO_QUESTIONS = 'No questions are available yet. Please try again later.';

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

    private function questionIdsOf(Quiz $quiz): array
    {
        return $quiz->questions->pluck('id')->all();
    }

    public function test_the_first_visit_saves_one_open_quiz_with_twenty_ordered_questions()
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/quiz')->assertOk();

        $this->assertSame(1, Quiz::count());

        $quiz = Quiz::first();

        $this->assertSame($user->id, (int) $quiz->user_id);
        $this->assertSame(0, (int) $quiz->completed);
        $this->assertSame(range(1, 20), array_map('intval', $quiz->questions->pluck('pivot.position')->all()));

        foreach (Question::CATEGORIES as $category) {
            $this->assertSame(4, $quiz->questions->where('category', $category)->count(), $category);
        }
    }

    public function test_a_refresh_shows_the_same_questions_in_the_same_order()
    {
        $user = User::factory()->create();

        $first = $this->actingAs($user)->get('/quiz');
        $second = $this->actingAs($user)->get('/quiz');

        $this->assertSame(1, Quiz::count());
        $this->assertSame(20, DB::table('question_quiz')->count());

        $this->assertSame(
            $this->questionIdsOf($first->viewData('quiz')),
            $this->questionIdsOf($second->viewData('quiz'))
        );

        $texts = $second->viewData('quiz')->questions->pluck('question')->map(function ($text) {
            return e($text);
        })->all();

        $second->assertSeeInOrder($texts, false);
    }

    public function test_an_open_quiz_is_returned_with_its_questions_in_position_order()
    {
        $user = User::factory()->create();
        $quiz = Quiz::create(['user_id' => $user->id, 'completed' => 0]);
        [$a, $b, $c] = Question::orderBy('id')->take(3)->pluck('id')->all();

        $quiz->questions()->attach([
            $a => ['position' => 3],
            $b => ['position' => 1],
            $c => ['position' => 2],
        ]);

        $shown = $this->actingAs($user)->get('/quiz')->viewData('quiz');

        $this->assertSame($quiz->id, $shown->id);
        $this->assertSame([$b, $c, $a], $this->questionIdsOf($shown));
        $this->assertSame(1, Quiz::count());
    }

    public function test_after_the_quiz_is_completed_start_quiz_makes_a_new_one()
    {
        $user = User::factory()->create();

        $firstQuiz = $this->actingAs($user)->get('/quiz')->viewData('quiz');

        Quiz::where('id', $firstQuiz->id)->update(['completed' => 1]);

        $secondQuiz = $this->actingAs($user)->get('/quiz')->viewData('quiz');

        $this->assertNotSame($firstQuiz->id, $secondQuiz->id);
        $this->assertSame(2, Quiz::count());
        $this->assertSame(1, Quiz::where('user_id', $user->id)->where('completed', 0)->count());
        $this->assertSame(1, (int) Quiz::find($firstQuiz->id)->completed);
    }

    public function test_every_user_gets_a_quiz_of_their_own()
    {
        $one = User::factory()->create();
        $two = User::factory()->create();

        $quizOne = $this->actingAs($one)->get('/quiz')->viewData('quiz');
        $quizTwo = $this->actingAs($two)->get('/quiz')->viewData('quiz');

        $this->assertNotSame($quizOne->id, $quizTwo->id);
        $this->assertSame($one->id, (int) $quizOne->user_id);
        $this->assertSame($two->id, (int) $quizTwo->user_id);
    }

    public function test_starting_twice_never_leaves_two_open_quizzes()
    {
        $user = User::factory()->create();

        $first = Quiz::startFor($user);
        $second = Quiz::startFor($user);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Quiz::where('user_id', $user->id)->where('completed', 0)->count());
    }

    public function test_no_quiz_is_made_when_the_question_table_is_empty()
    {
        Question::query()->delete();

        $this->actingAs(User::factory()->create())
            ->get('/quiz')
            ->assertOk()
            ->assertSee(self::NO_QUESTIONS);

        $this->assertSame(0, Quiz::count());
        $this->assertSame(0, DB::table('question_quiz')->count());
    }

    public function test_no_quiz_is_made_when_one_category_has_no_questions()
    {
        Question::where('category', 'Art')->delete();

        $this->actingAs(User::factory()->create())
            ->get('/quiz')
            ->assertOk()
            ->assertSee(self::NO_QUESTIONS);

        $this->assertSame(0, Quiz::count());
    }
}
