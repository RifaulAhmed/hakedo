const mix = require("laravel-mix");

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

// mix.js('resources/js/app.js', 'public/js')
//     .vue()
//     .sass('resources/sass/app.scss', 'public/css');

mix
    /* CSS */
    .js("resources/js/app.js", "public/js")
    .sass("resources/sass/app.scss", "public/css")
    .sass(
        "resources/assets/styles/sass/themes/lite-purple.scss",
        "public/assets/styles/css/themes/lite-purple.min.css"
    );
  

/* JS */

/* Laravel JS */

mix.combine(
    [
        "resources/assets/js/vendor/jquery.min.js",
        "resources/assets/js/vendor/bootstrap.bundle.min.js",
        "resources/assets/js/vendor/perfect-scrollbar.min.js",
    ],
    "public/assets/js/common-bundle-script.js"
);

mix.js(["resources/assets/js/script.js"], "public/assets/js/script.js");
mix.js(["resources/js/plugin-script/alpine-data.js"], "public/js/plugin-script/alpine-data.js");
mix.js(["resources/js/plugin-script/alpine-store.js"], "public/js/plugin-script/alpine-store.js");
mix.js(["resources/js/plugin-script/apexcharts.js"], "public/js/plugin-script/apexcharts.js");
