@extends('app')

@section('content')

@if ($quiz)
<form action="{{ route('quiz.submit', $quiz) }}" method="post">
    @csrf

    @if ($errors->has('quiz'))
    <div class="center error-msg mb4" role="alert">
        {{ $errors->first('quiz') }}
    </div>
    @endif

    @foreach ($quiz['questions'] as $question)
    <x-question :question="$question" :error="$errors->first('answers.' . $question->id)" />
    @endforeach

    <button type="submit" class="center green-btn">Submit</button>
</form>
@else
<a class="top-right-corner red-btn" href="{{ route('home') }}">Back ></a>

<p class="center title">No questions are available yet. Please try again later.</p>
@endif


@endsection
