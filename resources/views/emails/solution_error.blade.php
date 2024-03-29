@extends('beautymail::templates.sunny')

@section('content')

  @include ('beautymail::templates.sunny.heading' , [
      'heading' => 'Solution Error',
      'level' => 'h1',
  ])

  @include('beautymail::templates.sunny.contentStart')
  <p>Hi,</p>
  <p>
    {{$instructor}} with email {{$email}} found a problem with the solution associated with Libretexts
    ID {{$libretexts_id}}:</p>
  <div>{!! $text !!}</div>
  <p>You can view the full question and solution <a href="{{$url}}">here</a>.
  </p>
  <p>-ADAPT Support</p>
  @include('beautymail::templates.sunny.contentEnd')


@stop
