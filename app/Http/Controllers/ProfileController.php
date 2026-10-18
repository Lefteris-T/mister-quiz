<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function index()
    {

        $user = Auth()->user();

        //variables that will be used to display user score in each category
        $art = $user->categoryScore('art');
        $geography = $user->categoryScore('geography');
        $history = $user->categoryScore('history');
        $science = $user->categoryScore('science');
        $sports = $user->categoryScore('sports');

        return view('profile', ['art' => $art, 'geography' => $geography, 'history' => $history, 'science' => $science, 'sports' => $sports]);
    }
}
