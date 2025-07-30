<!Doctype html>
<html lang="en">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>Media Player</title>
  <script src="//ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
  <script src="//cdn.jsdelivr.net/npm/js-cookie@3.0.1/dist/js.cookie.min.js"></script>
  <script type="text/javascript"
          src="https://cdnjs.cloudflare.com/ajax/libs/iframe-resizer/4.2.11/iframeResizer.contentWindow.min.js"
  ></script>
  <!-- Bootstrap CSS -->
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
    rel="stylesheet"
  />

  <link
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    rel="stylesheet"
  />

@if(!$is_phone)
    <link rel="stylesheet" href="{{ asset('assets/js/ableplayer/build/ableplayer.min.css')}}" type="text/css"/>
    <script src="{{ asset('assets/js/ableplayer/build/ableplayer.min.js') }}"></script>
  @endif
</head>
<body>
<div>
  @if($is_phone)
    @if($type === 'audio')
      <div style="height:300px;"></div>
      <script>
        window.addEventListener('load', function () {
          setTimeout(() => {
            window.location.href = @json($temporary_url);
          }, 100)
        })
      </script>
    @elseif($type === 'video')
      @if (isset($mp4_temporary_url) && $mp4_temporary_url)
        <video controls style="width: 100%; height: auto;">
          <source src="{{ $mp4_temporary_url }}" type="video/mp4">
          <source src="{{ $temporary_url }}" type="video/webm">
          Your browser does not support the video tag.
        </video>
      @else
        <div style="height: 300px">
          <div id="processing-message" style="
              max-width: 400px;
              margin: 2rem auto;
              padding: 1.5rem;
              border: 2px solid #ccc;
              border-radius: 12px;
              background-color: #f9f9f9;
              font-family: sans-serif;
              text-align: center;
              box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            "
          >
            <p style="font-size: 1.1rem; margin-bottom: 0.5rem;">Your video is being optimized for mobile view...</p>
            <p style="font-size: 0.95rem; color: #555;">This may take a few minutes. In the meantime, you may view your
              video in a browser.</p>
          </div>
        </div>
      @endif
    @endif
  @else
    @if($type === 'audio')
      <audio id="audio1"
             preload="auto"
             data-able-player
             playsinline
             data-start-time="{{ $start_time }}"
      >
        <source type="audio/mpeg" src="{{ $temporary_url }}"/>
        <track kind="captions" src="{{ $vtt_file }}"/>
      </audio>
    @elseif($type === 'video')
      <video id="video1"
             preload="auto"
             playsinline
             data-able-player
             data-start-time="{{ $start_time }}"
      >
        <source src="{{ $mp4_temporary_url }}" type="video/mp4">
        <source src="{{ $temporary_url }}" type="video/webm">
        <track kind="captions" src="{{ $vtt_file }}"/>
      </video>
      <div class="controls" style="margin-bottom:50px">
        <button type="button" class="btn btn-primary me-2" onclick="rotateLeft()">
          <i class="fas fa-rotate-left"></i> Rotate Left
        </button>

        <button type="button" class="btn btn-primary me-2" onclick="rotateRight()">
          <i class="fas fa-rotate-right"></i> Rotate Right
        </button>

        <button type="button" class="btn btn-secondary" onclick="resetRotation()">
          <i class="fas fa-undo"></i> Reset
        </button>
      </div>
    @endif
  @endif

</div>
<script>
  let rotation = 0;
  const video = document.getElementById('video1');

  function applyRotation() {
    video.style.transform = `rotate(${rotation}deg)`;
  }

  function rotateLeft() {
    rotation -= 90;
    applyRotation();
  }

  function rotateRight() {
    rotation += 90;
    applyRotation();
  }

  function resetRotation() {
    rotation = 0;
    applyRotation();
  }
</script>

</body>
</html>
