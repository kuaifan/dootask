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

const MAILTO_PREFIX = "mailto:";

const PERMITTED_URL_SCHEMES = ["http:", "https:", MAILTO_PREFIX];

const electronMenu = {
    language: {
        openInBrowser: "在浏览器中打开",
        saveImageAs: "图片存储为...",
        copyImage: "复制图片",
        copyEmailAddress: "复制电子邮件地址",
        copyLinkAddress: "复制链接地址",
        copyImageAddress: "复制图片地址",
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
        const targetFileName = params.suggestedFilename || params.altText || "image.png";
        const {filePath} = await dialog.showSaveDialog({
            defaultPath: targetFileName,
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

    webContentsMenu(webContents) {
        webContents.on("context-menu", function (e, params) {
            if (params.linkURL || params.srcURL) {
                const url = params.linkURL || params.srcURL;
                const popupMenu = new Menu();

                if (!electronMenu.isBlobOrDataUrl(url)) {
                    popupMenu.append(
                        new MenuItem({
                            label: electronMenu.language.openInBrowser,
                            accelerator: "o",
                            click() {
                                electronMenu.safeOpenURL(url);
                            },
                        }),
                    );
                }

                if (params.hasImageContents) {
                    if (!electronMenu.isBlob(url)) {
                        popupMenu.append(
                            new MenuItem({
                                label: electronMenu.language.saveImageAs,
                                accelerator: "s",
                                click: async function () {
                                    await electronMenu.saveImageAs(url, params);
                                },
                            }),
                        );
                    }
                    popupMenu.append(
                        new MenuItem({
                            label: electronMenu.language.copyImage,
                            accelerator: "c",
                            click() {
                                webContents.copyImageAt(params.x, params.y);
                            },
                        }),
                    );
                }

                if (!electronMenu.isBlobOrDataUrl(url)) {
                    if (url.startsWith(MAILTO_PREFIX)) {
                        popupMenu.append(
                            new MenuItem({
                                label: electronMenu.language.copyEmailAddress,
                                accelerator: "a",
                                click() {
                                    clipboard.writeText(url.substring(MAILTO_PREFIX.length));
                                },
                            }),
                        );
                    } else {
                        popupMenu.append(
                            new MenuItem({
                                label: params.hasImageContents ? electronMenu.language.copyImageAddress : electronMenu.language.copyLinkAddress,
                                accelerator: "a",
                                click() {
                                    clipboard.writeText(url);
                                },
                            }),
                        );
                    }
                }

                if (popupMenu.items.length > 0) {
                    popupMenu.popup({});
                    e.preventDefault();
                }
            }
        })
    }
}
module.exports = electronMenu;
