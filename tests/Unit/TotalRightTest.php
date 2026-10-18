<?php

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\TestCase;

class TotalRightTest extends TestCase
{
    private function userWith(array $scores): User
    {
        return (new User())->forceFill(array_merge([
            'art' => '0/0',
            'geography' => '0/0',
            'history' => '0/0',
            'science' => '0/0',
            'sports' => '0/0',
        ], $scores));
    }

    public function test_a_new_player_has_no_right_answers()
    {
        $this->assertSame(0, $this->userWith([])->totalRight());
    }

    public function test_the_total_is_the_sum_of_the_right_answers_of_the_five_categories()
    {
        $user = $this->userWith([
            'art' => '3/4',
            'geography' => '2/4',
            'history' => '0/4',
            'science' => '1/4',
            'sports' => '4/4',
        ]);

        $this->assertSame(10, $user->totalRight());
    }

    public function test_two_digit_values_are_added_correctly()
    {
        $user = $this->userWith([
            'art' => '12/20',
            'geography' => '10/20',
            'history' => '8/20',
            'science' => '15/20',
            'sports' => '5/20',
        ]);

        $this->assertSame(50, $user->totalRight());
    }

    public function test_the_answered_numbers_are_not_counted()
    {
        $this->assertSame(1, $this->userWith(['art' => '1/99'])->totalRight());
    }

    public function test_a_category_with_bad_text_counts_as_zero()
    {
        $user = $this->userWith(['art' => 'abc', 'geography' => '', 'history' => '3/4']);

        $this->assertSame(3, $user->totalRight());
    }
}
