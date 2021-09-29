@php
  $config = [
      'appName' => config('app.name'),
      'locale' => $locale = app()->getLocale(),
      'locales' => config('app.locales'),
      'githubAuth' => config('services.github.client_id'),
      'libretextsAuth' => config('services.libretexts.client_id'),
      'isMe' => config('myconfig.is_me_cookie') === ($_COOKIE['IS_ME'] ?? 'no cookie present'),
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

</body>
</html>
