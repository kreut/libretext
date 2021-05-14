@extends('beautymail::templates.sunny')

@section('content')

  @include ('beautymail::templates.sunny.heading' , [
      'heading' => 'Grading Reminder',
      'level' => 'h1',
  ])

  @include('beautymail::templates.sunny.contentStart')

  <p>Hi {{ $first_name }},</p>
  <p>Please grade the following assignments/questions: </p>


  <ul>
    {!! $formatted_ungraded_submissions_by_grader !!}
  </ul>
  <p>
    -Adapt Support
  </p>
  @include('beautymail::templates.sunny.contentEnd')

@stop

