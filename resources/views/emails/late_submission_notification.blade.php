@extends('beautymail::templates.sunny')

@section('content')

  @include ('beautymail::templates.sunny.heading' , [
      'heading' => 'Late Submission',
      'level' => 'h1',
  ])

  @include('beautymail::templates.sunny.contentStart')

  <p>Hi {{ $first_name }},</p>
  <p>In the past 24 hours, there have been late submissions for the following assignments/questions: </p>
  <ul>
    {!! $formatted_ungraded_submissions_by_grader !!}
  </ul>
  <p>
  -ADAPT Support
    </p>
  @include('beautymail::templates.sunny.contentEnd')

@stop
