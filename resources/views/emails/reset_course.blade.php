@extends('beautymail::templates.sunny')

@section('content')

  @include ('beautymail::templates.sunny.heading' , [
        'heading' => 'Reset Course',
        'level' => 'h1',
    ])

  @include('beautymail::templates.sunny.contentStart')
  <p>Hi {{$first_name}},</p>
  <p>The course {{$name }} concluded {{$num_days}} days ago. If you no longer need ADAPT to save your students'
    enrollments and
    submissions please log in and then reset the course.</p>
  <p>Please note that as part of our commitment to students' data, courses that have ended 100 days ago will
    automatically be reset by ADAPT.</p>
  <p>-ADAPT Support</p>
  @include('beautymail::templates.sunny.contentEnd')
  @include('beautymail::templates.sunny.button', [
        'title' => "Reset $name",
        'link' =>  $reset_course_link
  ])


@stop
