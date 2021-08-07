@extends('beautymail::templates.sunny')

@section('content')

  @include ('beautymail::templates.sunny.heading' , [
      'heading' => 'Refresh Question Approval',
      'level' => 'h1',
  ])

  @include('beautymail::templates.sunny.contentStart')
  <p>Hi {{$first_name}}, </p>
  <p>
    Unfortunately your request to refresh the question with id {{$question_id}} has been denied since the changes
    were not purely cosmetic in nature.  We recommend that you remove the question from your assignment and create another one.
  </p>
  <p>-Adapt Support</p>
  @include('beautymail::templates.sunny.contentEnd')


@stop
