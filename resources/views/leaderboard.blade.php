@extends('app')

@section('content')

<a class="top-right-corner red-btn" href="{{ route('home') }}">Back ></a>

<div style="margin-top:100px">
    <p class="title">Leaderboard</p>

    @if ($players->isEmpty())
    <p class="leaderboard-empty">No players yet.</p>
    @else
    <table class="leaderboard-table">
        <thead>
            <tr>
                <th>Username</th>
                <th>XP</th>
                <th>Total right answers</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($players as $player)
            <tr>
                <td>{{ $player->username }}</td>
                <td>{{ $player->xp }}</td>
                <td>{{ $player->totalRight() }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>

@endsection
