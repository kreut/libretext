@extends('beautymail::templates.sunny')

@section('content')

  @include ('beautymail::templates.sunny.heading' , [
      'heading' => 'LTI Registration',
      'level' => 'h1',
  ])

  @include('beautymail::templates.sunny.contentStart')
  <p>Hi, </p>
  <p>
    Thank you for registering your Canvas LTI information with ADAPT. At this point, you'll need to add ADAPT as an
    external app:
  </p>
  <iframe width="560" height="315" src="https://www.youtube.com/embed/0rhJ7CfiF04" title="YouTube video player"
          frameborder="0"
          allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
          allowfullscreen
  ></iframe>
  <p>Then, instructors should click "Yes" for LMS in their course properties to enable the LTI grade passback feature.
    Once a course
    is given this capability an instructor can set up their assignments:</p>
  <iframe width="560" height="315" src="https://www.youtube.com/embed/mwptJHUjY6w" title="YouTube video player"
          frameborder="0"
          allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
          allowfullscreen
  ></iframe>
  <p>Please get in touch with any questions.</p><p>-ADAPT Support</p>
  @include('beautymail::templates.sunny.contentEnd')

@stop
