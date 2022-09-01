@extends('beautymail::templates.sunny')

@section('content')

  @include ('beautymail::templates.sunny.heading' , [
      'heading' => 'Email Changed',
      'level' => 'h1',
  ])

  @include('beautymail::templates.sunny.contentStart')

  <p>Hi {{ $student_first_name }},</p>
  <p>Your instructor {{$instructor_name}} has changed your ADAPT log in email to this one.  Please use it when logging into your ADAPT account.</p>
  <p>
    -ADAPT Support
  </p>
  @include('beautymail::templates.sunny.contentEnd')

@stop

