const mix = require('laravel-mix');
const ipv4 = require('internal-ip').v4.sync();
const argv = process.argv;

let mixBuildName = function (str) {
    if (typeof str !== "string") {
        return str;
    }
    if (/resources_assets_js_pages_(.*?)_vue/.test(str)) {
        str = /resources_assets_js_pages_(.*?)_vue/.exec(str)[1];
    }
    return str.replace(/_/g, '/');
}

let isHot = argv.includes('--hot');
let isElectron = argv.includes('--electron');
let publicPath = (!isHot && isElectron) ? 'electron/public' : 'public';

mix
    .copy('resources/assets/statics/public', publicPath)
    .js('resources/assets/js/app.js', 'js')
    .sass('resources/assets/sass/app.scss', 'css')
    .setPublicPath(publicPath)
    .webpackConfig(() => {
        let config = {
            output: {
                chunkFilename: ({chunk}) => {
                    return `js/build/${mixBuildName(chunk.id)}.js`
                }
            }
        };
        if (isElectron && !isHot) {
            config.output.publicPath = './'
        }
        return config
    })
    .options({
        processCssUrls: false,
        hmrOptions: {
            host: ipv4 || 'localhost',
            port: '22222'
        },
    })
    .vue({
        version: 2,
    });
