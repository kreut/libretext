@extends('beautymail::templates.sunny')

@section('content')

  @include ('beautymail::templates.sunny.heading' , [
      'heading' => 'ADAPT Registration',
      'level' => 'h1',
  ])

  @include('beautymail::templates.sunny.contentStart')
  <p>Hi {{$first_name}}, </p>
  <p>You are about to start the course "{{$course_name}}", taught by "{{$instructor_name}}".
    The homework for your course is being served through your school's
    Learning Management System (LMS) and once your course begins, you will be able to access your homework by logging
    into your LMS.</p>
  <p>In addition, you may access it directly through ADAPT, the online homework system used to deliver your homework
    problems. Please first create a new
    password in order to log into ADAPT by visiting <a style="color:blue" href="https://adapt.libretexts.org/">ADAPT</a>, clicking on Log
    In/Register and then
    using the "Reset Password Link" with your school email address: <strong>{{$to_email}}</strong>.</p>
  @if (!$single_section_course && !$already_enrolled)
    <p>If they have not already done so, your instructor will provide you with an access code that you'll be able to
      then
      use to then have full access to the homework system.</p>
  @endif
  <p>Any replies to this email will be directly sent to your instructor.</p>
  <p>-ADAPT Support</p>
  @include('beautymail::templates.sunny.contentEnd')
@stop
