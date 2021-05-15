@extends('beautymail::templates.sunny')

@section('content')

  @include ('beautymail::templates.sunny.heading' , [
      'heading' => 'Late Submission',
      'level' => 'h1',
  ])

  @include('beautymail::templates.sunny.contentStart')

  <p>Hi {{ $first_name }},</p>
  <p>There has been at least one late submission in the past 24 hours for one of the sections that you are grading.
    Please visit the grading page for: </p>


  <ul>
    @foreach ($late_submissions_by_grader as $late_submissions_by_section)
      @foreach ($late_submissions_by_section as $late_submission)
        <li>Course: {{$late_submission->course_name}},
          Assignment: {{$late_submission->assignment_name}},
          Section: {{$late_submission->section_name}}</li>
      @endforeach
    @endforeach
  </ul>

  <p><
  -Adapt Support
    </p>
  @include('beautymail::templates.sunny.contentEnd')

@stop
