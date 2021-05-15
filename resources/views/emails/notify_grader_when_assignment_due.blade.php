@extends('beautymail::templates.sunny')

@section('content')

  @include ('beautymail::templates.sunny.heading' , [
      'heading' => 'Assignment Ready For Grading',
      'level' => 'h1',
  ])

  @include('beautymail::templates.sunny.contentStart')

  <p>The assignment <strong>{{ $assignment_name  }}</strong> from the course  <strong>{{  $course_name  }}</strong> has just become due for students in one of your sections.</p>
  <p> If you already logged in, then you can just visit the <a href="{{$grading_link}}">grading page directly</a> and begin grading.</p>

  @include('beautymail::templates.sunny.contentEnd')


@stop
