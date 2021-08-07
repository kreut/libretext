@extends('beautymail::templates.sunny')

@section('content')

  @include ('beautymail::templates.sunny.heading' , [
      'heading' => 'Refresh Question Approval',
      'level' => 'h1',
  ])

  @include('beautymail::templates.sunny.contentStart')
  <p>Hi {{$first_name}}, </p>
  <p>
    An instructor would like to refresh a question which already has student submissions.
    Assuming you are logged in, you can accept or deny the submission <a href="{{$refresh_question_approval_link}}">here</a>.
  </p>
  <p>-Adapt Support</p>
  @include('beautymail::templates.sunny.contentEnd')


@stop
