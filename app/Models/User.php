<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'username',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function quizes()
    {
        return $this->hasMany(Quiz::class);
    }

    /**
     * The rank for the XP of the user. Where the subject ranges overlap
     * (5000 and 10000), the higher rank wins. See issue #32.
     */
    public function rank(): string
    {
        if ($this->xp >= 10000) {
            return 'Quiz Master';
        }

        if ($this->xp >= 5000) {
            return 'Epic Quizer';
        }

        if ($this->xp >= 1500) {
            return 'Average Quizer';
        }

        return 'Quiz Aprentice';
    }

    /**
     * The score of one category column (art, geography, history, science or
     * sports), which is stored as the text "right/answered". The percentage
     * is a whole number, and it is 0 when nothing was answered. See issue #33.
     */
    public function categoryScore(string $category): array
    {
        $right = 0;
        $answered = 0;

        if (preg_match('/^(\d+)\/(\d+)$/', (string) $this->getAttribute($category), $parts)) {
            $right = (int) $parts[1];
            $answered = (int) $parts[2];
        }

        return [
            'right' => $right,
            'answered' => $answered,
            'percentage' => $answered > 0 ? (int) round($right / $answered * 100) : 0,
        ];
    }

    /**
     * The total right answers: the sum of the right answers (the x of "x/y")
     * of the five categories. No extra column, as decided in issue #3.
     */
    public function totalRight(): int
    {
        $total = 0;

        foreach (['art', 'geography', 'history', 'science', 'sports'] as $category) {
            $total += $this->categoryScore($category)['right'];
        }

        return $total;
    }

    /**
     * The best players for the leaderboard: XP from high to low, then username
     * from A to Z, then id. The id decides when two players have the same name
     * and the same XP, so the order never changes between reloads (issue #35).
     */
    public static function topPlayers(int $limit = 10)
    {
        return static::orderByDesc('xp')
            ->orderBy('username')
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }
}
