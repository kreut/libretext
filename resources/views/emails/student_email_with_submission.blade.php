@extends('beautymail::templates.sunny')

@section('content')

  @include ('beautymail::templates.sunny.heading' , [
      'heading' => 'Re-submit Assignment Question',
      'level' => 'h1',
  ])

  @include('beautymail::templates.sunny.contentStart')

  {!! $email_message !!}
  <p><strong>You can respond to this email and it will be sent directly to your instructor.</strong>
  </p>
  @include('beautymail::templates.sunny.contentEnd')

@stop
