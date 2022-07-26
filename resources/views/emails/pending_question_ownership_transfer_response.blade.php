@extends('beautymail::templates.sunny')

@section('content')

  @include ('beautymail::templates.sunny.heading' , [
      'heading' => 'Question Ownership Response',
      'level' => 'h1',
  ])

  @include('beautymail::templates.sunny.contentStart')
  <p>Hi {{$old_owner_name}}, </p>
  <p>
    {{$new_owner_user_info}} has {{$action}}ed the transfer of ownership for the following questions:
  </p>
  <p>{!!  $questions_to_transfer_html!!}</p>
  <p>No further action is needed on your part.</p>
  <p>-ADAPT Support</p>
  @include('beautymail::templates.sunny.contentEnd')
@stop
