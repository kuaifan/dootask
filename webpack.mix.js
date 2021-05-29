const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel applications. By default, we are compiling the CSS
 | file for the application as well as bundling up all the JS files.
 |
 */

function getFileName(str) {
    if (/resources_js_pages_(.*?)_vue/.test(str)) {
        return /resources_js_pages_(.*?)_vue/.exec(str)[1];
    }
    return str;
}

mix.vue({ version: 2 })
    .copy('resources/public',           'public')
    .js('resources/js/app.js',      'public/js')
    .sass('resources/scss/app.scss','public/css')
    .webpackConfig({
        output: {
            chunkFilename: function ({chunk}) {
                return `js/build/${ getFileName(chunk.id) }.${ mix.inProduction() ? '[hash:8].' : '' }js`
            }
        },
    });
