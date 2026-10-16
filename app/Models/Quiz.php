<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Quiz extends Model
{
    use HasFactory;

    protected $fillable = ['completed', 'user_id'];

    public function questions()
    {
        return $this->belongsToMany(Question::class)
            ->withPivot('position', 'answer_id')
            ->orderBy('question_quiz.position');
    }

    public function getQuestions()
    {
        return $this->questions->all();
    }

    /**
     * The open quiz of the user, or a new one when there is none.
     * Returns null when no quiz can be made (a category has no questions).
     */
    public static function startFor(User $user): ?Quiz
    {
        return DB::transaction(function () use ($user) {
            // Locking the user row makes a second request wait here, so a user never gets two open quizzes.
            User::whereKey($user->id)->lockForUpdate()->first();

            $open = static::where('user_id', $user->id)->where('completed', 0)->first();

            if ($open) {
                return $open;
            }

            $questions = Question::pickForQuiz();

            if ($questions->pluck('category')->unique()->count() < count(Question::CATEGORIES)) {
                return null;
            }

            $quiz = static::create(['user_id' => $user->id, 'completed' => 0]);

            $quiz->questions()->attach(
                $questions->values()->mapWithKeys(function ($question, $index) {
                    return [$question->id => ['position' => $index + 1]];
                })->all()
            );

            return $quiz;
        });
    }
}
