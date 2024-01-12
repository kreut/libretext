@extends('beautymail::templates.sunny')

@section('content')

  @include ('beautymail::templates.sunny.heading' , [
        'heading' => 'Canvas API Updates',
        'level' => 'h1',
    ])

  @include('beautymail::templates.sunny.contentStart')
  <p>Hi {{$name}},</p>
  <p>As you might be aware, this is the first semester where we're rolling out the Canvas API. And, it looks like you
    are using the
    Canvas API in:</p>
  <ul>
    @foreach ($courses as $course)
      <li>
        {{$course}}</a>
      </li>
    @endforeach
  </ul>
  <p>I've made 2 adjustments to the API that I wanted you to be aware of. First, I was using the course start and end
    dates as the
    unlocked and due dates for every assignment in Canvas. I've now updated the code so that it will take any
    "Everybody" assign to from ADAPT
    and send over that as the "Everybody" timings (unlocked and due dates) in Canvas. This will help minimize confusion
    between non-matching dates
    in Canvas and ADAPT. Note that I still have to implement the correct logic for multiple sections or assigning
    overrides. At this point, if you create
    an assign to other than Everybody in ADAPT, you'll need to manually add that set of timings to Canvas.</p>
  <p>Second, for those of you who copied over courses, depending on how you did it, you may see that your assignments in
    Canvas all equal 100 points.
    This was not intentional on my part and I've written code so that the points on Canvas can be updated to match the
    points in your assignment.</p>
  <p>Updating both of these requires pressing a button within your ADAPT course. I made the choice to have each
    instructor do this manually so as to
    not surprise anyone (instructors or students) if they see a change in their gradebook. In addition, this will give
    you the opportunity to contact
    me if you wanted me to go through the process with you. Note that you'll just need to do this once for each course
    since the code
    has been fixed moving forward. </p>
  <p>This video illustrates the process: <a href="https://youtu.be/GcikdrBpduo">https://youtu.be/GcikdrBpduo</a></p>

  <p>-Eric a.k.a ADAPT support</p>
  @include('beautymail::templates.sunny.contentEnd')

@stop
