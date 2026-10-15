<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Question;
use Tests\TestCase;

class QuestionComponentTest extends TestCase
{
    private function questionWithAnswers(): Question
    {
        $question = new Question();
        $question->forceFill(['id' => 7, 'question' => 'World War I began in which year?']);

        $answers = collect([
            ['id' => 25, 'answer' => '1923'],
            ['id' => 26, 'answer' => '1938'],
            ['id' => 27, 'answer' => '1917'],
            ['id' => 28, 'answer' => '1914'],
        ])->map(function ($row) {
            return (new Answer())->forceFill($row);
        });

        return $question->setRelation('answers', $answers);
    }

    public function test_the_question_text_is_shown()
    {
        $this->blade('<x-question :question="$question" />', ['question' => $this->questionWithAnswers()])
            ->assertSee('World War I began in which year?');
    }

    public function test_there_is_one_radio_button_per_answer_with_the_answer_text()
    {
        $view = $this->blade('<x-question :question="$question" />', ['question' => $this->questionWithAnswers()]);

        foreach ([25 => '1923', 26 => '1938', 27 => '1917', 28 => '1914'] as $id => $text) {
            $view->assertSee('<input type="radio" name="answers[7]" value="' . $id . '">', false);
            $view->assertSee('<span>' . $text . '</span>', false);
        }

        $this->assertSame(4, substr_count((string) $view, 'type="radio"'));
    }

    public function test_no_answer_is_selected_in_advance()
    {
        $this->blade('<x-question :question="$question" />', ['question' => $this->questionWithAnswers()])
            ->assertDontSee('checked');
    }
}
