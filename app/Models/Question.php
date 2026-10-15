<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    /**
     * The categories of the questions, spelled as in the `category` column.
     */
    public const CATEGORIES = ['History', 'Art', 'Geography', 'Science', 'Sports'];

    protected $table = 'question';

    /**
     * Pick random questions for a quiz: $perCategory from every category, mixed.
     * A category with fewer questions than asked gives all it has.
     */
    public static function pickForQuiz(int $perCategory = 4): Collection
    {
        $questions = new Collection();

        foreach (self::CATEGORIES as $category) {
            $questions = $questions->merge(
                static::where('category', $category)->inRandomOrder()->limit($perCategory)->get()
            );
        }

        return $questions->shuffle()->values();
    }

    public function answers()
    {
        return $this->hasMany(Answer::class);
    }

    public function quizes()
    {
        return $this->belongsToMany(Quiz::class);
    }
}
