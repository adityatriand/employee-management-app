const mix = require('laravel-mix');

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

mix.js('resources/js/app.js', 'public/js')
    .sass('resources/sass/app.scss', 'public/css')
    .styles([
        'resources/css/_variables.css',
        'resources/css/_base.css',
        'resources/css/_sidebar.css',
        'resources/css/_layout.css',
        'resources/css/_components.css',
        'resources/css/_forms.css',
        'resources/css/_utilities.css',
        'resources/css/_pages.css',
        'resources/css/_modules.css',
        'resources/css/_responsive.css',
    ], 'public/css/custom.css')
    .sourceMaps();
