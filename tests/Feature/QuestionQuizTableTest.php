<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Database\Seeders\QuestionSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QuestionQuizTableTest extends TestCase
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

    private function newQuiz(): Quiz
    {
        return Quiz::create([
            'user_id' => User::factory()->create()->id,
            'completed' => 0,
        ]);
    }

    public function test_the_questions_of_a_quiz_come_back_in_position_order()
    {
        $quiz = $this->newQuiz();
        [$first, $second, $third] = Question::orderBy('id')->take(3)->pluck('id')->all();

        $quiz->questions()->attach([
            $first => ['position' => 3],
            $second => ['position' => 1],
            $third => ['position' => 2],
        ]);

        $this->assertSame(
            [$second, $third, $first],
            collect($quiz->getQuestions())->pluck('id')->all()
        );
    }

    public function test_the_same_question_cannot_be_in_a_quiz_twice()
    {
        $quiz = $this->newQuiz();
        $question = Question::first();

        $quiz->questions()->attach($question->id, ['position' => 1]);

        $this->expectException(QueryException::class);

        $quiz->questions()->attach($question->id, ['position' => 2]);
    }

    public function test_a_question_needs_a_position_in_the_quiz()
    {
        $quiz = $this->newQuiz();

        $this->expectException(QueryException::class);

        $quiz->questions()->attach(Question::first()->id);
    }

    public function test_the_chosen_answer_is_empty_until_it_is_saved_and_must_exist()
    {
        $quiz = $this->newQuiz();
        $question = Question::with('answers')->first();
        $answer = $question->answers->first();

        $quiz->questions()->attach($question->id, ['position' => 1]);

        $this->assertNull(DB::table('question_quiz')->value('answer_id'));

        DB::table('question_quiz')->update(['answer_id' => $answer->id]);

        $this->assertSame($answer->id, (int) DB::table('question_quiz')->value('answer_id'));

        $this->expectException(QueryException::class);

        DB::table('question_quiz')->update(['answer_id' => 999999]);
    }

    public function test_deleting_the_chosen_answer_keeps_the_row_and_empties_the_choice()
    {
        $quiz = $this->newQuiz();
        $question = Question::with('answers')->first();
        $answer = $question->answers->first();

        $quiz->questions()->attach($question->id, ['position' => 1]);
        DB::table('question_quiz')->update(['answer_id' => $answer->id]);

        Answer::where('id', $answer->id)->delete();

        $this->assertSame(1, DB::table('question_quiz')->count());
        $this->assertNull(DB::table('question_quiz')->value('answer_id'));
    }

    public function test_deleting_a_quiz_or_a_question_removes_its_rows()
    {
        $quiz = $this->newQuiz();
        $otherQuiz = $this->newQuiz();
        [$first, $second] = Question::orderBy('id')->take(2)->pluck('id')->all();

        $quiz->questions()->attach([$first => ['position' => 1], $second => ['position' => 2]]);
        $otherQuiz->questions()->attach([$first => ['position' => 1]]);

        Question::where('id', $second)->delete();

        $this->assertSame(1, DB::table('question_quiz')->where('quiz_id', $quiz->id)->count());
        $this->assertSame(2, Quiz::count());

        $quiz->delete();

        $this->assertSame(0, DB::table('question_quiz')->where('quiz_id', $quiz->id)->count());
        $this->assertSame(1, DB::table('question_quiz')->where('quiz_id', $otherQuiz->id)->count());
    }
}
