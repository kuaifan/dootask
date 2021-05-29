const mix = require('laravel-mix');
const ipv4 = require('internal-ip').v4.sync();

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


//复制资源
mix.copy('resources/public',     'public');

//生成vue页面js
mix.js('resources/js/app.js',       'public/js');

//生成css样式文件
mix.sass('resources/scss/app.scss',      'public/css');

//配置webpack
mix.webpackConfig({
    output: {
        // 路由懒加载文件路径
        chunkFilename: 'js/build/[name].js?[hash:8]',
    },
    devServer: {
        disableHostCheck: true,
    },
    module: {
        rules: [{
            // 图片资源保存路径规则
            test: /(\.(png|jpe?g|gif)$|^((?!font).)*\.svg$)/,
            loaders: [
                {
                    loader: 'file-loader',
                    options: {
                        name: path => {
                            path = path.replace(/\\/g, '/');
                            // 自定义部分
                            try {
                                if (/\/resources\//.test(path)) {
                                    let file = path.substring(path.indexOf('/resources/') + '/resources/'.length);
                                    if (file) {
                                        if (file.substring(0, 3) === 'js/') file = 'pages/' + file.substring(3);
                                        if (file.substring(0, 7) === 'images/') file = file.substring(7);
                                        return 'images/' + file + '?[hash:8]'
                                    }
                                }
                            } catch (e) { }
                            // 系统定义部分
                            if (!/node_modules|bower_components/.test(path)) {
                                return (
                                    Config.fileLoaderDirs.images + '/[name].[ext]?[hash:8]'
                                );
                            }
                            return (
                                Config.fileLoaderDirs.images + '/vendor/' + path.replace(
                                    /((.*(node_modules|bower_components))|images|image|img|assets)\//g,
                                    ''
                                ) + '?[hash:8]'
                            );
                        },
                        publicPath: Config.resourceRoot
                    }
                }, {
                    loader: 'img-loader',
                    options: Config.imgLoaderOptions
                }
            ]
        }, {
            // 字体资源保存路径规则
            test: /(\.(woff2?|ttf|eot|otf)$|font.*\.svg$)/,
            loader: 'file-loader',
            options: {
                name: path => {
                    path = path.replace(/\\/g, '/');
                    // 自定义部分
                    try {
                        if (/\/resources\//.test(path)) {
                            let file = path.substring(path.indexOf('/resources/') + '/resources/'.length);
                            if (file) {
                                if (file.substring(0, 3) === 'js/') file = 'pages/' + file.substring(3);
                                if (file.substring(0, 7) === 'fonts/') file = file.substring(7);
                                return 'fonts/' + file + '?[hash:8]'
                            }
                        }
                    } catch (e) { }
                    // 系统定义部分
                    if (!/node_modules|bower_components/.test(path)) {
                        return Config.fileLoaderDirs.fonts + '/[name].[ext]?[hash:8]';
                    }
                    return (
                        Config.fileLoaderDirs.fonts + '/vendor/' + path.replace(
                            /((.*(node_modules|bower_components))|fonts|font|assets)\//g,
                            ''
                        ) + '?[hash:8]'
                    );
                },
                publicPath: Config.resourceRoot
            }
        }]
    }
}).options({
    uglify: {
        uglifyOptions: {
            compress: { inline: false }
        }
    },
    hmrOptions: {
        host: ipv4 || 'localhost',
        port: '9990'
    },
});
