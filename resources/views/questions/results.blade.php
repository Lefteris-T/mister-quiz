@extends('app')

@section('content')

<a class="top-left-corner blue-btn" href="{{ route('profile') }}"> {{auth()->user()->username}} </a>

<a class="top-right-corner blue-btn" href="{{ route('home') }}">
    < Home</a>

        <div class="center text-center content">
            <div>
                <p class="title">Your score was</p>
                <p class="title" style="font-size:70px; font-style:bold;">
                   {{ $results['overall']['correct'] }} / {{ $results['overall']['total'] }}
                </p>
            </div>

            <div class="results-wrapper">
                <div class="result">
                    <p>Art</p>
                    <p class="title">{{ $results['art']['correct'] }} / {{ $results['art']['total'] }}</p>
                </div>
                <div class="result">
                    <p>Geography</p>
                    <p class="title">{{ $results['geography']['correct'] }} / {{ $results['geography']['total'] }}</p>
                </div>
                <div class="result">
                    <p>History</p>
                    <p class="title">{{ $results['history']['correct'] }} / {{ $results['history']['total'] }}</p>
                </div>
                <div class="result">
                    <p>Science</p>
                    <p class="title">{{ $results['science']['correct'] }} / {{ $results['science']['total'] }}</p>
                </div>
                <div class="result">
                    <p>Sports</p>
                    <p class="title">{{ $results['sports']['correct'] }} / {{ $results['sports']['total'] }}</p>
                </div>

            </div>
        </div>

        @endsection