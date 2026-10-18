@extends('app')

@section('content')

<a class="top-right-corner red-btn" href="{{ route('home') }}">Back ></a>

<div style="margin-top:100px">
    <div class="profile-header">
        <p class="title profile-name">{{ auth()->user()->username }}</p>
        <p class="title profile-email">{{ auth()->user()->email }}</p>
    </div>

    <div class="profile-header">
        <p class="title profile-xp">{{ auth()->user()->xp }} XP</p>
        <p class="title profile-rank">{{ auth()->user()->rank() }}</p>
    </div>

    <table class="profile-table">
        <thead>
            <tr>
                <th>Category</th>
                <th>Right answers</th>
                <th>Questions answered</th>
                <th>Percentage</th>
            </tr>
        </thead>
        <tbody>
            @foreach (['Art' => $art, 'Geography' => $geography, 'History' => $history, 'Science' => $science, 'Sports' => $sports] as $name => $score)
            <tr>
                <td>{{ $name }}</td>
                <td>{{ $score['right'] }}</td>
                <td>{{ $score['answered'] }}</td>
                <td>{{ $score['percentage'] }}%</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>



@endsection