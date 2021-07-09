const fs = require('fs')
const path = require('path')
const XLSX = require('xlsx');
const {app, BrowserWindow, ipcMain, dialog} = require('electron')

let willQuitApp = false,
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

function createWindow() {
    const mainWindow = new BrowserWindow({
        width: 1280,
        height: 800,
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
            app.hide();
        }
    })
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
});

ipcMain.on('setDockBadge', (event, arg) => {
    if (runNum(arg) > 0) {
        app.dock.setBadge(String(arg))
    } else {
        app.dock.setBadge("")
    }
});

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
});
