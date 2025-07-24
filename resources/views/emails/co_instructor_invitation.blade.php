@extends('beautymail::templates.sunny')

@section('content')

  @include ('beautymail::templates.sunny.heading' , [
      'heading' => 'Co-Instructor Invitation',
      'level' => 'h1',
  ])

  @include('beautymail::templates.sunny.contentStart')
<p>Hi,</p>
  <p>{{ $instructor  }} has just invited you to be a co-instructor for <strong>{{  $course_name }}</strong>.</p>
  <p>You can accept the invitation by visiting <strong><span style="color: cornflowerblue">{{ $accept_co_instructor_invitation_link }}</span></strong>.</p>

  <p>-ADAPT Support</p>
  @include('beautymail::templates.sunny.contentEnd')


@stop

