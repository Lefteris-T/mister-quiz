<?php

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\TestCase;

class RankTest extends TestCase
{
    /**
     * @dataProvider ranks
     */
    public function test_the_rank_follows_the_xp_borders($xp, $rank)
    {
        $user = (new User())->forceFill(['xp' => $xp]);

        $this->assertSame($rank, $user->rank());
    }

    public function ranks(): array
    {
        return [
            'new player' => [0, 'Quiz Aprentice'],
            'last Aprentice' => [1499, 'Quiz Aprentice'],
            'first Average' => [1500, 'Average Quizer'],
            'last Average' => [4999, 'Average Quizer'],
            'first Epic' => [5000, 'Epic Quizer'],
            'last Epic' => [9999, 'Epic Quizer'],
            'first Master' => [10000, 'Quiz Master'],
            'far above' => [250000, 'Quiz Master'],
            'xp read as text' => ['1500', 'Average Quizer'],
        ];
    }
}
