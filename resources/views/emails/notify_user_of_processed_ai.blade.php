@extends('beautymail::templates.sunny')

@section('content')

  @include ('beautymail::templates.sunny.heading' , [
      'heading' => 'Processing of New Criteria',
      'level' => 'h1',
  ])

  @include('beautymail::templates.sunny.contentStart')
  <p>Hi {{$name}},</p>
  <p>
    The AI has finished processing the feedback for your updated criteria for "{{$category}}" as part of the assignment
    "{{$assignment}}"
    within the course "{{$course}}". If you are logged in, then you can visit the grading page directly by visiting:</p>
  <p><a
      href="{{$url}}"
    >{{$url}}</a>.
  </p>
  <p>-ADAPT Support</p>
  @include('beautymail::templates.sunny.contentEnd')

@stop
