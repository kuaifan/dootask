const nativefier = require('nativefier').default;
const config = require('./package.json');

const options = {
    name: config.name,
    version: config.version,
    targetUrl: 'http://127.0.0.1:2222',
    arch: 'arm64',
    out: './build',
    icon: './resources/assets/statics/public/images/logo-app.png',
    clearCache: false,
    disableDevTools: false,
    disableContextMenu: false,
    fileDownloadOptions: {
        saveAs: true,
    },
};

nativefier(options, function (error, appPath) {
    if (error) {
        console.error(error);
    } else {
        console.log('App has been nativefied to', appPath);
    }
});
