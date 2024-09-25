@extends('beautymail::templates.sunny')

@section('content')

  @include ('beautymail::templates.sunny.heading' , [
      'heading' => 'Course Invitation',
      'level' => 'h1',
  ])

  @include('beautymail::templates.sunny.contentStart')

  <h1>Hi {{ $first_name }},</h1>
  <p>You have been invited to join the course <strong>{{$course_name}} ({{$section_name}})</strong> taught
    by {{$instructor_name}}. You can enroll in the
    course using the following access code:</p>
  <p><strong>{{$access_code}}</strong></p>
  <p>This code is valid for 48 hours.</p>
  <br>
  <p>-ADAPT support</p>
  @include('beautymail::templates.sunny.contentEnd')

@stop
