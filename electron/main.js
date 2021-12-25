const fs = require('fs')
const path = require('path')
const XLSX = require('xlsx');
const {app, BrowserWindow, ipcMain, dialog} = require('electron')

let mainWindow = null,
    subWindow = [],
    willQuitApp = false,
    inheritClose = false,
    devloadCachePath = path.resolve(__dirname, ".devload"),
    devloadUrl = "";
if (fs.existsSync(devloadCachePath)) {
    devloadUrl = fs.readFileSync(devloadCachePath, 'utf8')
}

function runNum(str, fixed) {
    let _s = Number(str);
    if (_s + "" === "NaN") {
        _s = 0;
    }
    if (/^[0-9]*[1-9][0-9]*$/.test(fixed)) {
        _s = _s.toFixed(fixed);
        let rs = _s.indexOf('.');
        if (rs < 0) {
            _s += ".";
            for (let i = 0; i < fixed; i++) {
                _s += "0";
            }
        }
    }
    return _s;
}

function randomString(len) {
    len = len || 32;
    let $chars = 'ABCDEFGHJKMNPQRSTWXYZabcdefhijkmnprstwxyz2345678oOLl9gqVvUuI1';
    let maxPos = $chars.length;
    let pwd = '';
    for (let i = 0; i < len; i++) {
        pwd += $chars.charAt(Math.floor(Math.random() * maxPos));
    }
    return pwd;
}

function createWindow() {
    mainWindow = new BrowserWindow({
        width: 1280,
        height: 800,
        center: true,
        webPreferences: {
            preload: path.join(__dirname, 'preload.js'),
            nodeIntegration: true,
            contextIsolation: false
        }
    })

    if (devloadUrl) {
        mainWindow.loadURL(devloadUrl).then(r => {

        })
    } else {
        mainWindow.loadFile('./public/index.html').then(r => {

        })
    }

    mainWindow.on('close', function (e) {
        if (!willQuitApp) {
            e.preventDefault();
            if (inheritClose) {
                mainWindow.webContents.send("windowClose", {})
            } else {
                app.hide();
            }
        }
    })
}

function createRouter(arg) {
    if (!arg) {
        return;
    }

    if (typeof arg !== "object") {
        arg = {
            path: arg,
            config: {},
        }
    }

    let name = arg.name || "auto_" + randomString(6);
    let item = subWindow.find(item => item.name == name);
    let browser = item ? item.browser : null;
    if (browser) {
        browser.focus();
        if (arg.force === false) {
            return;
        }
    } else {
        browser = new BrowserWindow(Object.assign({
            width: 1280,
            height: 800,
            center: true,
            parent: mainWindow,
            webPreferences: {
                preload: path.join(__dirname, 'preload.js'),
                nodeIntegration: true,
                contextIsolation: false
            }
        }, arg.config || {}))
        browser.on('close', function () {
            let index = subWindow.findIndex(item => item.name == name);
            if (index > -1) {
                subWindow.splice(index, 1)
            }
        })
        subWindow.push({ name, browser })
    }

    if (devloadUrl) {
        browser.loadURL(devloadUrl + '#' + (arg.hash || arg.path)).then(r => {

        })
    } else {
        browser.loadFile('./public/index.html', {
            hash: arg.hash || arg.path
        }).then(r => {

        })
    }
}

app.whenReady().then(() => {
    createWindow()

    app.on('activate', function () {
        if (BrowserWindow.getAllWindows().length === 0) createWindow()
    })
})

app.on('window-all-closed', function () {
    if (process.platform !== 'darwin') app.quit()
})

app.on('before-quit', () => {
    willQuitApp = true
})

ipcMain.on('inheritClose', () => {
    inheritClose = true
})

ipcMain.on('windowRouter', (event, arg) => {
    createRouter(arg)
})

ipcMain.on('windowHidden', () => {
    app.hide();
})

ipcMain.on('windowClose', () => {
    mainWindow.close()
})

ipcMain.on('windowMax', function () {
    if (mainWindow.isMaximized()) {
        mainWindow.restore();
    } else {
        mainWindow.maximize();
    }
})

ipcMain.on('setDockBadge', (event, arg) => {
    if(process.platform !== 'darwin'){
        // Mac only
        return;
    }
    if (runNum(arg) > 0) {
        app.dock.setBadge(String(arg))
    } else {
        app.dock.setBadge("")
    }
})

ipcMain.on('saveSheet', (event, data, filename, opts) => {
    const EXTENSIONS = "xls|xlsx|xlsm|xlsb|xml|csv|txt|dif|sylk|slk|prn|ods|fods|htm|html".split("|");
    dialog.showSaveDialog({
        title: 'Save file as',
        defaultPath: filename,
        filters: [{
            name: "Spreadsheets",
            extensions: EXTENSIONS
        }]
    }).then(o => {
        XLSX.writeFile(data, o.filePath, opts);
    });
})
