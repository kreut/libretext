@extends('beautymail::templates.sunny')

@section('content')

  @include ('beautymail::templates.sunny.heading' , [
      'heading' => 'Upcoming Assignment Due',
      'level' => 'h1',
  ])

  @include('beautymail::templates.sunny.contentStart')
  <p>Hi,</p>
  <p> Using ADAPT’s assignment settings, you can control how many times students may submit.
    Currently, the <strong>Number of Attempts</strong> in your Canvas assignment settings for the ADAPT assignment
    <strong>{{$course_name}}: {{$assignment_name}}</strong> is set to something other than <em>Unlimited</em>. </p>
  <p> Please update this setting in Canvas so that submissions are set to <em>Unlimited</em>.
    This is required because every score ADAPT sends back after a student answers a question is
    treated by Canvas as a new “assignment submission.” If submissions aren’t unlimited,
    Canvas will stop accepting scores once the maximum number is reached. </p> <p> After updating this setting in
    Canvas,
    you can go to the assignment settings in ADAPT (<a href="{{$url}}" style="color:blue">{{$url}}</a>) to resend the
    grades back to
    Canvas.
  </p>
  <p>-ADAPT Support</p>
  <p><strong>This is an automatically generated email. Please do not respond as your email will go unanswered.</strong>
  </p>
  @include('beautymail::templates.sunny.contentEnd')

@stop
