<!Doctype html>
<html lang="en">
<head>
  <title>Question Media</title>
  <script src="//ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
  <script src="//cdn.jsdelivr.net/npm/js-cookie@3.0.1/dist/js.cookie.min.js"></script>
  <script type="text/javascript"
          src="https://cdnjs.cloudflare.com/ajax/libs/iframe-resizer/4.2.11/iframeResizer.contentWindow.min.js"
  ></script>
  <link rel="stylesheet" href="{{ asset('assets/js/ableplayer/build/ableplayer.min.css')}}" type="text/css"/>
  <style>

  </style>
  <script src="{{ asset('assets/js/ableplayer/build/ableplayer.min.js') }}"></script>
</head>
<body>
<video id="video1" preload="auto" data-able-player>
  <source type="video/mp4"
          src="{{$temporary_url}}"
  />
</video>
</body>
</html>