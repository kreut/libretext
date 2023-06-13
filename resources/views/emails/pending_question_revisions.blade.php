@extends('beautymail::templates.sunny')

@section('content')

  @include ('beautymail::templates.sunny.heading' , [
      'heading' => 'Pending Question Revisions',
      'level' => 'h1',
  ])

  @include('beautymail::templates.sunny.contentStart')

  <p>Hi {{ $first_name }},</p>
  <p>The following questions exist in your assignments have been revised.
    Please log into ADAPT and then visit each of the links to below
    to either accept the revision or to leave the question as is. Future imports of this question will use the most
    up-to-date revision.</p>

    <h3>Pending Question Revisions</h3>
    <ul>
      @foreach ($pending_question_revisions as $item)
        <li>
          {{$item['course_name'] }}:{{$item['assignment_name']}} <a href="{{$item['url']}}">{{$item['url']}}</a>
        </li>
      @endforeach
    </ul>
  <p>
    -ADAPT Support
  </p>
  @include('beautymail::templates.sunny.contentEnd')

@stop

