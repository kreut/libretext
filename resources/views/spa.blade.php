@php
  $config = [
      'appName' => config('app.name'),
      'locale' => $locale = app()->getLocale(),
      'locales' => config('app.locales'),
      'githubAuth' => config('services.github.client_id'),
      'libretextsAuth' => config('services.libretexts.client_id'),
      'isAdmin' =>\App\Helpers\Helper::isAdmin(),
      'showEnvironment' => $_COOKIE['show_environment'] ?? false,
      'environment' => config('app.env'),
      'clickerApp' => isset($_COOKIE['clicker_app']) &&  $_COOKIE['clicker_app'] === '1',
      'toggleColors' => [  'checked' => '#008600', 'unchecked' => '#6c757d' ]
  ];

  if (!\Auth::user() && !session()->has('landing_page')){
      session(['landing_page' =>$_SERVER['REQUEST_URI']]);
  }
@endphp
  <!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="asset-url" content="{{ config('app.asset_url') }}">
  <link rel="shortcut icon" type="image/png" href="{{ asset('assets/img/favicon.png') }}"/>
  <title>{{ config('app.name') }}</title>
  <link rel="stylesheet" href="{{ mix('dist/css/app.css') }}">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/iframe-resizer/4.2.11/iframeResizer.contentWindow.js"
          integrity="sha512-RMBWitJB1ymY4l6xeYsFwoEgVCAnOWX/zL1gNwXjlUj78nZ8SVbJsZxbH/w0p2jDNraHkOW8rzQgcJ0LNSXWBA=="
          crossorigin="anonymous"
  ></script>
  <script src="https://unpkg.com/centrifuge@5.0.1/dist/centrifuge.js"></script>
</head>
<body>
<div id="app"></div>

{{-- Global configuration object --}}
<script>
  window.config = @json($config);
  //
</script>
{{-- Load the application scripts --}}
<script src="{{ mix('dist/js/app.js') }}"></script>
<script type="text/x-mathjax-config">/*<![CDATA[*/
  MathJax.Ajax.config.path["mhchem"] =
            "https://cdnjs.cloudflare.com/ajax/libs/mathjax-mhchem/3.3.2";
        MathJax.Hub.Config({ messageStyle: "none",
        tex2jax: {preview: "none"},
        jax: ["input/TeX","input/MathML","output/SVG"],
  extensions: ["tex2jax.js","mml2jax.js","MathMenu.js","MathZoom.js"],
  TeX: {
        extensions: ["autobold.js","mhchem.js","color.js","cancel.js", "AMSmath.js","AMSsymbols.js","noErrors.js","noUndefined.js"]
  },
    "HTML-CSS": { linebreaks: { automatic: true , width: "90%"}, scale: 85, mtextFontInherit: false},
menuSettings: { zscale: "150%", zoom: "Double-Click" },
         SVG: { linebreaks: { automatic: true } }});
/*]]>*/





</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.3/MathJax.js?config=TeX-AMS_HTML"></script>
<script>
  titleHolder = document.getElementById('titleHolder')
  let front
  if (titleHolder) {
    front = titleHolder.innerText
    front = front.match(/^.*?:/)
  }
  if (front) {
    front = front[0]
    front = front.split(':')[0]
    if (front.includes('.')) {
      front = front.split('.')
      front = front.map((int) => int.includes('0') ? parseInt(int, 10) : int).join('.')
    }
    front += '.'
  } else {
    front = ''
  }
  front = front.replace(/_/g, ' ')
  MathJaxConfig = {
    TeX: {
      equationNumbers: {
        autoNumber: 'all',
        formatNumber: function (n) {
          return front + n
        }
      },
      macros: {
        PageIndex: ['{' + front + ' #1}', 1],
        test: ['{' + front + ' #1}', 1]
      },
      Macros: {
        PageIndex: ['{' + front + ' #1}', 1],
        test: ['{' + front + ' #1}', 1]
      },
      SVG: {
        linebreaks: { automatic: true }
      }
    }
  }

  MathJax.Hub.Config(MathJaxConfig)

</script>
<script>   document.addEventListener('DOMContentLoaded', function (event) {
  console.log('dom loaded')
    if (window.self !== window.top) {
      console.log('fixing body')
      console.log(document.getElementsByTagName('body')[0])
      document.body.style.background = 'transparent'
    }
  })</script>
</body>
</html>
