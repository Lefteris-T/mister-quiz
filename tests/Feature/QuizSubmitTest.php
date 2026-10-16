<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\User;
use Database\Seeders\QuestionSeeder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QuizSubmitTest extends TestCase
{
    private const MISSING = 'Please answer every question before you submit.';
    private const INVALID = 'The selected answer is invalid.';

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

    private function openQuiz(User $user): Quiz
    {
        return Quiz::startFor($user)->load('questions.answers');
    }

    // question id => id of its first answer, for every question of the quiz
    private function fullAnswers(Quiz $quiz): array
    {
        return $quiz->questions->mapWithKeys(function ($question) {
            return [$question->id => $question->answers->first()->id];
        })->all();
    }

    private function submit(User $user, Quiz $quiz, array $answers)
    {
        return $this->actingAs($user)->post(route('quiz.submit', $quiz), ['answers' => $answers]);
    }

    private function assertNothingChanged(User $user, Quiz $quiz): void
    {
        $this->assertSame(0, (int) $quiz->fresh()->completed);
        $this->assertSame(0, (int) $user->fresh()->xp);

        foreach (['art', 'history', 'geography', 'science', 'sports'] as $category) {
            $this->assertSame('0/0', $user->fresh()->{$category}, $category);
        }
    }

    public function test_the_quiz_form_posts_to_the_submit_route_of_the_quiz()
    {
        $user = User::factory()->create();
        $quiz = $this->openQuiz($user);

        $this->actingAs($user)->get('/quiz')
            ->assertSee('action="' . route('quiz.submit', $quiz) . '"', false);
    }

    public function test_a_complete_valid_submit_is_accepted_and_nothing_is_scored_yet()
    {
        $user = User::factory()->create();
        $quiz = $this->openQuiz($user);

        $answers = $this->fullAnswers($quiz) + [999999 => 1];

        $this->submit($user, $quiz, $answers)
            ->assertRedirect(route('quiz'))
            ->assertSessionHasNoErrors();

        $this->assertNothingChanged($user, $quiz);
    }

    public function test_a_missing_answer_is_rejected_and_the_chosen_answers_are_kept()
    {
        $user = User::factory()->create();
        $quiz = $this->openQuiz($user);

        $answers = $this->fullAnswers($quiz);
        $missingId = array_key_first($answers);
        unset($answers[$missingId]);

        $response = $this->submit($user, $quiz, $answers);

        $response->assertRedirect(route('quiz'));
        $response->assertSessionHasErrors(['answers.' . $missingId, 'quiz']);

        $errors = session('errors');
        $this->assertSame(self::MISSING, $errors->first('quiz'));
        $this->assertSame('Please answer this question.', $errors->first('answers.' . $missingId));
        $this->assertSame(2, $errors->count());

        $this->assertEquals($answers, session('_old_input')['answers']);
        $this->assertNothingChanged($user, $quiz);
    }

    public function test_an_empty_submit_flags_every_question()
    {
        $user = User::factory()->create();
        $quiz = $this->openQuiz($user);

        $this->actingAs($user)->post(route('quiz.submit', $quiz))
            ->assertRedirect(route('quiz'));

        $errors = session('errors');
        $this->assertCount(20, array_filter($errors->keys(), function ($key) {
            return strpos($key, 'answers.') === 0;
        }));
        $this->assertSame(self::MISSING, $errors->first('quiz'));
        $this->assertNothingChanged($user, $quiz);
    }

    public function test_an_answer_that_belongs_to_another_question_is_rejected()
    {
        $user = User::factory()->create();
        $quiz = $this->openQuiz($user);

        $answers = $this->fullAnswers($quiz);
        [$first, $second] = array_keys($answers);
        $answers[$first] = $answers[$second];

        $this->submit($user, $quiz, $answers)->assertRedirect(route('quiz'));

        $errors = session('errors');
        $this->assertSame(self::INVALID, $errors->first('answers.' . $first));
        $this->assertSame(self::INVALID, $errors->first('quiz'));
        $this->assertNothingChanged($user, $quiz);
    }

    public function test_unknown_or_malformed_answers_are_rejected_without_a_crash()
    {
        $user = User::factory()->create();
        $quiz = $this->openQuiz($user);

        $answers = $this->fullAnswers($quiz);
        $first = array_key_first($answers);

        foreach ([999999, 'abc', ['1'], '0', '-1'] as $bad) {
            $answers[$first] = $bad;

            $this->submit($user, $quiz, $answers)->assertRedirect(route('quiz'));

            $this->assertSame(self::INVALID, session('errors')->first('answers.' . $first));
        }

        $this->assertNothingChanged($user, $quiz);
    }

    public function test_a_user_cannot_submit_the_quiz_of_another_user()
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $quiz = $this->openQuiz($owner);

        $this->submit($other, $quiz, $this->fullAnswers($quiz))->assertForbidden();

        $this->assertNothingChanged($owner, $quiz);
        $this->assertNothingChanged($other, $quiz);
    }

    public function test_an_unknown_quiz_is_not_found()
    {
        $this->actingAs(User::factory()->create())
            ->post('/quiz/99999', ['answers' => [1 => 1]])
            ->assertNotFound();
    }

    public function test_a_guest_who_submits_is_sent_to_the_login_page()
    {
        $quiz = $this->openQuiz(User::factory()->create());

        $this->post(route('quiz.submit', $quiz), ['answers' => $this->fullAnswers($quiz)])
            ->assertRedirect(route('login'));
    }

    public function test_a_quiz_that_is_already_submitted_is_refused()
    {
        $user = User::factory()->create();
        $quiz = $this->openQuiz($user);
        Quiz::where('id', $quiz->id)->update(['completed' => 1]);

        $this->submit($user, $quiz, $this->fullAnswers($quiz))->assertRedirect(route('quiz'));

        $this->assertSame('This quiz has already been submitted.', session('errors')->first('quiz'));
        $this->assertSame(0, (int) $user->fresh()->xp);
    }

    public function test_the_quiz_page_after_a_rejected_submit_keeps_the_chosen_answers_selected()
    {
        $user = User::factory()->create();
        $quiz = $this->openQuiz($user);

        $answers = $this->fullAnswers($quiz);
        unset($answers[array_key_first($answers)]);

        $page = $this->actingAs($user)
            ->followingRedirects()
            ->post(route('quiz.submit', $quiz), ['answers' => $answers])
            ->assertOk();

        $this->assertSame(19, substr_count($page->getContent(), 'checked'));

        foreach ($answers as $answerId) {
            $page->assertSee('value="' . $answerId . '" checked', false);
        }
    }
}
