<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    use HasFactory;

    protected $fillable = ['completed', 'user_id'];

    public function questions()
    {
        return $this->belongsToMany(Question::class);
    }

    public function getQuestions()
    {
        $questions_quiz = Question_Quiz::where('quiz_id', $this->id)
            ->orderBy('position')
            ->get()
            ->toArray();
        $result = [];
        foreach ($questions_quiz as $q) {
            array_push($result, Question::where('id', $q['question_id'])->first());
        }
        return $result;
    }
}
