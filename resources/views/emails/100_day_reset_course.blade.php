@extends('beautymail::templates.sunny')

@section('content')

  @include ('beautymail::templates.sunny.heading' , [
        'heading' => '100 Day Old Courses',
        'level' => 'h1',
    ])

  @include('beautymail::templates.sunny.contentStart')
  <p>Hi Delmar,</p>
  <p>The course {{$name}} with instructor {{ $instructor  }} concluded 100 days ago. If you are already logged
    in, then clicking on the button below will take you directly
    to the Course Reset page.
  <p>-Automated email from Eric</p>
  @include('beautymail::templates.sunny.contentEnd')
  @include('beautymail::templates.sunny.button', [
        'title' => "Reset 100 Day Old Courses",
        'link' =>  $courses_to_reset_link
  ])


@stop
