
const mix = require('laravel-mix')

mix
  .js('resources/js/app.js', 'public/dist/js')
  .sass('resources/sass/app.scss', 'public/dist/css')
  .sass('resources/sass/flowy.scss', 'public/dist/css')

mix.babel([
  'resources/js/helpers/Date.js',
  'resources/js/helpers/LoginRedirect.js'
], 'public/assets/js/combined.js')
