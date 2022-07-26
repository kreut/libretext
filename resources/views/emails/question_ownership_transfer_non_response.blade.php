@extends('beautymail::templates.sunny')

@section('content')

  @include ('beautymail::templates.sunny.heading' , [
      'heading' => 'Question Ownership Non-Response',
      'level' => 'h1',
  ])

  @include('beautymail::templates.sunny.contentStart')
  <p>Hi {{$old_owner_name}}, </p>
  <p>
    {{$new_owner_user_info}} has not responded to the request for a transfer of ownership for the following questions:
  </p>
  <p>{!! $questions_to_transfer_html !!}</p>
  <p>Since the request was made over 24 ago, the pending request will no longer be valid.</p>
  <p>-ADAPT Support</p>
  @include('beautymail::templates.sunny.contentEnd')
@stop
