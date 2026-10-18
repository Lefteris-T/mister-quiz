<?php

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\TestCase;

class CategoryScoreTest extends TestCase
{
    /**
     * @dataProvider scores
     */
    public function test_the_score_text_gives_right_answered_and_percentage($text, $right, $answered, $percentage)
    {
        $user = (new User())->forceFill(['art' => $text]);

        $this->assertSame(
            ['right' => $right, 'answered' => $answered, 'percentage' => $percentage],
            $user->categoryScore('art')
        );
    }

    public function scores(): array
    {
        return [
            'new player' => ['0/0', 0, 0, 0],
            'nothing right' => ['0/5', 0, 5, 0],
            'one third' => ['1/3', 1, 3, 33],
            'two thirds' => ['2/3', 2, 3, 67],
            'half rounds up' => ['1/8', 1, 8, 13],
            'two digits' => ['12/20', 12, 20, 60],
            'all right' => ['8/8', 8, 8, 100],
            'empty text' => ['', 0, 0, 0],
            'no slash' => ['5', 0, 0, 0],
            'not numbers' => ['a/b', 0, 0, 0],
        ];
    }

    public function test_each_category_reads_its_own_column()
    {
        $user = (new User())->forceFill([
            'art' => '1/2',
            'geography' => '2/4',
            'history' => '3/6',
            'science' => '4/8',
            'sports' => '5/10',
        ]);

        $this->assertSame(1, $user->categoryScore('art')['right']);
        $this->assertSame(2, $user->categoryScore('geography')['right']);
        $this->assertSame(3, $user->categoryScore('history')['right']);
        $this->assertSame(4, $user->categoryScore('science')['right']);
        $this->assertSame(5, $user->categoryScore('sports')['right']);
    }
}
