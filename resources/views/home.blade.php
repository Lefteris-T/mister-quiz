@extends('app')

@section('content')

@auth
<a class="top-left-corner blue-btn" href="{{ route('profile') }}">Profile</a>
@endauth

@guest
<a class="top-left-corner blue-btn" href="{{ route('login') }}">Login</a>

@endguest

<a class="top-right-corner blue-btn" href="{{ route('leaderboard') }}">Leaderboard</a>

@auth
<form action="{{ route('logout') }}" method="POST">
    @csrf
    <button
        type="submit"
        class="bottom-right-corner red-btn"
        style="cursor: pointer;"
    >Logout</button>
</form>
@endauth

<div class="main-img">
    <img src="{{ asset('images/mister_quiz.png') }}" alt="">
    <p class="title">Mister Quiz</p>

    <a style="margin-bottom:20px" class="green-btn center" href="{{ route('quiz') }}">Start Quiz</a>
</div>

@endsection