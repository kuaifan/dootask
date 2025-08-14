const path = require("path");
const loger = require("electron-log");
const Store = require('electron-store');
const utils = require("./utils");
const store = new Store({
    name: 'download-manager',
    defaults: {
        downloadHistory: [],
    }
});

const DownloadStore = {
    get(key, defaultValue) {
        return store.get(key, defaultValue);
    },
    set(key, value) {
        store.set(key, value);
    },
};

class DownloadManager {
    static key = 'downloadHistory';

    constructor() {
        const history = DownloadStore.get(DownloadManager.key, []);
        if (utils.isArray(history)) {
            this.downloadHistory = history.map(item => ({
                ...item,

                // 历史记录中，将 progressing 状态改为 interrupted
                state: item.state === 'progressing' ? 'interrupted' : item.state,

                // 移除源对象，避免序列化问题
                _source: undefined,
            }));
        } else {
            this.downloadHistory = [];
        }
    }

    /**
     * 转换下载项格式
     * @param {Electron.DownloadItem} downloadItem
     */
    convert(downloadItem) {
        return {
            filename: path.basename(downloadItem.getSavePath()) || downloadItem.getFilename(),
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
    add(downloadItem) {
        // 根据保存路径，如果下载项已存在，则取消下载（避免重复下载）
        this.cancel(downloadItem.getSavePath());

        // 添加下载项
        this.downloadHistory.unshift({
            ...this.convert(downloadItem),
            error: null,
            _source: downloadItem,
        });
        if (this.downloadHistory.length > 1000) {
            this.downloadHistory = this.downloadHistory.slice(0, 1000);
        }
        DownloadStore.set(DownloadManager.key, this.downloadHistory);
    }

    /**
     * 获取下载列表
     * @returns {*}
     */
    get() {
        return this.downloadHistory.map(item => {
            return {
                ...item,

                // 移除源对象，避免序列化问题
                _source: undefined,
            };
        });
    }

    /**
     * 更新下载项
     * @param {string} path
     */
    refresh(path) {
        const item = this.downloadHistory.find(d => d.path === path)
        if (!item) {
            return;
        }
        const downloadItem = item._source;
        if (!downloadItem) {
            loger.warn(`Download item not found for path: ${path}`);
            return;
        }
        Object.assign(item, this.convert(downloadItem))
        DownloadStore.set(DownloadManager.key, this.downloadHistory);
    }

    /**
     * 尝试更新下载项的错误信息
     * @param {Electron.DownloadItem} downloadItem
     * @param {Object} headers
     */
    async updateError(downloadItem, headers = {}) {
        const urls = downloadItem.getURLChain()
        const url = urls.length > 0 ? urls[0] : downloadItem.getURL()
        const path = downloadItem.getSavePath()

        const item = this.downloadHistory.find(d => d.path === path)
        if (!item) {
            return;
        }

        try {
            const res = await fetch(url, {
                method: 'HEAD',
                headers,
            })
            let error = null
            if (res.headers.get('X-Error-Message-Base64')) {
                error = Buffer.from(res.headers.get('X-Error-Message-Base64'), 'base64').toString('utf-8')
            } else if (res.headers.get('X-Error-Message')) {
                error = res.headers.get('X-Error-Message')
            }
            if (error) {
                Object.assign(item, {error});
                DownloadStore.set(DownloadManager.key, this.downloadHistory);
                return true;
            }
        } catch {
            // 忽略错误
        }
        return false
    }

    /**
     * 暂停下载项
     * @param {string} path
     */
    pause(path) {
        const item = this.downloadHistory.find(d => d.path === path)
        if (!item) {
            return;
        }
        const downloadItem = item._source;
        if (!downloadItem) {
            loger.warn(`Download item not found for path: ${path}`);
            return;
        }
        downloadItem.pause();
        this.refresh(path);
    }

    /**
     * 恢复下载项
     * @param {string} path
     */
    resume(path) {
        const item = this.downloadHistory.find(d => d.path === path)
        if (!item) {
            return;
        }
        const downloadItem = item._source;
        if (!downloadItem) {
            loger.warn(`Download item not found for path: ${path}`);
            return;
        }
        downloadItem.resume();
        this.refresh(path);
    }

    /**
     * 取消下载项
     * @param {string} path
     */
    cancel(path) {
        const item = this.downloadHistory.find(d => d.path === path)
        if (!item) {
            return;
        }
        const downloadItem = item._source;
        if (!downloadItem) {
            loger.warn(`Download item not found for path: ${path}`);
            return;
        }
        downloadItem.cancel();
        this.refresh(path);
    }

    /**
     * 取消所有下载项
     */
    cancelAll() {
        this.downloadHistory.forEach(item => {
            this.cancel(item.path);
        });
    }

    /**
     * 删除下载项
     * @param {string} path
     */
    remove(path) {
        const index = this.downloadHistory.findIndex(item => item.path === path);
        if (index > -1) {
            this.cancel(path);
            this.downloadHistory.splice(index, 1);
            DownloadStore.set(DownloadManager.key, this.downloadHistory);
        }
    }

    /**
     * 清空下载项
     */
    removeAll() {
        this.cancelAll();
        this.downloadHistory = [];
        DownloadStore.set(DownloadManager.key, []);
    }
}

module.exports = {DownloadStore, DownloadManager};
