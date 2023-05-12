@extends('beautymail::templates.sunny')

@section('content')

  @include ('beautymail::templates.sunny.heading' , [
      'heading' => 'Pending Question Revisions',
      'level' => 'h1',
  ])

  @include('beautymail::templates.sunny.contentStart')

  <p>Hi {{ $first_name }},</p>
  <p>The following questions exist in your assignments have been significantly revised.
    Please log into ADAPT and then visit each of the links to below
    to either accept the revision or to leave the question as is. Future imports of this question will use the most
    up-to-date revision.</p>
  @if (count($current))
    <h3>Questions that exist in current assignments</h3>
    <ul>
      @foreach ($current as $item)
        <li>
          {{$item['course_name'] }}:{{$item['assignment_name']}} <a href="{{$item['url']}}">{{$item['url']}}</a>
        </li>
      @endforeach
    </ul>
  @endif
  @if (count($upcoming))
    <h4>Questions that exist in upcoming assignments</h4>
    <ul>
      @foreach ($upcoming as $item)
        <li>
          {{$item['course_name'] }}: {{$item['assignment_name']}}<a href="{{$item['url']}}">{{$item['url']}}</a>
        </li>
      @endforeach
    </ul>
  @endif
  <p>
    -ADAPT Support
  </p>
  @include('beautymail::templates.sunny.contentEnd')

@stop

