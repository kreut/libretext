@extends('beautymail::templates.sunny')

@section('content')

  @include ('beautymail::templates.sunny.heading' , [
      'heading' => 'Question Ownership Request',
      'level' => 'h1',
  ])

  @include('beautymail::templates.sunny.contentStart')
  <p>Hi {{$new_owner_name}}, </p>
  <p>
    {{$old_owner_info}} would like to transfer their ownership of the following question(s):
  </p>
  <p>{!! $questions_to_transfer_html !!}</p>
  <p>Please choose from the following actions by clicking on the links or pasting the URL's into your browser.</p>
  <p>Accept:  <a href="{{$action_url}}/accept/{{$token}}">{{$action_url}}/accept/{{$token}}</a></p>
  <p>Reject:  <a href="{{$action_url}}/reject/{{$token}}">{{$action_url}}/reject/{{$token}}</a></p>
  <p>If you accept the ownership transfer, then you'll be able to find these questions in your Transferred Questions folder. If you take no action within 24 hours of the initial request, the owner will have to re-send the request to you.</p>
  <p>-ADAPT Support</p>
  @include('beautymail::templates.sunny.contentEnd')


@stop
