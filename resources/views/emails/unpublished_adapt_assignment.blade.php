@extends('beautymail::templates.sunny')

@section('content')

  @include ('beautymail::templates.sunny.heading' , [
        'heading' => 'Unreleased ADAPT Course',
        'level' => 'h1',
    ])

  @include('beautymail::templates.sunny.contentStart')
  <p>Hi,</p>
  <p>The assignment "{{$assignment_name}}" in the course "{{$course_name}}" is not currently released within ADAPT and your students
    have been provided access to it.  Please go to your course and click on "Manual" under the Released Status column.  When the row
    turns green they will be abel to view the assignment.</p>
  <p>If you are logged in, then you can visit the courses page directly using <a href="{{$url}}">{{$url}}</a>.
  <p>-ADAPT Support</p>
  @include('beautymail::templates.sunny.contentEnd')

@stop
