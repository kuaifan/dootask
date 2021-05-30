const mix = require('laravel-mix');

const mixBuildName = function (str) {
    if (/resources_assets_js_pages_(.*?)_vue/.test(str)) {
        str = /resources_assets_js_pages_(.*?)_vue/.exec(str)[1];
    }
    return str.replace(/_/g, '/');
}

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

mix
    .copy('resources/assets/statics/public', 'public')
    .js('resources/assets/js/app.js', 'public/js')
    .sass('resources/assets/sass/app.scss', 'public/css')
    .webpackConfig({
        output: {
            chunkFilename: function ({chunk}) {
                return `js/build/${mixBuildName(chunk.id)}.${mix.inProduction() ? '[hash:8].' : ''}js`
            }
        },
    })
    .vue({
        version: 2,
    });
