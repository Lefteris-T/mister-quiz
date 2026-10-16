@props(['question'=>$question, 'error' => null])

<div class="mb4{{ $error ? ' unanswered' : '' }}">
    <p class="center title">{{ $question->question }}</p>

    @if ($error)
    <p class="center error-msg">{{ $error }}</p>
    @endif

    <div class="checkboxes-wrapper" class="center">
        @foreach ($question->answers as $answer)
        <div class="checkbox">
            <label>
                <input type="radio" name="answers[{{ $question->id }}]" value="{{ $answer->id }}"@if ((string) old('answers.' . $question->id) === (string) $answer->id) checked @endif>
                <span>{{ $answer->answer }}</span>
            </label>
        </div>
        @endforeach
    </div>

    <div class="center line"></div>
</div>