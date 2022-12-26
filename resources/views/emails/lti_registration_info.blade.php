@extends('beautymail::templates.sunny')

@section('content')

  @include ('beautymail::templates.sunny.heading' , [
      'heading' => 'Complete LTI Registration',
      'level' => 'h1',
  ])

  @include('beautymail::templates.sunny.contentStart')
  <p>Hi,</p>
  <p>
    Thank you for registering your Canvas LTI information with ADAPT. At this point, you'll need to <a href="https://www.youtube.com/watch?v=0rhJ7CfiF04">add ADAPT as an
      external app</a>.  One important note about this process: 23 seconds into the video you're asked to "Add App".  If your Canvas installation doesn't have a Configuration Type of
    LTI 1.3, then instead, please choose "By Client ID" and use Canvas' "Developer Key ID" which you entered into our Canvas Configuration form.
  </p>
  <p>Within ADAPT, instructors should navigate to a particular course that they would like to use within Canvas.  Then, under <a href="https://youtu.be/watch?v=CjvAzBTRM9o">Course Properties</a> for that course
    they should choose "Yes" for LMS to enable the LTI grade passback feature.
    Once a course is given this capability an instructor can <a href="https://www.youtube.com/watch?v=mwptJHUjY6w">link up their assignments</a>.</p>
<p>Within the Canvas assignments, please remind instructors to:</p>
  <ul>
    <li>Be sure to have Load This Tool In A New Tab checked.</li>
    <li>Choose Unlimited Attempts (ADAPT will control the number of submissions using Assignment Properties)</li>
  </ul>
  <p>If you have any questions, feel free to get in touch.</p>
  <p>-ADAPT Support</p>
  @include('beautymail::templates.sunny.contentEnd')

@stop
