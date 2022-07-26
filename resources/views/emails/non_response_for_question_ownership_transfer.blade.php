@extends('beautymail::templates.sunny')

@section('content')

  @include ('beautymail::templates.sunny.heading' , [
      'heading' => 'Non-Response for Question Ownership Transfer',
      'level' => 'h1',
  ])

  @include('beautymail::templates.sunny.contentStart')
  <p>Hi {{$old_owner_name}}, </p>
  <p>
    Because {{$new_owner_user_info}} has not responded to the email for the transfer of ownership for the following questions:
  </p>
  {{ $questions_to_transfer_html}}
  <p>we have removed the pending request from our database. You can always re-visit your Meta-tags page and re-send a request to change ownership.</p>
  <p>-ADAPT Support</p>
  @include('beautymail::templates.sunny.contentEnd')
@stop
