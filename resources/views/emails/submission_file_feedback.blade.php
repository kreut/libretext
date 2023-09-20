@extends('beautymail::templates.sunny')

@section('content')

  @include ('beautymail::templates.sunny.heading' , [
      'heading' => 'Submission Feedback',
      'level' => 'h1',
  ])

  @include('beautymail::templates.sunny.contentStart')
  @php
    @endphp
  <p>Hi,</p>
  <p>Below you can find written feedback for your submissions. If you are logged into ADAPT, then you can visit the
    questions
    directly by clicking on each question number. If you have any questions about these comments, please reach out to your
    instructor as replies to
    this email will not be answered.</p>
  {!! $formatted_text !!}

  <p>-ADAPT Support</p>
  @include('beautymail::templates.sunny.contentEnd')

@stop
