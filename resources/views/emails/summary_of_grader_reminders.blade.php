@extends('beautymail::templates.sunny')

@section('content')

  @include ('beautymail::templates.sunny.heading' , [
      'heading' => 'Summary of Ungraded Assignments',
      'level' => 'h1',
  ])

  @include('beautymail::templates.sunny.contentStart')

  <p>Hi {{ $first_name }},</p>
  <p>Here is a summary of all assignments which need to be graded: </p>


  <ul>
    {!! $formatted_ungraded_submissions_by_course !!}
  </ul>
  <p>
    -ADAPT Support
  </p>
  @include('beautymail::templates.sunny.contentEnd')

@stop

