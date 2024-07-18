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

</head>
<body>
<div class="alert alert-danger">
  {{$message}}
</div>
</body>
</html>
