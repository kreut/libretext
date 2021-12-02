@extends('beautymail::templates.sunny')

@section('content')

  @include ('beautymail::templates.sunny.heading' , [
      'heading' => 'Upcoming Assignment Due',
      'level' => 'h1',
  ])

  @include('beautymail::templates.sunny.contentStart')

  <p>Hi {{ $student_first_name }},</p>
  <p>Just a friendly reminder that the assignment <strong>{{  $assignment }}</strong> from the course
    <strong>{{ $course }}</strong> is due in {{ $hours_until_due }}. And, if you're already logged into ADAPT in the
    current browser,
    <a href="{{$assignment_link}}">this link</a> will take you directly to the assignment.</p>
  <p>-ADAPT Support</p>
  <p><strong>This is an automatically generated email. Please do not respond as your email will go unanswered.</strong>
  </p>
  @include('beautymail::templates.sunny.contentEnd')

@stop
