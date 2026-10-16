@extends('app')

@section('content')

@if ($quiz)
<form action="{{ route('quiz', $quiz) }}" method="post">
    @csrf

    @foreach ($quiz['questions'] as $question)
    <x-question :question="$question" />
    @endforeach

    <button type="submit" class="center green-btn">Submit</button>
</form>
@else
<a class="top-right-corner red-btn" href="{{ route('home') }}">Back ></a>

<p class="center title">No questions are available yet. Please try again later.</p>
@endif


@endsection
