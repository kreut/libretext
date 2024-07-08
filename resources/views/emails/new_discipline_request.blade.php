@extends('beautymail::templates.sunny')

@section('content')

  @include ('beautymail::templates.sunny.heading' , [
      'heading' => 'New Discipline Request',
      'level' => 'h1',
  ])

  @include('beautymail::templates.sunny.contentStart')

  <p>Hi,</p>
  <p>{{$requested_by}} would like you to add the discipline <strong>{{$discipline}}</strong> to ADAPT.</p>
  <p>You may reply to this email to directly contact the instructor.</p>
  -ADAPT Support
  </p>
  @include('beautymail::templates.sunny.contentEnd')

@stop

