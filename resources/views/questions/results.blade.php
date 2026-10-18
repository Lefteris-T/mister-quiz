@extends('app')

@section('content')
<main class="results-page">
    <nav class="results-nav" aria-label="Results navigation">
        <a class="blue-btn" href="{{ route('home') }}">Home</a>
        <a class="blue-btn" href="{{ route('profile') }}">Profile</a>
    </nav>

    <header class="results-summary">
        <h1 class="title">Results</h1>
        <p>Correct answers out of total questions</p>
        <p class="results-score">{{ $results['overall']['correct'] }} / {{ $results['overall']['total'] }}</p>
    </header>

    <section class="results-wrapper" aria-label="Results by category">
        @foreach (['Art' => 'art', 'Geography' => 'geography', 'History' => 'history', 'Science' => 'science', 'Sports' => 'sports'] as $label => $category)
            <section class="result">
                <h2>{{ $label }}</h2>
                <p class="result-score">{{ $results[$category]['correct'] }} / {{ $results[$category]['total'] }}</p>
            </section>
        @endforeach
    </section>
</main>
@endsection
