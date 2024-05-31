@extends('beautymail::templates.sunny')

@section('content')

  @include ('beautymail::templates.sunny.heading' , [
      'heading' => 'Transcription Results',
      'level' => 'h1',
  ])

  @include('beautymail::templates.sunny.contentStart')

  <p>Hi {{ $first_name }},</p>
  @if($success)
    <p>
      Your transcription for {{$original_filename}} with ADAPT question ID {{$question_id}} has been completed. If you are logged into
      ADAPT,
      you can <a href="{{$url}}">view/edit</a> the transcript.</p>
  @else
    <p>There was an error creating the transcription for {{$original_filename}} with ADAPT question ID {{$question_id}} has been
      completed. Please reach out to support for assistance.</p>
  @endif
  <p>-ADAPT Support</p>
  <p><strong>This is an automatically generated email. Please do not respond as your email will go unanswered.</strong>
  </p>
  @include('beautymail::templates.sunny.contentEnd')

@stop
