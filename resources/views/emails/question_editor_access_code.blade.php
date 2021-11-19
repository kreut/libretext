@extends('beautymail::templates.sunny')

@section('content')

  @include ('beautymail::templates.sunny.heading' , [
      'heading' => 'Question Editor Access Code',
      'level' => 'h1',
  ])

  @include('beautymail::templates.sunny.contentStart')
  <p>Hi,</p>
  <p>
    You have been invited to be a Question Editor for Adapt.  Your access code is <strong>{{$access_code}}</strong>.
    To finish the registration process, please visit <a href="{{$access_code_link}}">this link</a> within
    the next 48 hours.
  </p>
  <p>-Adapt Support</p>
  @include('beautymail::templates.sunny.contentEnd')


@stop
