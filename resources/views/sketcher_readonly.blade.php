<!DOCTYPE html>
<html lang="en">
<head>
  <title>ReadOnly Embedded Sketcher</title>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    .sketcher {
      display: inline-block;
      width: 40em;
      height: 32em;
      border: 2px solid #555555;
    }
  </style>
  <script type="text/javascript"
          src="https://cdnjs.cloudflare.com/ajax/libs/iframe-resizer/4.2.11/iframeResizer.contentWindow.min.js"
  ></script>
  <script src="{{ asset('assets/js/molview.mjs?v=5') }}"></script>
  <script>
    window.addEventListener('message', receiveMessage)

    async function receiveMessage (event) {
      console.log(event.data)
      if (event.data.method === 'load') {
        const sketcher = document.getElementById('sketcher')
        await sketcher.load(event.data.structure)
      }
    }
  </script>
</head>
<body>
<div id="page">
  <molview-sketcher id="sketcher" readonly class="sketcher"></molview-sketcher>
</div>
</body>
</html>
