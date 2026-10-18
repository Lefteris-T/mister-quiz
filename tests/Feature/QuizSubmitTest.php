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

    private function answersWithCorrectPerCategory(Quiz $quiz, int $correctPerCategory): array
    {
        $answers = [];
        $correctCounts = [];

        foreach ($quiz->questions as $question) {
            $category = $question->category;
            $correctCount = $correctCounts[$category] ?? 0;

            if ($correctCount < $correctPerCategory) {
                $answer = $question->answers->firstWhere('correct', 1);
                $correctCounts[$category] = $correctCount + 1;
            } else {
                $answer = $question->answers->firstWhere('correct', 0);
            }

            $answers[$question->id] = $answer->id;
        }

        return $answers;
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

    public function test_a_complete_valid_submit_saves_answers_and_completes_the_quiz()
    {
        $user = User::factory()->create();
        $quiz = $this->openQuiz($user);
        $answers = $this->fullAnswers($quiz);

        // This ID does not belong to the quiz and must not be saved.
        $submittedAnswers = $answers + [999999 => 1];

        $this->submit($user, $quiz, $submittedAnswers)
            ->assertRedirect('/quiz/' . $quiz->id . '/results')
            ->assertSessionHasNoErrors();

        $this->assertSame(1, (int) $quiz->fresh()->completed);

        foreach ($answers as $questionId => $answerId) {
            $savedAnswerId = DB::table('question_quiz')
                ->where('quiz_id', $quiz->id)
                ->where('question_id', $questionId)
                ->value('answer_id');

            $this->assertSame((int) $answerId, (int) $savedAnswerId);
        }

        $this->assertSame(
            0,
            DB::table('question_quiz')
                ->where('quiz_id', $quiz->id)
                ->where('question_id', 999999)
                ->count()
        );
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

    public function test_a_rejected_submit_shows_a_message_and_marks_the_unanswered_questions()
    {
        $user = User::factory()->create();
        $quiz = $this->openQuiz($user);

        $answers = $this->fullAnswers($quiz);
        foreach (array_slice(array_keys($answers), 0, 3) as $questionId) {
            unset($answers[$questionId]);
        }

        $page = $this->actingAs($user)
            ->followingRedirects()
            ->post(route('quiz.submit', $quiz), ['answers' => $answers])
            ->assertOk()
            ->assertSee(self::MISSING);

        $html = $page->getContent();

        $this->assertSame(1, substr_count($html, self::MISSING));
        $this->assertSame(3, substr_count($html, 'class="mb4 unanswered"'));
        $this->assertSame(3, substr_count($html, 'Please answer this question.'));
    }

    public function test_an_invalid_answer_shows_its_own_message_on_its_question()
    {
        $user = User::factory()->create();
        $quiz = $this->openQuiz($user);

        $answers = $this->fullAnswers($quiz);
        $answers[array_key_first($answers)] = 999999;

        $html = $this->actingAs($user)
            ->followingRedirects()
            ->post(route('quiz.submit', $quiz), ['answers' => $answers])
            ->assertOk()
            ->getContent();

        $this->assertSame(2, substr_count($html, self::INVALID));
        $this->assertSame(1, substr_count($html, 'class="mb4 unanswered"'));
        $this->assertStringNotContainsString(self::MISSING, $html);
    }

    public function test_a_fresh_quiz_page_shows_no_error_and_marks_nothing()
    {
        $user = User::factory()->create();
        $this->openQuiz($user);

        $this->actingAs($user)->get('/quiz')
            ->assertOk()
            ->assertDontSee(self::MISSING)
            ->assertDontSee('Please answer this question.')
            ->assertDontSee('unanswered');
    }

    public function test_correct_answers_add_question_xp_and_update_category_scores()
    {
        $user = User::factory()->create();
        $quiz = $this->openQuiz($user);

        foreach ($quiz->questions as $index => $question) {
            DB::table('question')
                ->where('id', $question->id)
                ->update(['xp' => ($index + 1) * 10]);
        }

        $quiz->unsetRelation('questions');
        $quiz->load('questions.answers');

        $answers = $this->answersWithCorrectPerCategory($quiz, 2);
        $expectedXp = 0;

        foreach ($quiz->questions as $question) {
            $selectedAnswer = $question->answers
                ->firstWhere('id', $answers[$question->id]);

            if ((int) $selectedAnswer->correct === 1) {
                $expectedXp += (int) $question->xp;
            }
        }

        $this->submit($user, $quiz, $answers)
            ->assertRedirect('/quiz/' . $quiz->id . '/results');

        $this->assertSame($expectedXp, (int) $user->fresh()->xp);

        foreach (['art', 'history', 'geography', 'science', 'sports'] as $category) {
            $this->assertSame('2/4', $user->fresh()->{$category}, $category);
        }
    }

    public function test_scores_accumulate_across_completed_quizzes()
    {
        $user = User::factory()->create();

        $firstQuiz = $this->openQuiz($user);
        $firstAnswers = $this->answersWithCorrectPerCategory($firstQuiz, 1);
        $firstXp = 0;

        foreach ($firstQuiz->questions as $question) {
            $answer = $question->answers
                ->firstWhere('id', $firstAnswers[$question->id]);

            if ((int) $answer->correct === 1) {
                $firstXp += (int) $question->xp;
            }
        }

        $this->submit($user, $firstQuiz, $firstAnswers)
            ->assertRedirect('/quiz/' . $firstQuiz->id . '/results');

        $secondQuiz = $this->openQuiz($user);
        $secondAnswers = $this->answersWithCorrectPerCategory($secondQuiz, PHP_INT_MAX);
        $secondXp = 0;

        foreach ($secondQuiz->questions as $question) {
            $answer = $question->answers
                ->firstWhere('id', $secondAnswers[$question->id]);

            if ((int) $answer->correct === 1) {
                $secondXp += (int) $question->xp;
            }
        }

        $this->submit($user, $secondQuiz, $secondAnswers)
            ->assertRedirect('/quiz/' . $secondQuiz->id . '/results');

        $this->assertSame($firstXp + $secondXp, (int) $user->fresh()->xp);

        foreach (['art', 'history', 'geography', 'science', 'sports'] as $category) {
            $this->assertSame('5/8', $user->fresh()->{$category}, $category);
        }
    }

    public function test_submitting_the_same_quiz_again_does_not_change_user_scores()
    {
        $user = User::factory()->create();
        $quiz = $this->openQuiz($user);
        $answers = $this->answersWithCorrectPerCategory($quiz, 1);

        $this->submit($user, $quiz, $answers)
            ->assertRedirect('/quiz/' . $quiz->id . '/results');

        $xpAfterFirstSubmit = (int) $user->fresh()->xp;
        $scoresAfterFirstSubmit = [];

        foreach (['art', 'history', 'geography', 'science', 'sports'] as $category) {
            $scoresAfterFirstSubmit[$category] = $user->fresh()->{$category};
        }

        $this->submit($user, $quiz, $answers)
            ->assertRedirect(route('quiz'))
            ->assertSessionHasErrors([
                'quiz' => 'This quiz has already been submitted.',
            ]);

        $this->assertSame(1, (int) $quiz->fresh()->completed);
        $this->assertSame($xpAfterFirstSubmit, (int) $user->fresh()->xp);

        foreach ($scoresAfterFirstSubmit as $category => $score) {
            $this->assertSame($score, $user->fresh()->{$category}, $category);
        }
    }

    public function test_results_count_correct_answers_only_for_questions_in_the_quiz()
    {
        $user = User::factory()->create();
        $quiz = $this->openQuiz($user);
        $answers = $this->answersWithCorrectPerCategory($quiz, 2);

        // The extra ID is submitted but does not belong to this quiz.
        $this->submit($user, $quiz, $answers + [999999 => 1])
            ->assertRedirect('/quiz/' . $quiz->id . '/results');

        $path = '/quiz/' . $quiz->id . '/results';

        $firstVisit = $this->actingAs($user)->get($path)->assertOk();
        $firstVisit->assertSee('10 / 20');
        $this->assertSame(5, substr_count($firstVisit->getContent(), '2 / 4'));

        // Refreshing the page reads the saved results again.
        $refresh = $this->actingAs($user)->get($path)->assertOk();
        $refresh->assertSee('10 / 20');
        $this->assertSame(5, substr_count($refresh->getContent(), '2 / 4'));
    }

    public function test_results_use_the_actual_number_of_questions_in_each_category()
    {
        $user = User::factory()->create();
        $quiz = $this->openQuiz($user);

        $artQuestionIds = $quiz->questions
            ->where('category', 'Art')
            ->take(2)
            ->pluck('id')
            ->all();

        $quiz->questions()->detach($artQuestionIds);
        $quiz->unsetRelation('questions');
        $quiz->load('questions.answers');

        $answers = $this->answersWithCorrectPerCategory($quiz, PHP_INT_MAX);

        $this->submit($user, $quiz, $answers)
            ->assertRedirect('/quiz/' . $quiz->id . '/results');

        $this->assertSame('2/2', $user->fresh()->art);

        foreach (['history', 'geography', 'science', 'sports'] as $category) {
            $this->assertSame('4/4', $user->fresh()->{$category}, $category);
        }

        $page = $this->actingAs($user)
            ->get('/quiz/' . $quiz->id . '/results')
            ->assertOk();

        $page->assertSee('18 / 18');
        $page->assertSee('2 / 2');

        $this->assertSame(4, substr_count($page->getContent(), '4 / 4'));
    }

    public function test_a_user_cannot_view_another_users_results()
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $quiz = $this->openQuiz($owner);

        $this->submit($owner, $quiz, $this->fullAnswers($quiz))
            ->assertRedirect('/quiz/' . $quiz->id . '/results');

        $this->actingAs($otherUser)
            ->get('/quiz/' . $quiz->id . '/results')
            ->assertForbidden();
    }
}
