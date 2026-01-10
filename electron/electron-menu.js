const {
    clipboard,
    nativeImage,
    Menu,
    MenuItem,
    dialog,
    shell,
} = require('electron')
const fs = require('fs')
const url = require('url')
const request = require("request");
const utils = require('./lib/utils')

const MAILTO_PREFIX = "mailto:";

const PERMITTED_URL_SCHEMES = ["http:", "https:", MAILTO_PREFIX];

const electronMenu = {
    language: {
        copy: "复制",
        back: "后退",
        forward: "前进",
        reload: "重新加载",
        print: "打印",
        openInBrowser: "在浏览器中打开",
        openInDefaultBrowser: "默认浏览器打开",
        saveImageAs: "图片存储为...",
        copyImage: "复制图片",
        copyEmailAddress: "复制电子邮件地址",
        copyLinkAddress: "复制链接地址",
        copyImageAddress: "复制图片地址",
        moveToNewWindow: "将标签页移至新窗口",
        failedToSaveImage: "图片保存失败",
        theImageFailedToSave: "图片无法保存",
    },

    setLanguage(language) {
        this.language = Object.assign(this.language, language);
    },

    safeOpenURL(target) {
        const parsedUrl = url.parse(target);
        if (PERMITTED_URL_SCHEMES.includes(parsedUrl.protocol)) {
            const newTarget = url.format(parsedUrl);
            shell.openExternal(newTarget).then(r => {
            });
        }
    },

    isBlob(url) {
        return url.startsWith("blob:");
    },

    isDataUrl(url) {
        return url.startsWith("data:");
    },

    isBlobOrDataUrl(url) {
        return electronMenu.isBlob(url) || electronMenu.isDataUrl(url);
    },

    async saveImageAs(url, params) {
        let extension = url.split('.').pop().split(/[#?]/)[0].toLowerCase();
        if (!['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'].includes(extension)) {
            extension = 'png';
        }

        let targetFileName = params.suggestedFilename || params.altText || "image";
        if (!targetFileName.toLowerCase().endsWith('.' + extension)) {
            targetFileName = targetFileName.replace(/\.[^/.]+$/, '') + '.' + extension;
        }

        const {filePath} = await dialog.showSaveDialog({
            defaultPath: targetFileName,
            filters: [
                { name: 'Images', extensions: ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'] }
            ]
        });

        if (!filePath) return; // user cancelled dialog

        try {
            if (electronMenu.isBlobOrDataUrl(url)) {
                await electronMenu.writeNativeImage(filePath, nativeImage.createFromDataURL(url));
            } else {
                const writeStream = fs.createWriteStream(filePath)
                const readStream = request(url)
                readStream.pipe(writeStream);
                readStream.on('end', function (response) {
                    writeStream.end();
                });
            }
        } catch (err) {
            await dialog.showMessageBox({
                type: "error",
                title: electronMenu.language.failedToSaveImage,
                message: electronMenu.language.theImageFailedToSave,
            });
        }
    },

    writeNativeImage(filePath, img) {
        switch (filePath.split(".").pop()?.toLowerCase()) {
            case "jpg":
            case "jpeg":
                return fs.promises.writeFile(filePath, img.toJPEG(100));
            case "bmp":
                return fs.promises.writeFile(filePath, img.toBitmap());
            case "png":
            default:
                return fs.promises.writeFile(filePath, img.toPNG());
        }
    },

    webContentsMenu(webContents, isBrowser = false) {
        webContents.on("context-menu", function (e, params) {
            const popupMenu = new Menu();
            if (params.linkURL || params.srcURL) {
                const url = params.linkURL || params.srcURL;

                if (!electronMenu.isBlobOrDataUrl(url) && !utils.isLocalHost(url)) {
                    popupMenu.append(new MenuItem({
                        label: electronMenu.language.openInBrowser,
                        click: async function () {
                            electronMenu.safeOpenURL(url);
                        },
                    }));
                }

                if (params.hasImageContents) {
                    if (!electronMenu.isBlob(url)) {
                        popupMenu.append(new MenuItem({
                            label: electronMenu.language.saveImageAs,
                            click: async function () {
                                await electronMenu.saveImageAs(url, params);
                            },
                        }));
                    }
                    popupMenu.append(new MenuItem({
                        label: electronMenu.language.copyImage,
                        click: async function () {
                            webContents.copyImageAt(params.x, params.y);
                        },
                    }));
                }

                if (!electronMenu.isBlobOrDataUrl(url)) {
                    if (url.startsWith(MAILTO_PREFIX)) {
                        popupMenu.append(new MenuItem({
                            label: electronMenu.language.copyEmailAddress,
                            click: async function () {
                                clipboard.writeText(url.substring(MAILTO_PREFIX.length));
                            },
                        }));
                    } else if (!utils.isLocalHost(url)) {
                        popupMenu.append(new MenuItem({
                            label: params.hasImageContents ? electronMenu.language.copyImageAddress : electronMenu.language.copyLinkAddress,
                            click: async function () {
                                clipboard.writeText(url);
                            },
                        }));
                    }
                }
            }

            if (isBrowser) {
                if (popupMenu.items.length > 0) {
                    popupMenu.insert(0, new MenuItem({type: 'separator'}))
                }

                popupMenu.insert(0, new MenuItem({
                    label: electronMenu.language.print,
                    click: () => webContents.print()
                }))

                popupMenu.insert(0, new MenuItem({
                    label: electronMenu.language.reload,
                    click: () => webContents.reload()
                }))

                popupMenu.insert(0, new MenuItem({
                    label: electronMenu.language.forward,
                    enabled: webContents.navigationHistory.canGoForward(),
                    click: () => webContents.navigationHistory.goForward()
                }))

                popupMenu.insert(0, new MenuItem({
                    label: electronMenu.language.back,
                    enabled: webContents.navigationHistory.canGoBack(),
                    click: () => webContents.navigationHistory.goBack()
                }))
            }

            if (params.selectionText) {
                if (popupMenu.items.length > 0) {
                    popupMenu.insert(0, new MenuItem({type: 'separator'}))
                }
                popupMenu.insert(0, new MenuItem({
                    label: electronMenu.language.copy,
                    role: 'copy'
                }))
            }

            if (popupMenu.items.length > 0) {
                popupMenu.popup({});
                e.preventDefault();
            }
        })
    },
}
module.exports = electronMenu;
