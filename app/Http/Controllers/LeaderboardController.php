<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    public function index()
    {
        return view('leaderboard', ['players' => User::topPlayers()]);
    }
}
