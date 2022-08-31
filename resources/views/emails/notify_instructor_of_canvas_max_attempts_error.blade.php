@extends('beautymail::templates.sunny')

@section('content')

  @include ('beautymail::templates.sunny.heading' , [
      'heading' => 'ADAPT to Canvas grade passback failed',
      'level' => 'h1',
  ])

  @include('beautymail::templates.sunny.contentStart')
  <p>Hi {{$first_name}}, </p>
  <p>
    ADAPT is unable to send grades back to Canvas for the following assignment{{$plural}}:
  </p>
  <p><strong>{!!  $course_assignment !!}</strong>
  </p>
  <p>ADAPT passes grades back to Canvas after each submission.  This means that if the number of questions in an assignment is greater than
    the number of Submission Attempts allowed by Canvas,
    grade passback will fail.</p>
  <p>To fix this, please go to Canvas and change the assignment Submission Attempts to Unlimited. Note that ADAPT can control the number of submissions per question by changing this
    value in your Assignment Properties page.</p>
  <p>Finally, please go to the ADAPT course gradebook to find your students' scores for this assignment and manually
    update them in Canvas to fix the grades for this assignment.</p>

  <p>-ADAPT Support</p>
  @include('beautymail::templates.sunny.contentEnd')
@stop
