const path = require('path')
const fs = require('fs-extra')
const mix = require('laravel-mix')
require('laravel-mix-versionhash')
// const { BundleAnalyzerPlugin } = require('webpack-bundle-analyzer')

mix
  .js('resources/js/app.js', 'public/dist/js')
  .sass('resources/sass/app.scss', 'public/dist/css')

  .disableNotifications()

const ASSET_URL = process.env.ASSET_URL ? process.env.ASSET_URL + '/' : '/'
console.log('Asset URL: ' + ASSET_URL)

if (mix.inProduction()) {
  // console.log(process.env);
  mix
    // .extract() // Disabled until resolved: https://github.com/JeffreyWay/laravel-mix/issues/1889
    // .version() // Use `laravel-mix-versionhash` for the generating correct Laravel Mix manifest file.
    .versionHash()
} else {
  mix.sourceMaps()
}
const webpack = require('webpack')

mix.webpackConfig({
  plugins: [
    // new BundleAnalyzerPlugin()
    new webpack.IgnorePlugin(/^\.\/locale$/, /moment$/), // Locales were causing a css error in app.css
    new webpack.DefinePlugin({
      'process.env.ASSET_PATH': JSON.stringify(ASSET_URL)
    })
  ],
  resolve: {
    extensions: ['.js', '.json', '.vue'],
    alias: {
      '~': path.join(__dirname, './resources/js')
    }
  },
  output: {
    chunkFilename: 'dist/js/[chunkhash].js',
    path: mix.config.hmr ? '/' : path.resolve(__dirname, './public/build'),
    publicPath: ASSET_URL
  }
})

mix.then(() => {
  if (!mix.config.hmr) {
    process.nextTick(() => publishAseets())
  }
})

function publishAseets () {
  const publicDir = path.resolve(__dirname, './public')

  if (mix.inProduction()) {
    fs.removeSync(path.join(publicDir, 'dist'))
  }

  fs.copySync(path.join(publicDir, 'build', 'dist'), path.join(publicDir, 'dist'))
  fs.removeSync(path.join(publicDir, 'build'))
}

/* mix
  .js("resources/js/app.js", "public/js")
  .sass("resources/sass/app.scss", "public/css");*/

