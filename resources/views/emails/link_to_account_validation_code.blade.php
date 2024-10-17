@extends('beautymail::templates.sunny')

@section('content')

  @include ('beautymail::templates.sunny.heading' , [
      'heading' => 'Validation code to Link Accounts',
      'level' => 'h1',
  ])

  @include('beautymail::templates.sunny.contentStart')
  <p>Hi {{$first_name}},</p>
  <p>
    Please use the following validation code to link your two accounts: <strong>{{$validation_code}}</strong>.
    This code will expire 10 minutes.
  </p>
  <p>If you did not make this request, please contact us.</p>
  <p>-ADAPT Support</p>
  @include('beautymail::templates.sunny.contentEnd')

  @include('beautymail::templates.sunny.contentEnd')

@stop
