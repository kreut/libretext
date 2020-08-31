@extends('beautymail::templates.sunny')

@section('content')

  @include ('beautymail::templates.sunny.heading' , [
      'heading' => 'Invitation to Grade',
      'level' => 'h1',
  ])

  @include('beautymail::templates.sunny.contentStart')

  <p>{{ $instructor  }} has just invited you to be a grader for <strong>{{  $course }}</strong>. Please sign
    up below by using the access code <strong>{{ $access_code }}</strong>.</p>

  @include('beautymail::templates.sunny.contentEnd')

  @include('beautymail::templates.sunny.button', [
        'title' => 'Sign Up',
        'link' =>  $link
  ])

@stop
