@extends('beautymail::templates.sunny')

@section('content')

  @include ('beautymail::templates.sunny.heading' , [
      'heading' => 'Score Results',
      'level' => 'h1',
  ])

  @include('beautymail::templates.sunny.contentStart')

  <p>Hi {{ $instructor_first_name }},</p>
  <p>{{$tester}} would like you to see the testing results for {{$student}} in your course {{$course}}. They
    submitted {{$number_of_responses}} responses and scored
    a {{$score}}.</p>
  <p>You may reply-to this email to directly contact the tester.</p>
  -ADAPT Support
  </p>
  @include('beautymail::templates.sunny.contentEnd')

@stop

