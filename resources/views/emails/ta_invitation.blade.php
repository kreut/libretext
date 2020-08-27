@extends('beautymail::templates.sunny')

@section('content')

  @include ('beautymail::templates.sunny.heading' , [
      'heading' => 'Inviation to Grade',
      'level' => 'h1',
  ])

  @include('beautymail::templates.sunny.contentStart')

  <p>You've just been invted to be a grader for some cool course with some cool instructor. Please sign
  up below using the access code {{ $access_code }} and visiting the link below.</p>

  @include('beautymail::templates.sunny.contentEnd')

  @include('beautymail::templates.sunny.button', [
        'title' => 'Sign Up',
        'link' => 'http://google.com'
  ])

@stop
