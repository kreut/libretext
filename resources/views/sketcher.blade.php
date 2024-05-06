<!DOCTYPE html>
<html lang="en">
<head>
  <title>Embedded Sketcher</title>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    #page {
      text-align: center;
    }

    #sketcher {
      display: inline-block;
      width: 40em;
      height: 32em;
      border: 2px solid #555555;
    }

    #check {
      font-size: 2em;
      padding: .6em .8em;
      border: 2px solid #555555;
      border-radius: .5em;
      cursor: pointer;
    }
  </style>
  <script type="text/javascript"
          src="https://cdnjs.cloudflare.com/ajax/libs/iframe-resizer/4.2.11/iframeResizer.contentWindow.min.js"
  ></script>
  <script src="{{ asset('assets/js/molview.mjs') }}"></script>
  <script>
    window.addEventListener('message', receiveMessage)

    async function receiveMessage (event) {
      if (event.data === 'checkSketcher') {
        const sketcher = document.getElementById('sketcher')
        const smiles = await sketcher.getSMILES()
        event.source.postMessage({
          technology: 'sketcher',
          correct: smiles === 'CCC(C)O',
          submissionResults: true,
          smiles: smiles
        }, '*')
      }
    }

    async function check () {

    }
  </script>
</head>
<body>
<div id="page">
  <h1>Draw 2-butanol:</h1>
  <molview-sketcher id="sketcher"></molview-sketcher>
  <br><br>
</div>
</body>
</html>
