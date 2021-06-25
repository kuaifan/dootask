const nativefier = require('nativefier').default;
const inquirer = require('inquirer');
const config = require('./package.json');

const options = {
    name: config.name,
    appVersion: config.version,
    buildVersion: config.version,
    out: './build',
    icon: './resources/assets/statics/public/images/logo-app.png',
    bounce: false,
    counter: true,
    clearCache: false,
    disableDevTools: true,
    disableContextMenu: true,
    fileDownloadOptions: {
        saveAs: true,
    },
};

const questions = [
    {
        type: 'input',
        name: 'targetUrl',
        message: "请输入网站地址",
        validate: function (value) {
            return value !== ''
        }
    }, {
        type: 'list',
        name: 'platform',
        message: "选择操作系统平台",
        choices: [{
            name: "MacOS Intel",
            value: {
                platform: 'mac',
                arch: 'x64',
            }
        }, {
            name: "MacOS Arm64",
            value: {
                platform: 'mac',
                arch: 'arm64',
            }
        }, {
            name: "Window x86_64",
            value: {
                platform: 'windows',
                arch: 'x64',
            }
        }]
    }
];

inquirer.prompt(questions).then(answers => {
    nativefier(Object.assign(options, answers.platform, {
        targetUrl: answers.targetUrl
    }), (error) => {
        error && console.error(error)
    });
});
