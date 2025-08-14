const loger = require("electron-log");
const Store = require('electron-store');
const store = new Store({
    name: 'download-manager',
    defaults: {
        downloadHistory: [],
    }
});
const electronDl = require("@dootask/electron-dl").default;

class DownloadManager {
    constructor() {
        this.downloadHistory = store.get('downloadHistory', []);
    }

    /**
     * 转换下载项格式
     * @param {Electron.DownloadItem} downloadItem
     */
    convertItem(downloadItem) {
        return {
            filename: downloadItem.getFilename(),
            path: downloadItem.getSavePath(),
            url: downloadItem.getURL(),
            urls: downloadItem.getURLChain(),
            mine: downloadItem.getMimeType(),
            received: downloadItem.getReceivedBytes(),
            total: downloadItem.getTotalBytes(),
            percent: downloadItem.getPercentComplete(),
            speed: downloadItem.getCurrentBytesPerSecond(),
            state: downloadItem.getState(),
            paused: downloadItem.isPaused(),
            startTime: downloadItem.getStartTime(),
            endTime: downloadItem.getEndTime(),
        }
    }

    /**
     * 添加下载项
     * @param {Electron.DownloadItem} downloadItem
     */
    addDownloadItem(downloadItem) {
        this.downloadHistory.unshift({
            ...this.convertItem(downloadItem),
            _source: downloadItem,
        });
        store.set('downloadHistory', this.downloadHistory.slice(0, 100)); // 限制最多100个下载项
        loger.info(`Download item added: ${downloadItem.getSavePath()}`);
    }

    /**
     * 更新下载项
     * @param {string} path
     */
    updateDownloadItem(path) {
        const item = this.downloadHistory.find(d => d.path === path)
        if (!item) {
            loger.warn(`Download item not found for path: ${path}`);
            return;
        }
        const downloadItem = item._source;
        if (!downloadItem) {
            loger.warn(`Download item not found for path: ${path}`);
            return;
        }
        Object.assign(item, this.convertItem(downloadItem))
        store.set('downloadHistory', this.downloadHistory);
        loger.info(`Download item updated: ${path} - ${item.state} (${item.percent}%)`);
    }

    /**
     * 暂停下载项
     * @param {string} path
     */
    pauseDownloadItem(path) {
        const item = this.downloadHistory.find(d => d.path === path)
        if (!item) {
            loger.warn(`Download item not found for path: ${path}`);
            return;
        }
        const downloadItem = item._source;
        if (!downloadItem) {
            loger.warn(`Download item not found for path: ${path}`);
            return;
        }
        downloadItem.pause();
        this.updateDownloadItem(path);
    }

    /**
     * 恢复下载项
     * @param {string} path
     */
    resumeDownloadItem(path) {
        const item = this.downloadHistory.find(d => d.path === path)
        if (!item) {
            loger.warn(`Download item not found for path: ${path}`);
            return;
        }
        const downloadItem = item._source;
        if (!downloadItem) {
            loger.warn(`Download item not found for path: ${path}`);
            return;
        }
        downloadItem.resume();
        this.updateDownloadItem(path);
    }

    /**
     * 取消下载项
     * @param {string} path
     */
    cancelDownloadItem(path) {
        const item = this.downloadHistory.find(d => d.path === path)
        if (!item) {
            loger.warn(`Download item not found for path: ${path}`);
            return;
        }
        const downloadItem = item._source;
        if (!downloadItem) {
            loger.warn(`Download item not found for path: ${path}`);
            return;
        }
        downloadItem.cancel();
        this.updateDownloadItem(path);
    }

    /**
     * 取消所有下载项
     */
    cancelAllDownloadItems() {
        this.downloadHistory.forEach(item => {
            this.cancelDownloadItem(item.path);
        });
    }

    /**
     * 清空下载历史
     */
    clearHistory() {
        this.downloadHistory = [];
        store.set('downloadHistory', []);
    }
}

const downloadManager = new DownloadManager();

function initialize(options = {}) {
    // 下载配置
    electronDl({
        showBadge: false,
        showProgressBar: false,

        ...options,

        onStarted: (item) => {
            downloadManager.addDownloadItem(item);
        },
        onProgress: (item) => {
            downloadManager.updateDownloadItem(item.path);
        },
        onCancel: (item) => {
            downloadManager.updateDownloadItem(item.getSavePath())
        },
        onCompleted: (item) => {
            downloadManager.updateDownloadItem(item.path);
        }
    });
}


module.exports = {
    initialize
}
