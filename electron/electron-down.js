const {BrowserWindow, screen, shell, ipcMain} = require('electron')
const fs = require('fs');
const path = require('path');
const loger = require("electron-log");
const {default: electronDl, download, CancelError, InterruptedError} = require("@dootask/electron-dl");
const utils = require("./lib/utils");
const {DownloadManager, DownloadStore} = require("./lib/download-manager");

const downloadManager = new DownloadManager();

let downloadWindow = null,
    downloadLanguageCode = 'zh',
    downloadWaiting = false;

function initialize(onStarted = null) {
    // 下载配置
    electronDl({
        showBadge: false,
        showProgressBar: false,

        onStarted: (item) => {
            downloadManager.add(item);
            downloadWaiting = false;
            syncDownloadItems();
            if (typeof onStarted === 'function') {
                onStarted(item)
            }
        },
        onCancel: (item) => {
            downloadManager.refresh(item.getSavePath())
            syncDownloadItems();
        },
        onInterrupted: (item) => {
            downloadManager.refresh(item.getSavePath());
            syncDownloadItems();
            // 尝试更新下载项的错误信息
            downloadManager.updateError(item, {
                language: downloadLanguageCode,
            }).then(success => {
                if (success) {
                    syncDownloadItems();
                }
            });
        },
        onProgress: (item) => {
            downloadManager.refresh(item.path);
            syncDownloadItems();
        },
        onCompleted: (item) => {
            downloadManager.refresh(item.path);
            syncDownloadItems();
        }
    });

    // IPC
    ipcMain.handle('downloadManager', async (event, {action, path}) => {
        switch (action) {
            case "get": {
                return {
                    items: downloadManager.get(),
                    waiting: downloadWaiting,
                };
            }

            case "pause": {
                downloadManager.pause(path);
                syncDownloadItems();
                return true;
            }

            case "resume": {
                downloadManager.resume(path);
                syncDownloadItems();
                return true;
            }

            case "cancel": {
                downloadManager.cancel(path);
                syncDownloadItems();
                return true;
            }

            case "remove": {
                downloadManager.remove(path);
                syncDownloadItems();
                return true;
            }

            case "removeAll": {
                downloadManager.removeAll();
                syncDownloadItems();
                return true;
            }

            case "openFile": {
                if (!fs.existsSync(path)) {
                    throw new Error('file not found');
                }
                return shell.openPath(path);
            }

            case "showFolder": {
                if (!fs.existsSync(path)) {
                    throw new Error('file not found');
                }
                shell.showItemInFolder(path);
                return true;
            }
        }
    });
}

async function createDownload(window_, url, options = {}) {
    downloadWaiting = true;
    syncDownloadItems();
    try {
        return await download(window_, url, options);
    } catch (error) {
        // electron-dl rejects with CancelError/InterruptedError; treat them as expected.
        const isCancelError = (typeof CancelError === 'function' && error instanceof CancelError)
            || error?.name === 'CancelError';
        const isInterruptedError = (typeof InterruptedError === 'function' && error instanceof InterruptedError)
            || error?.name === 'InterruptedError';
        if (!isCancelError && !isInterruptedError) {
            throw error;
        }
        return null;
    } finally {
        downloadWaiting = false;
        syncDownloadItems();
    }
}

function syncDownloadItems() {
    // 同步下载项到渲染进程
    if (downloadWindow) {
        downloadWindow.webContents.send('download-items', {
            items: downloadManager.get(),
            waiting: downloadWaiting,
        });
    }
}

