const mix = require('laravel-mix');
const ipv4 = require('internal-ip').v4.sync();
const execSync = require('child_process').execSync;
const argv = process.argv;

const mixBuildName = function (str) {
    if (typeof str !== "string") {
        return str;
    }
    if (/resources_assets_js_pages_(.*?)_vue/.test(str)) {
        str = /resources_assets_js_pages_(.*?)_vue/.exec(str)[1];
    }
    return str.replace(/_/g, '/');
}

const hmrPublicURL = function (port) {
    try {
        return execSync('gp url ' + port + ' &> /dev/null').toString().trim()
    } catch (e) {
        return null
    }
}

const devPort = 22222
const publicURL = hmrPublicURL(devPort)

const isHot = argv.includes('--hot');
const isElectron = argv.includes('--electron');
const publicPath = (!isHot && isElectron) ? 'electron/public' : 'public';

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
            },
        };
        if (isHot) {
            if (publicURL) {
                config.output.publicPath = publicURL + '/'
            }
        } else {
            if (isElectron) {
                config.output.publicPath = './'
            }
        }
        return config
    })
    .options({
        processCssUrls: false,
        hmrOptions: {
            host: ipv4 || 'localhost',
            port: devPort,
            publicURL
        },
    })
    .vue({
        version: 2,
    });
