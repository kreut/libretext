@extends('beautymail::templates.sunny')

@section('content')

  @include ('beautymail::templates.sunny.heading' , [
      'heading' => 'Pending Beta Course Approvals',
      'level' => 'h1',
  ])

  @include('beautymail::templates.sunny.contentStart')

  <p>Hi {{ $first_name }},</p>
  <p>The following assignments have assessments created/removed by a tethered Alpha course and need your approval.
  If you are logged into Adapt, then you can click on the links to go directly to the Tethered Assignments section.</p>
  <ul>
    {!! $pending_approvals !!}
  </ul>
  <p>
    -Adapt Support
  </p>
  @include('beautymail::templates.sunny.contentEnd')

@stop

