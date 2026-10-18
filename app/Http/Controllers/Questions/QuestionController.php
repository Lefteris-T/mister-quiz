<?php

namespace App\Http\Controllers\Questions;

use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Facades\DB;

class QuestionController extends Controller
{
    public function index(Request $request)
    {
        //the open quiz of the user, or a new one (4 random questions from each category)
        $quiz = Quiz::startFor($request->user());

        if ($quiz) {
            $quiz->load('questions.answers');
        }

        return view('questions.list', ['quiz' => $quiz]);
    }

    public function submit(Request $request, Quiz $quiz)
    {
        //only the owner of the quiz can submit it
        abort_unless((int) $quiz->user_id === (int) $request->user()->id, 403);

        if ((int) $quiz->completed === 1) {
            return redirect()->route('quiz')->withErrors(['quiz' => 'This quiz has already been submitted.']);
        }

        //every question of this quiz needs one answer, and the answer must belong to that question
        $questionIds = $quiz->questions()->pluck('question.id');

        $allowed = Answer::whereIn('question_id', $questionIds)->get(['id', 'question_id'])
            ->groupBy('question_id')
            ->map(function ($answers) {
                return $answers->pluck('id')->map(function ($id) {
                    return (string) $id;
                })->all();
            });

        $submitted = $request->input('answers');
        $submitted = is_array($submitted) ? $submitted : [];

        $errors = new MessageBag();
        $missing = false;

        foreach ($questionIds as $questionId) {
            $answer = $submitted[$questionId] ?? null;

            if ($answer === null || $answer === '') {
                $missing = true;
                $errors->add('answers.' . $questionId, 'Please answer this question.');
            } elseif (!is_scalar($answer) || !in_array((string) $answer, $allowed[$questionId] ?? [], true)) {
                $errors->add('answers.' . $questionId, 'The selected answer is invalid.');
            }
        }

        if ($errors->isNotEmpty()) {
            $errors->add('quiz', $missing ? 'Please answer every question before you submit.' : 'The selected answer is invalid.');

            return redirect()->route('quiz')->withErrors($errors)->withInput($request->only('answers'));
        }

        $accepted = DB::transaction(function () use ($quiz, $questionIds, $submitted) {
            $claimed = Quiz::whereKey($quiz->id)
                ->where('completed', 0)
                ->update(['completed' => 1]);

            if ($claimed !== 1) {
                return false;
            }

            foreach ($questionIds as $questionId) {
                $quiz->questions()->updateExistingPivot($questionId, [
                    'answer_id' => (int) $submitted[$questionId],
                ]);
            }

            return true;
        });

        if (!$accepted) {
            return redirect()->route('quiz')->withErrors(['quiz' => 'This quiz has already been submitted.']);
        }

        return redirect()->route('quiz.results', $quiz);
    }

    public function results(Request $request, Quiz $quiz)
    {
        abort_unless((int) $quiz->user_id === (int) $request->user()->id, 403);

        if ((int) $quiz->completed !== 1) {
            return redirect()->route('quiz')
                ->withErrors(['quiz' => 'This quiz has not been submitted.']);
        }

        $quiz->load('questions.answers');

        $results = [
            'overall' => ['correct' => 0, 'total' => 0],
        ];

        foreach (Question::CATEGORIES as $category) {
            $results[strtolower($category)] = ['correct' => 0, 'total' => 0];
        }

        foreach ($quiz->questions as $question) {
            $categoryKey = strtolower($question->category);
            $correctAnswer = $question->answers->firstWhere('correct', 1);
            $isCorrect = $correctAnswer
                && (int) $question->pivot->answer_id === (int) $correctAnswer->id;

            $results['overall']['total']++;
            $results[$categoryKey]['total']++;

            if ($isCorrect) {
                $results['overall']['correct']++;
                $results[$categoryKey]['correct']++;
            }
        }

        return view('questions.results', ['results' => $results]);
    }
}
