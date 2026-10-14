<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuestionSeeder extends Seeder
{
    public function run()
    {
        $questions = json_decode(
            file_get_contents(database_path('data/questions.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        DB::transaction(function () use ($questions) {
            foreach ($questions as $question) {
                DB::table('question')->updateOrInsert(
                    [
                        'question' => $question['question'],
                        'category' => $question['category'],
                    ],
                    [
                        'xp' => $question['xp'],
                    ]
                );

                $questionId = DB::table('question')
                    ->where('question', $question['question'])
                    ->where('category', $question['category'])
                    ->value('id');

                foreach ($question['answers'] as $answer) {
                    DB::table('answer')->updateOrInsert(
                        [
                            'question_id' => $questionId,
                            'answer' => $answer['answer'],
                        ],
                        [
                            'correct' => $answer['correct'],
                        ]
                    );
                }
            }
        });
    }
}