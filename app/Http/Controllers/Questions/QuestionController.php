<?php

namespace App\Http\Controllers\Questions;

use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\MessageBag;

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

        //the answers are complete and valid; scoring and the results page come with #29 to #31
        return redirect()->route('quiz');
    }




    public function results(Request $request)
    {
        //get quiz from DB
        $quiz = Quiz::where('id', $request->quiz)->get()->first();
        $request = $request->all();
        //makes quiz completed
        $quiz['completed'] = 1;

        $results = array('overall' => 0, 'art' => 0, 'geography' => 0, 'history' => 0, 'science' => 0, 'sports' => 0);
        $xp = 0;

        //figuring out which answers are correct
        foreach ($request as $key => $value) {
            if (is_numeric($key)) {
                $correct_answer = Answer::where('question_id', $key)->where('correct', 1)->get()->first()['answer'];
                if ($correct_answer == $value) {
                    $question = Question::where('id', $key)->get()->first();
                    $results['overall']++;
                }
            }
        }

        //adding xp to the 
        Auth::user()['xp'] += $xp;

        //adding categories score to the user
        foreach ($results as $key => $value) {
            if ($key != 'overall') {
                [$correct, $total] = [explode("/", Auth::user()[$key])[0], explode("/", Auth::user()[$key])[1]];
                Auth::user()[$key] = ($correct + $value) . "/" . ($total + 4);
            }
        }

        //adding xp to the user
        Auth::user()['xp'] += $xp;

        //save changes in DB
        Auth::user()->save();
        $quiz->save();


        return view('questions.results', ['results' => $results]);
    }
}
