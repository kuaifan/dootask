const {BrowserWindow, screen} = require('electron')
const path = require('path');
const Store = require("electron-store");
const loger = require("electron-log");
const {default: electronDl, download} = require("@dootask/electron-dl");
const utils = require("./utils");
const {DownloadManager} = require("./utils/download");

const store = new Store();
const downloadManager = new DownloadManager();
let downloadWindow = null;

function initialize(options = {}) {
    // 下载配置
    electronDl({
        showBadge: false,
        showProgressBar: false,

        ...options,

        onStarted: (item) => {
            downloadManager.addDownloadItem(item);
            syncDownloadItems();
        },
        onProgress: (item) => {
            downloadManager.updateDownloadItem(item.path);
            syncDownloadItems();
        },
        onCancel: (item) => {
            downloadManager.updateDownloadItem(item.getSavePath())
            syncDownloadItems();
        },
        onCompleted: (item) => {
            downloadManager.updateDownloadItem(item.path);
            syncDownloadItems();
        }
    });
}

function syncDownloadItems() {
    // 同步下载项到渲染进程
    if (downloadWindow) {
        downloadWindow.webContents.send('download-items', downloadManager.getDownloadItems());
    }
}

function getLanguagePack(codeOrPack) {
    if (codeOrPack && typeof codeOrPack === 'object') {
        return codeOrPack;
    }
    const code = (codeOrPack || 'zh').toString();
    return {
        code,
        title: '下载管理器',
        // todo
    }
}

async function open(language = 'zh', theme = 'light') {
    // 获取语言包
    const finalLanguage = getLanguagePack(language);

    // 如果窗口已存在，直接显示
    if (downloadWindow) {
        // 更新窗口数据
        await updateDownloadWindow(language, theme)
        // 显示窗口并聚焦
        downloadWindow.show();
        downloadWindow.focus();
        return;
    }

    // 窗口默认参数
    const downloadWindowOptions = {
        width: 700,
        height: 480,
        minWidth: 500,
        minHeight: 350,
        center: true,
        show: false,
        autoHideMenuBar: true,
        title: finalLanguage.title,
        backgroundColor: utils.getDefaultBackgroundColor(),
        webPreferences: {
            preload: path.join(__dirname, 'electron-preload.js'),
            webSecurity: true,
            nodeIntegration: true,
            contextIsolation: true,
        }
    }

    // 恢复窗口位置
    const downloadWindowBounds = store.get('downloadWindowBounds', {});
    if (
        downloadWindowBounds.width !== undefined &&
        downloadWindowBounds.height !== undefined &&
        downloadWindowBounds.x !== undefined &&
        downloadWindowBounds.y !== undefined
    ) {
        // 获取所有显示器的可用区域
        const displays = screen.getAllDisplays();
        // 检查窗口是否在任意一个屏幕内
        let isInScreen = false;
        for (const display of displays) {
            const area = display.workArea;
            if (
                downloadWindowBounds.x + downloadWindowBounds.width > area.x &&
                downloadWindowBounds.x < area.x + area.width &&
                downloadWindowBounds.y + downloadWindowBounds.height > area.y &&
                downloadWindowBounds.y < area.y + area.height
            ) {
                isInScreen = true;
                break;
            }
        }
        // 如果超出所有屏幕，则移动到主屏幕可见区域
        if (!isInScreen) {
            const primaryArea = screen.getPrimaryDisplay().workArea;
            downloadWindowBounds.x = primaryArea.x + 50;
            downloadWindowBounds.y = primaryArea.y + 50;
            // 防止窗口太大超出屏幕
            downloadWindowBounds.width = Math.min(downloadWindowBounds.width, primaryArea.width - 100);
            downloadWindowBounds.height = Math.min(downloadWindowBounds.height, primaryArea.height - 100);
        }
        downloadWindowOptions.width = downloadWindowBounds.width;
        downloadWindowOptions.height = downloadWindowBounds.height;
        downloadWindowOptions.center = false;
        downloadWindowOptions.x = downloadWindowBounds.x;
        downloadWindowOptions.y = downloadWindowBounds.y;
    }

    // 创建窗口
    downloadWindow = new BrowserWindow(downloadWindowOptions);

    // 禁止修改窗口标题
    downloadWindow.on('page-title-updated', (event) => {
        event.preventDefault()
    })

    // 监听窗口关闭保存窗口位置
    downloadWindow.on('close', () => {
        const bounds = downloadWindow.getBounds();
        store.set('downloadWindowBounds', bounds);
    });

    // 监听窗口关闭事件
    downloadWindow.on('closed', () => {
        downloadWindow = null;
    });

    // 加载下载管理器页面
    const htmlPath = path.join(__dirname, 'render', 'download', 'index.html');
    const themeParam = (theme === 'dark' ? 'dark' : 'light');
    await downloadWindow.loadFile(htmlPath, {query: {theme: themeParam}});

    // 将语言包发送到渲染进程
    downloadWindow.webContents.once('dom-ready', () => {
        updateDownloadWindow(language, theme)
    });

    // 显示窗口
    downloadWindow.show();
}

function close() {
    if (downloadWindow) {
        downloadWindow.close();
        downloadWindow = null;
    }
}

function destroy() {
    if (downloadWindow) {
        downloadWindow.destroy();
        downloadWindow = null;
    }
}

async function updateDownloadWindow(language, theme) {
    if (downloadWindow) {
        try {
            const finalLanguage = getLanguagePack(language);
            downloadWindow.setTitle(finalLanguage.title);
            downloadWindow.webContents.send('download-theme', theme);
            downloadWindow.webContents.send('download-language', finalLanguage);
            syncDownloadItems()
        } catch (error) {
            loger.error(error);
        }
    }
}

module.exports = {
    initialize,
    download,
    open,
    close,
    destroy,
    updateDownloadWindow
}
