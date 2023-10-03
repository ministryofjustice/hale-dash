const mix_ = require('laravel-mix');


mix_.setPublicPath('./dist')
  .copy('./assets/images/*', 'dist/images/')
  .copy('./assets/webfonts/*', 'dist/webfonts/')
  .sass('./assets/scss/style.scss', 'css/hale-dash-style.min.css')
  .copy('./assets/js/*', 'dist/js/')
  .options({
    processCssUrls: false
  });

if (mix_.inProduction()) {
  mix_.version();
} else {
  mix_.sourceMaps();
}