function getLanguageData(code) {
    const packs = {
        zh: {
            // 语言设置
            locale: 'zh-CN',
            title: '下载管理器',

            // 界面文本
            searchPlaceholder: '搜索文件名或链接...',
            noItems: '暂无任务',
            noSearchResult: '未找到匹配的结果',

            // 操作按钮
            refresh: '刷新',
            removeAll: "清空历史",
            copyLink: '复制链接',
            resume: '继续',
            pause: '暂停',
            cancel: '取消',
            remove: '删除',
            showInFolder: '显示在文件夹',

            // 状态文本
            progressing: '下载中',
            completed: '已完成',
            cancelled: '已取消',
            interrupted: '失败',
            paused: '已暂停',

            // 成功消息
            copied: "已复制",
            refreshSuccess: '刷新成功',

            // 确认对话框
            confirmCancel: '确定要取消此下载任务并删除记录吗？',
            confirmRemove: '确定要从历史记录中删除此项吗？',
            confirmRemoveAll: '确定要清空下载历史吗？',

            // 错误消息
            copyFailed: '复制失败: ',
            pauseFailed: '暂停失败: ',
            resumeFailed: '继续失败: ',
            removeFailed: '删除失败: ',
            removeAllFailed: '清空失败: ',
            openFailed: '打开文件失败: ',
            showFailed: '显示文件失败: ',
        },
        'zh-CHT': {
            locale: 'zh-TW',
            title: '下載管理器',

            // 界面文本
            searchPlaceholder: '搜尋檔案名稱或連結...',
            noItems: '暫無任務',
            noSearchResult: '未找到匹配的結果',

            // 操作按钮
            refresh: '重新整理',
            removeAll: "清空歷史",
            copyLink: '複製連結',
            resume: '繼續',
            pause: '暫停',
            cancel: '取消',
            remove: '刪除',
            showInFolder: '顯示在資料夾',

            // 状态文本
            progressing: '下載中',
            completed: '已完成',
            cancelled: '已取消',
            interrupted: '失敗',
            paused: '已暫停',

            // 成功消息
            copied: "已複製",
            refreshSuccess: '重新整理成功',

            // 确认对话框
            confirmCancel: '確定要取消此下載任務並刪除記錄嗎？',
            confirmRemove: '確定要從歷史記錄中刪除此項嗎？',
            confirmRemoveAll: '確定要清空下載歷史嗎？',

            // 错误消息
            copyFailed: '複製失敗: ',
            pauseFailed: '暫停失敗: ',
            resumeFailed: '繼續失敗: ',
            removeFailed: '刪除失敗: ',
            removeAllFailed: '清空失敗: ',
            openFailed: '開啟檔案失敗: ',
            showFailed: '顯示檔案失敗: ',
        },
        en: {
            locale: 'en-US',
            title: 'Download Manager',

            // 界面文本
            searchPlaceholder: 'Search filename or link...',
            noItems: 'No tasks',
            noSearchResult: 'No matching results found',

            // 操作按钮
            refresh: 'Refresh',
            removeAll: "Clear History",
            copyLink: 'Copy Link',
            resume: 'Resume',
            pause: 'Pause',
            cancel: 'Cancel',
            remove: 'Remove',
            showInFolder: 'Show in Folder',

            // 状态文本
            progressing: 'Downloading',
            completed: 'Completed',
            cancelled: 'Cancelled',
            interrupted: 'Failed',
            paused: 'Paused',

            // 成功消息
            copied: "Copied",
            refreshSuccess: 'Refresh successful',

            // 确认对话框
            confirmCancel: 'Are you sure you want to cancel this download task and delete the record?',
            confirmRemove: 'Are you sure you want to remove this item from history?',
            confirmRemoveAll: 'Are you sure you want to clear download history?',

            // 错误消息
            copyFailed: 'Copy failed: ',
            pauseFailed: 'Pause failed: ',
            resumeFailed: 'Resume failed: ',
            removeFailed: 'Remove failed: ',
            removeAllFailed: 'Clear failed: ',
            openFailed: 'Open file failed: ',
            showFailed: 'Show file failed: ',
        },
        ko: {
            locale: 'ko-KR',
            title: '다운로드 관리자',

            // 界面文本
            searchPlaceholder: '파일명 또는 링크 검색...',
            noItems: '작업 없음',
            noSearchResult: '일치하는 결과를 찾을 수 없습니다',

            // 操作按钮
            refresh: '새로고침',
            removeAll: "기록 지우기",
            copyLink: '링크 복사',
            resume: '계속',
            pause: '일시정지',
            cancel: '취소',
            remove: '삭제',
            showInFolder: '폴더에서 보기',

            // 状态文本
            progressing: '다운로드 중',
            completed: '완료됨',
            cancelled: '취소됨',
            interrupted: '실패',
            paused: '일시정지됨',

            // 成功消息
            copied: "복사됨",
            refreshSuccess: '새로고침 성공',

            // 确认对话框
            confirmCancel: '이 다운로드 작업을 취소하고 기록을 삭제하시겠습니까?',
            confirmRemove: '기록에서 이 항목을 삭제하시겠습니까?',
            confirmRemoveAll: '다운로드 기록을 지우시겠습니까?',

            // 错误消息
            copyFailed: '복사 실패: ',
            pauseFailed: '일시정지 실패: ',
            resumeFailed: '계속 실패: ',
            removeFailed: '삭제 실패: ',
            removeAllFailed: '지우기 실패: ',
            openFailed: '파일 열기 실패: ',
            showFailed: '파일 표시 실패: ',
        },
        ja: {
            locale: 'ja-JP',
            title: 'ダウンロードマネージャー',

            // 界面文本
            searchPlaceholder: 'ファイル名またはリンクを検索...',
            noItems: 'タスクがありません',
            noSearchResult: '一致する結果が見つかりません',

            // 操作按钮
            refresh: '更新',
            removeAll: "履歴をクリア",
            copyLink: 'リンクをコピー',
            resume: '再開',
            pause: '一時停止',
            cancel: 'キャンセル',
            remove: '削除',
            showInFolder: 'フォルダで表示',

            // 状态文本
            progressing: 'ダウンロード中',
            completed: '完了',
            cancelled: 'キャンセル済み',
            interrupted: '失敗',
            paused: '一時停止中',

            // 成功消息
            copied: "コピーしました",
            refreshSuccess: '更新が完了しました',

            // 确认对话框
            confirmCancel: 'このダウンロードタスクをキャンセルして記録を削除しますか？',
            confirmRemove: '履歴からこの項目を削除しますか？',
            confirmRemoveAll: 'ダウンロード履歴をクリアしますか？',

            // 错误消息
            copyFailed: 'コピーに失敗しました: ',
            pauseFailed: '一時停止に失敗しました: ',
            resumeFailed: '再開に失敗しました: ',
            removeFailed: '削除に失敗しました: ',
            removeAllFailed: 'クリアに失敗しました: ',
            openFailed: 'ファイルを開けませんでした: ',
            showFailed: 'ファイルの表示に失敗しました: ',
        },
        de: {
            locale: 'de-DE',
            title: 'Download-Manager',

            // 界面文本
            searchPlaceholder: 'Dateiname oder Link suchen...',
            noItems: 'Keine Aufgaben',
            noSearchResult: 'Keine übereinstimmenden Ergebnisse gefunden',

            // 操作按钮
            refresh: 'Aktualisieren',
            removeAll: "Verlauf löschen",
            copyLink: 'Link kopieren',
            resume: 'Fortsetzen',
            pause: 'Pause',
            cancel: 'Abbrechen',
            remove: 'Entfernen',
            showInFolder: 'Im Ordner anzeigen',

            // 状态文本
            progressing: 'Wird heruntergeladen',
            completed: 'Abgeschlossen',
            cancelled: 'Abgebrochen',
            interrupted: 'Fehlgeschlagen',
            paused: 'Pausiert',

            // 成功消息
            copied: "Kopiert",
            refreshSuccess: 'Erfolgreich aktualisiert',

            // 确认对话框
            confirmCancel: 'Sind Sie sicher, dass Sie diese Download-Aufgabe abbrechen und den Eintrag löschen möchten?',
            confirmRemove: 'Sind Sie sicher, dass Sie diesen Eintrag aus dem Verlauf entfernen möchten?',
            confirmRemoveAll: 'Sind Sie sicher, dass Sie den Download-Verlauf löschen möchten?',

            // 错误消息
            copyFailed: 'Kopieren fehlgeschlagen: ',
            pauseFailed: 'Pause fehlgeschlagen: ',
            resumeFailed: 'Fortsetzen fehlgeschlagen: ',
            removeFailed: 'Entfernen fehlgeschlagen: ',
            removeAllFailed: 'Löschen fehlgeschlagen: ',
            openFailed: 'Datei öffnen fehlgeschlagen: ',
            showFailed: 'Datei anzeigen fehlgeschlagen: ',
        },
        fr: {
            locale: 'fr-FR',
            title: 'Gestionnaire de téléchargements',

            // 界面文本
            searchPlaceholder: 'Rechercher nom de fichier ou lien...',
            noItems: 'Aucune tâche',
            noSearchResult: 'Aucun résultat correspondant trouvé',

            // 操作按钮
            refresh: 'Actualiser',
            removeAll: "Effacer l'historique",
            copyLink: 'Copier le lien',
            resume: 'Reprendre',
            pause: 'Pause',
            cancel: 'Annuler',
            remove: 'Supprimer',
            showInFolder: 'Afficher dans le dossier',

            // 状态文本
            progressing: 'Téléchargement en cours',
            completed: 'Terminé',
            cancelled: 'Annulé',
            interrupted: 'Échoué',
            paused: 'En pause',

            // 成功消息
            copied: "Copié",
            refreshSuccess: 'Actualisation réussie',

            // 确认对话框
            confirmCancel: 'Êtes-vous sûr de vouloir annuler cette tâche de téléchargement et supprimer l\'enregistrement ?',
            confirmRemove: 'Êtes-vous sûr de vouloir supprimer cet élément de l\'historique ?',
            confirmRemoveAll: 'Êtes-vous sûr de vouloir effacer l\'historique des téléchargements ?',

            // 错误消息
            copyFailed: 'Échec de la copie : ',
            pauseFailed: 'Échec de la pause : ',
            resumeFailed: 'Échec de la reprise : ',
            removeFailed: 'Échec de la suppression : ',
            removeAllFailed: 'Échec de l\'effacement : ',
            openFailed: 'Échec de l\'ouverture du fichier : ',
            showFailed: 'Échec de l\'affichage du fichier : ',
        },
        id: {
            locale: 'id-ID',
            title: 'Manajer Unduhan',

            // 界面文本
            searchPlaceholder: 'Cari nama file atau tautan...',
            noItems: 'Tidak ada tugas',
            noSearchResult: 'Tidak ada hasil yang cocok ditemukan',

            // 操作按钮
            refresh: 'Segarkan',
            removeAll: "Hapus Riwayat",
            copyLink: 'Salin Tautan',
            resume: 'Lanjutkan',
            pause: 'Jeda',
            cancel: 'Batal',
            remove: 'Hapus',
            showInFolder: 'Tampilkan di Folder',

            // 状态文本
            progressing: 'Mengunduh',
            completed: 'Selesai',
            cancelled: 'Dibatalkan',
            interrupted: 'Gagal',
            paused: 'Dijeda',

            // 成功消息
            copied: "Disalin",
            refreshSuccess: 'Berhasil disegarkan',

            // 确认对话框
            confirmCancel: 'Apakah Anda yakin ingin membatalkan tugas unduhan ini dan menghapus catatan?',
            confirmRemove: 'Apakah Anda yakin ingin menghapus item ini dari riwayat?',
            confirmRemoveAll: 'Apakah Anda yakin ingin menghapus riwayat unduhan?',

            // 错误消息
            copyFailed: 'Gagal menyalin: ',
            pauseFailed: 'Gagal menjeda: ',
            resumeFailed: 'Gagal melanjutkan: ',
            removeFailed: 'Gagal menghapus: ',
            removeAllFailed: 'Gagal menghapus: ',
            openFailed: 'Gagal membuka file: ',
            showFailed: 'Gagal menampilkan file: ',
        },
        ru: {
            locale: 'ru-RU',
            title: 'Менеджер загрузок',

            // 界面文本
            searchPlaceholder: 'Поиск имени файла или ссылки...',
            noItems: 'Нет задач',
            noSearchResult: 'Совпадающих результатов не найдено',

            // 操作按钮
            refresh: 'Обновить',
            removeAll: "Очистить историю",
            copyLink: 'Копировать ссылку',
            resume: 'Возобновить',
            pause: 'Пауза',
            cancel: 'Отмена',
            remove: 'Удалить',
            showInFolder: 'Показать в папке',

            // 状态文本
            progressing: 'Загружается',
            completed: 'Завершено',
            cancelled: 'Отменено',
            interrupted: 'Ошибка',
            paused: 'На паузе',

            // 成功消息
            copied: "Скопировано",
            refreshSuccess: 'Успешно обновлено',

            // 确认对话框
            confirmCancel: 'Вы уверены, что хотите отменить эту задачу загрузки и удалить запись?',
            confirmRemove: 'Вы уверены, что хотите удалить этот элемент из истории?',
            confirmRemoveAll: 'Вы уверены, что хотите очистить историю загрузок?',

            // 错误消息
            copyFailed: 'Ошибка копирования: ',
            pauseFailed: 'Ошибка паузы: ',
            resumeFailed: 'Ошибка возобновления: ',
            removeFailed: 'Ошибка удаления: ',
            removeAllFailed: 'Ошибка очистки: ',
            openFailed: 'Ошибка открытия файла: ',
            showFailed: 'Ошибка отображения файла: ',
        }
    };
    downloadLanguageCode = code;
    return packs[code] || packs.zh;
}

async function open(language = 'zh', theme = 'light') {
    // 获取语言包
    const finalLanguage = getLanguageData(language);

    // 如果窗口已存在，直接显示
    if (downloadWindow) {
        // 更新窗口数据
        await updateWindow(language, theme)
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
    const downloadWindowBounds = DownloadStore.get('downloadWindowBounds', {});
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
        downloadWindowOptions.center = false;
        downloadWindowOptions.width = downloadWindowBounds.width;
        downloadWindowOptions.height = downloadWindowBounds.height;
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
        DownloadStore.set('downloadWindowBounds', bounds);
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
        updateWindow(language, theme)
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

async function updateWindow(language, theme) {
    if (downloadWindow) {
        try {
            const finalLanguage = getLanguageData(language);
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
    createDownload,
    open,
    close,
    destroy,
    updateWindow
}
