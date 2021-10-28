@php
  $config = [
      'appName' => config('app.name'),
      'locale' => $locale = app()->getLocale(),
      'locales' => config('app.locales'),
      'githubAuth' => config('services.github.client_id'),
      'libretextsAuth' => config('services.libretexts.client_id'),
      'isMe' => config('myconfig.is_me_cookie') === ($_COOKIE['IS_ME'] ?? 'no cookie present'),
      'showEnvironment' => $_COOKIE['show_environment'] ?? false,
      'environment' => config('app.env'),
      'isAdmin' => \App\Helpers\Helper::isAdmin(),
      'toggleColors' => isset($_COOKIE['accessibility'])
                    ? [  'checked' => '#007E00', 'unchecked' => '#6c757d' ]
                    : [  'checked' => '#28a745', 'unchecked' =>  '#6c757d']
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
  <?php if (isset($_COOKIE['accessibility'])) { ?>
  <link rel="stylesheet" href="{{ mix('dist/css/accessible_app.css') }}">
  <?php } else { ?>
  <link rel="stylesheet" href="{{ mix('dist/css/app.css') }}">
  <?php } ?>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/iframe-resizer/4.2.11/iframeResizer.contentWindow.js"
          integrity="sha512-RMBWitJB1ymY4l6xeYsFwoEgVCAnOWX/zL1gNwXjlUj78nZ8SVbJsZxbH/w0p2jDNraHkOW8rzQgcJ0LNSXWBA=="
          crossorigin="anonymous"
  ></script>
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
<script type="text/javascript" async="true"
        src="https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.3/MathJax.js?config=TeX-AMS_HTML"></script>
<script type="text/javascript">
  front = document.getElementById('titleHolder').innerText;
  front = front.match(/^.*?:/);
  if (front) {
    front = front[0];
    front = front.split(":")[0];
    if (front.includes(".")) {
      front = front.split(".");
      front = front.map((int) => int.includes("0") ? parseInt(int, 10) : int).join(".");
    }
    front += ".";
  } else {
    front = "";
  }
  console.log(front);
  front = front.replace(/_/g, " ");
  MathJaxConfig = {
    TeX: {
      equationNumbers: {
        autoNumber: "all",
        formatNumber: function (n) {
          return front + n;
        }
      },
      macros: {
        PageIndex: ["{" + front + " #1}", 1],
        test: ["{" + front + " #1}", 1]
      },
      Macros: {
        PageIndex: ["{" + front + " #1}", 1],
        test: ["{" + front + " #1}", 1]
      },
      SVG: {
        linebreaks: {automatic: true}
      }
    }
  };

  MathJax.Hub.Config(MathJaxConfig);
  MathJax.Hub.Register.StartupHook("End", () => {
    if (activateBeeLine) activateBeeLine()
  });
</script>
</body>
</html>
