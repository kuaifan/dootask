const fs = require('fs')
const path = require('path')
const {app, BrowserWindow} = require('electron')

let willQuitApp = false,
    devloadCachePath = path.resolve(__dirname, ".devload"),
    devloadUrl = "";
if (fs.existsSync(devloadCachePath)) {
    devloadUrl = fs.readFileSync(devloadCachePath, 'utf8')
}

function getCounterValue(title) {
    const itemCountRegex = /[([{]([\d.,]*)\+?[}\])]/;
    const match = itemCountRegex.exec(title);
    return match ? match[1] : undefined;
}

function createWindow(setDockBadge) {
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

    mainWindow.on('page-title-updated', function (event, title) {
        const counterValue = getCounterValue(title);
        if (counterValue) {
            setDockBadge(counterValue);
        } else {
            setDockBadge('');
        }
    })

    mainWindow.on('close', function (e) {
        if (!willQuitApp) {
            e.preventDefault();
            app.hide();
        }
    })
}

app.whenReady().then(() => {
    createWindow(app.dock.setBadge)

    app.on('activate', function () {
        if (BrowserWindow.getAllWindows().length === 0) createWindow(app.dock.setBadge)
    })
})

app.on('window-all-closed', function () {
    if (process.platform !== 'darwin') app.quit()
})

app.on('before-quit', () => {
    willQuitApp = true
});
