const fs = require('fs')
const os = require("os");
const path = require('path')
const {app, BrowserWindow, ipcMain, dialog, clipboard, nativeImage, shell, Tray, Menu} = require('electron')
const {autoUpdater} = require("electron-updater")
const log = require("electron-log");
const fsProm = require('fs/promises');
const PDFDocument = require('pdf-lib').PDFDocument;
const crc = require('crc');
const zlib = require('zlib');
const utils = require('./utils');
const config = require('./package.json');

let mainWindow = null,
    mainTray = null,
    subWindow = [],
    willQuitApp = false,
    devloadUrl = "",
    devloadCachePath = path.resolve(__dirname, ".devload");

if (fs.existsSync(devloadCachePath)) {
    devloadUrl = fs.readFileSync(devloadCachePath, 'utf8')
}

/**
 * 创建主窗口
 */
function createMainWindow() {
    mainWindow = new BrowserWindow({
        width: 1280,
        height: 800,
        center: true,
        autoHideMenuBar: true,
        webPreferences: {
            preload: path.join(__dirname, 'electron-preload.js'),
            webSecurity: true,
            nodeIntegration: true,
            contextIsolation: true,
            nativeWindowOpen: true
        }
    })
    mainWindow.webContents.setUserAgent(mainWindow.webContents.getUserAgent() + " MainTaskWindow/" + process.platform + "/" + os.arch() + "/1.0");

    if (devloadUrl) {
        mainWindow.loadURL(devloadUrl).then(_ => {

        })
    } else {
        mainWindow.loadFile('./public/index.html').then(_ => {

        })
    }

    mainWindow.on('page-title-updated', (event, title) => {
        if (title == "index.html") {
            event.preventDefault()
        }
    })

    mainWindow.on('close', event => {
        if (!willQuitApp) {
            utils.onBeforeUnload(event).then(() => {
                if (process.platform === 'win32') {
                    mainWindow.hide()
                } else if (process.platform === 'darwin') {
                    app.hide()
                } else {
                    app.quit()
                }
            })
        }
    })
}

/**
 * 创建子窗口
 * @param args {path, hash, title, titleFixed, force, userAgent, config, webPreferences}
 */
function createSubWindow(args) {
    if (!args) {
        return;
    }

    if (!utils.isJson(args)) {
        args = {path: args, config: {}}
    }

    let name = args.name || "auto_" + utils.randomString(6);
    let item = subWindow.find(item => item.name == name);
    let browser = item ? item.browser : null;
    if (browser) {
        browser.focus();
        if (args.force === false) {
            return;
        }
    } else {
        let config = args.config || {};
        let webPreferences = args.webPreferences || {};
        browser = new BrowserWindow(Object.assign({
            width: 1280,
            height: 800,
            center: true,
            parent: mainWindow,
            autoHideMenuBar: true,
            webPreferences: Object.assign({
                preload: path.join(__dirname, 'electron-preload.js'),
                webSecurity: true,
                nodeIntegration: true,
                contextIsolation: true,
                nativeWindowOpen: true
            }, webPreferences),
        }, config))

        browser.on('page-title-updated', (event, title) => {
            if (title == "index.html" || config.titleFixed === true) {
                event.preventDefault()
            }
        })

        browser.on('close', event => {
            if (!willQuitApp) {
                utils.onBeforeUnload(event).then(() => {
                    event.sender.destroy()
                })
            }
        })

        browser.on('closed', () => {
            let index = subWindow.findIndex(item => item.name == name);
            if (index > -1) {
                subWindow.splice(index, 1)
            }
        })

        subWindow.push({ name, browser })
    }
    browser.webContents.setUserAgent(browser.webContents.getUserAgent() + " SubTaskWindow/" + process.platform + "/" + os.arch() + "/1.0" + (args.userAgent ? (" " + args.userAgent) : ""));

    if (devloadUrl) {
        browser.loadURL(devloadUrl + '#' + (args.hash || args.path)).then(_ => {

        })
    } else {
        browser.loadFile('./public/index.html', {
            hash: args.hash || args.path
        }).then(_ => {

        })
    }
}

const getTheLock = app.requestSingleInstanceLock()
if (!getTheLock) {
    app.quit()
} else {
    app.on('second-instance', () => {
        utils.setShowWindow(mainWindow)
    })
    app.on('ready', () => {
        // 创建主窗口
        createMainWindow()
        // 创建托盘
        if (['darwin', 'win32'].includes(process.platform) && utils.isJson(config.trayIcon)) {
            mainTray = new Tray(path.join(__dirname, config.trayIcon[devloadUrl ? 'dev' : 'prod'][process.platform === 'darwin' ? 'mac' : 'win']));
            mainTray.on('click', () => {
                utils.setShowWindow(mainWindow)
            })
            mainTray.setToolTip(config.name)
            if (process.platform === 'win32') {
                const trayMenu = Menu.buildFromTemplate([{
                    label: '显示',
                    click: () => {
                        utils.setShowWindow(mainWindow)
                    }
                }, {
                    label: '退出',
                    click: () => {
                        app.quit()
                    }
                }])
                mainTray.setContextMenu(trayMenu)
            }
        }
    })
}

app.on('activate', () => {
    if (BrowserWindow.getAllWindows().length === 0) createMainWindow()
})

app.on('window-all-closed', () => {
    if (willQuitApp || process.platform !== 'darwin') {
        app.quit()
    }
})

app.on('before-quit', () => {
    willQuitApp = true
})

/**
 * 打开文件
 * @param args {path}
 */
ipcMain.on('openFile', (event, args) => {
    utils.openFile(args.path)
    event.returnValue = "ok"
})

/**
 * 退出客户端
 */
ipcMain.on('windowQuit', (event) => {
    event.returnValue = "ok"
    app.quit();
})

/**
 * 创建路由窗口
 * @param args {path, ?}
 */
ipcMain.on('windowRouter', (event, args) => {
    createSubWindow(args)
    event.returnValue = "ok"
})

/**
 * 隐藏窗口（mac、win隐藏，其他关闭）
 */
ipcMain.on('windowHidden', (event) => {
    if (['darwin', 'win32'].includes(process.platform)) {
        app.hide();
    } else {
        app.quit();
    }
    event.returnValue = "ok"
})

/**
 * 关闭窗口
 */
ipcMain.on('windowClose', (event) => {
    const win = BrowserWindow.fromWebContents(event.sender);
    win.close()
    event.returnValue = "ok"
})

/**
 * 销毁窗口
 */
ipcMain.on('windowDestroy', (event) => {
    const win = BrowserWindow.fromWebContents(event.sender);
    win.destroy()
    event.returnValue = "ok"
})

/**
 * 关闭所有子窗口
 */
ipcMain.on('subWindowCloseAll', (event) => {
    subWindow.some(({browser}) => {
        browser && browser.close()
    })
    event.returnValue = "ok"
})

/**
 * 销毁所有子窗口
 */
ipcMain.on('subWindowDestroyAll', (event) => {
    subWindow.some(({browser}) => {
        browser && browser.destroy()
    })
    event.returnValue = "ok"
})

/**
 * 设置窗口尺寸
 * @param args {width, height, autoZoom, minWidth, minHeight, maxWidth, maxHeight}
 */
ipcMain.on('windowSize', (event, args) => {
    const win = BrowserWindow.fromWebContents(event.sender);
    if (win) {
        if (args.width || args.height) {
            let [w, h] = win.getSize()
            const width = args.width || w
            const height = args.height || h
            win.setSize(width, height, args.animate === true)
            //
            if (args.autoZoom === true) {
                let move = false
                let [x, y] = win.getPosition()
                if (Math.abs(width - w) > 10) {
                    move = true
                    x -= (width - w) / 2
                }
                if (Math.abs(height - h) > 10) {
                    move = true
                    y -= (height - h) / 2
                }
                if (move) {
                    win.setPosition(Math.max(0, Math.floor(x)), Math.max(0, Math.floor(y)))
                }
            }
        }
        if (args.minWidth || args.minHeight) {
            win.setMinimumSize(args.minWidth || win.getMinimumSize()[0], args.minHeight || win.getMinimumSize()[1])
        }
        if (args.maxWidth || args.maxHeight) {
            win.setMaximumSize(args.maxWidth || win.getMaximumSize()[0], args.maxHeight || win.getMaximumSize()[1])
        }
    }
    event.returnValue = "ok"
})

/**
 * 设置窗口最小尺寸
 * @param args {minWidth, minHeight}
 */
ipcMain.on('windowMinSize', (event, args) => {
    const win = BrowserWindow.fromWebContents(event.sender);
    if (win) {
        win.setMinimumSize(args.minWidth || win.getMinimumSize()[0], args.minHeight || win.getMinimumSize()[1])
    }
    event.returnValue = "ok"
})

/**
 * 设置窗口最大尺寸
 * @param args {maxWidth, maxHeight}
 */
ipcMain.on('windowMaxSize', (event, args) => {
    const win = BrowserWindow.fromWebContents(event.sender);
    if (win) {
        win.setMaximumSize(args.maxWidth || win.getMaximumSize()[0], args.maxHeight || win.getMaximumSize()[1])
    }
    event.returnValue = "ok"
})

/**
 * 窗口居中
 */
ipcMain.on('windowCenter', (event) => {
    const win = BrowserWindow.fromWebContents(event.sender);
    if (win) {
        win.center();
    }
    event.returnValue = "ok"
})

/**
 * 窗口最大化或恢复
 */
ipcMain.on('windowMax', (event) => {
    const win = BrowserWindow.fromWebContents(event.sender);
    if (win.isMaximized()) {
        win.restore();
    } else {
        win.maximize();
    }
    event.returnValue = "ok"
})

/**
 * 给主窗口发送信息
 * @param args {channel, data}
 */
ipcMain.on('sendForwardMain', (event, args) => {
    if (mainWindow) {
        mainWindow.webContents.send(args.channel, args.data)
    }
    event.returnValue = "ok"
})

/**
 * 设置Dock标记
 * @param args
 */
ipcMain.on('setDockBadge', (event, args) => {
    if(process.platform !== 'darwin'){
        // Mac only
        return;
    }
    let num = args;
    let tray = true;
    if (utils.isJson(args)) {
        num = args.num
        tray = !!args.tray
    }
    let text = utils.runNum(num) > 0 ? String(num) : ""
    app.dock.setBadge(text)
    if (tray && mainTray) {
        mainTray.setTitle(text)
    }
    event.returnValue = "ok"
})

//================================================================
// Update
//================================================================

let autoUpdating = 0
autoUpdater.logger = log
autoUpdater.autoDownload = false
autoUpdater.on('update-available', info => {
    mainWindow.webContents.send("updateAvailable", info)
})
autoUpdater.on('update-downloaded', info => {
    mainWindow.webContents.send("updateDownloaded", info)
})

/**
 * 检查更新
 */
ipcMain.on('updateCheckAndDownload', (event, args) => {
    event.returnValue = "ok"
    if (autoUpdating + 3600 > utils.Time()) {
        return  // 限制1小时仅执行一次
    }
    if (args.provider) {
        autoUpdater.setFeedURL(args)
    }
    autoUpdater.checkForUpdates().then(info => {
        if (utils.compareVersion(config.version, info.updateInfo.version) >= 0) {
            return
        }
        if (args.apiVersion) {
            if (utils.compareVersion(info.updateInfo.version, args.apiVersion) === 0) {
                autoUpdating = utils.Time()
                autoUpdater.downloadUpdate().then(_ => {}).catch(_ => {})
            }
        } else {
            autoUpdating = utils.Time()
            autoUpdater.downloadUpdate().then(_ => {}).catch(_ => {})
        }
    })
})

/**
 * 退出并安装更新
 */
ipcMain.on('updateQuitAndInstall', (event) => {
    event.returnValue = "ok"
    willQuitApp = true
    setTimeout(() => autoUpdater.quitAndInstall(), 1)
})

//================================================================
// Pdf export
//================================================================

const MICRON_TO_PIXEL = 264.58 		//264.58 micron = 1 pixel
const PNG_CHUNK_IDAT = 1229209940;
const LARGE_IMAGE_AREA = 30000000;

//NOTE: Key length must not be longer than 79 bytes (not checked)
function writePngWithText(origBuff, key, text, compressed, base64encoded) {
    let isDpi = key == 'dpi';
    let inOffset = 0;
    let outOffset = 0;
    let data = text;
    let dataLen = isDpi ? 9 : key.length + data.length + 1; //we add 1 zeros with non-compressed data, for pHYs it's 2 of 4-byte-int + 1 byte

    //prepare compressed data to get its size
    if (compressed) {
        data = zlib.deflateRawSync(encodeURIComponent(text));
        dataLen = key.length + data.length + 2; //we add 2 zeros with compressed data
    }

    let outBuff = Buffer.allocUnsafe(origBuff.length + dataLen + 4); //4 is the header size "zTXt", "tEXt" or "pHYs"

    try {
        let magic1 = origBuff.readUInt32BE(inOffset);
        inOffset += 4;
        let magic2 = origBuff.readUInt32BE(inOffset);
        inOffset += 4;

        if (magic1 != 0x89504e47 && magic2 != 0x0d0a1a0a) {
            throw new Error("PNGImageDecoder0");
        }

        outBuff.writeUInt32BE(magic1, outOffset);
        outOffset += 4;
        outBuff.writeUInt32BE(magic2, outOffset);
        outOffset += 4;
    } catch (e) {
        log.error(e.message, {stack: e.stack});
        throw new Error("PNGImageDecoder1");
    }

    try {
        while (inOffset < origBuff.length) {
            let length = origBuff.readInt32BE(inOffset);
            inOffset += 4;
            let type = origBuff.readInt32BE(inOffset)
            inOffset += 4;

            if (type == PNG_CHUNK_IDAT) {
                // Insert zTXt chunk before IDAT chunk
                outBuff.writeInt32BE(dataLen, outOffset);
                outOffset += 4;

                let typeSignature = isDpi ? 'pHYs' : (compressed ? "zTXt" : "tEXt");
                outBuff.write(typeSignature, outOffset);

                outOffset += 4;

                if (isDpi) {
                    let dpm = Math.round(parseInt(text) / 0.0254) || 3937; //One inch is equal to exactly 0.0254 meters. 3937 is 100dpi

                    outBuff.writeInt32BE(dpm, outOffset);
                    outBuff.writeInt32BE(dpm, outOffset + 4);
                    outBuff.writeInt8(1, outOffset + 8);
                    outOffset += 9;

                    data = Buffer.allocUnsafe(9);
                    data.writeInt32BE(dpm, 0);
                    data.writeInt32BE(dpm, 4);
                    data.writeInt8(1, 8);
                } else {
                    outBuff.write(key, outOffset);
                    outOffset += key.length;
                    outBuff.writeInt8(0, outOffset);
                    outOffset++;

                    if (compressed) {
                        outBuff.writeInt8(0, outOffset);
                        outOffset++;
                        data.copy(outBuff, outOffset);
                    } else {
                        outBuff.write(data, outOffset);
                    }

                    outOffset += data.length;
                }

                let crcVal = 0xffffffff;
                crcVal = crc.crcjam(typeSignature, crcVal);
                crcVal = crc.crcjam(data, crcVal);

                // CRC
                outBuff.writeInt32BE(crcVal ^ 0xffffffff, outOffset);
                outOffset += 4;

                // Writes the IDAT chunk after the zTXt
                outBuff.writeInt32BE(length, outOffset);
                outOffset += 4;
                outBuff.writeInt32BE(type, outOffset);
                outOffset += 4;

                origBuff.copy(outBuff, outOffset, inOffset);

                // Encodes the buffer using base64 if requested
                return base64encoded ? outBuff.toString('base64') : outBuff;
            }

            outBuff.writeInt32BE(length, outOffset);
            outOffset += 4;
            outBuff.writeInt32BE(type, outOffset);
            outOffset += 4;

            origBuff.copy(outBuff, outOffset, inOffset, inOffset + length + 4);// +4 to move past the crc

            inOffset += length + 4;
            outOffset += length + 4;
        }
    } catch (e) {
        log.error(e.message, {stack: e.stack});
        throw e;
    }
}

//TODO Create a lightweight html file similar to export3.html for exporting to vsdx
function exportVsdx(event, args, directFinalize) {

    let win = new BrowserWindow({
        width: 1280,
        height: 800,
        show: false,
        webPreferences: {
            preload: path.join(__dirname, 'electron-preload.js'),
            webSecurity: true,
            nodeIntegration: true,
            contextIsolation: true,
            nativeWindowOpen: true
        },
    })

    let loadEvtCount = 0;

    function loadFinished() {
        loadEvtCount++;

        if (loadEvtCount == 2) {
            win.webContents.send('export-vsdx', args);

            ipcMain.once('export-vsdx-finished', (evt, data) => {
                let hasError = false;

                if (data == null) {
                    hasError = true;
                }

                //Set finalize here since it is call in the reply below
                function finalize() {
                    win.destroy();
                }

                if (directFinalize === true) {
                    event.finalize = finalize;
                } else {
                    //Destroy the window after response being received by caller
                    ipcMain.once('export-finalize', finalize);
                }

                if (hasError) {
                    event.reply('export-error');
                } else {
                    event.reply('export-success', data);
                }
            });
        }
    }

    //Order of these two events is not guaranteed, so wait for them async.
    //TOOD There is still a chance we catch another window 'app-load-finished' if user created multiple windows quickly
    ipcMain.once('app-load-finished', loadFinished);
    win.webContents.on('did-finish-load', loadFinished);
}

async function mergePdfs(pdfFiles, xml) {
    //Pass throgh single files
    if (pdfFiles.length == 1 && xml == null) {
        return pdfFiles[0];
    }

    try {
        const pdfDoc = await PDFDocument.create();
        pdfDoc.setCreator(config.name);

        if (xml != null) {
            //Embed diagram XML as file attachment
            await pdfDoc.attach(Buffer.from(xml).toString('base64'), config.name + '.xml', {
                mimeType: 'application/vnd.jgraph.mxfile',
                description: config.name + ' Content'
            });
        }

        for (let i = 0; i < pdfFiles.length; i++) {
            const pdfFile = await PDFDocument.load(pdfFiles[i].buffer);
            const pages = await pdfDoc.copyPages(pdfFile, pdfFile.getPageIndices());
            pages.forEach(p => pdfDoc.addPage(p));
        }

        const pdfBytes = await pdfDoc.save();
        return Buffer.from(pdfBytes);
    } catch (e) {
        throw new Error('Error during PDF combination: ' + e.message);
    }
}

//TODO Use canvas to export images if math is not used to speedup export (no capturePage). Requires change to export3.html also
function exportDiagram(event, args, directFinalize) {
    if (args.format == 'vsdx') {
        exportVsdx(event, args, directFinalize);
        return;
    }

    let browser = null;

    try {
        browser = new BrowserWindow({
            webPreferences: {
                preload: path.join(__dirname, 'electron-preload.js'),
                backgroundThrottling: false,
                contextIsolation: true,
                nativeWindowOpen: true
            },
            show: false,
            frame: false,
            enableLargerThanScreen: true,
            transparent: args.format == 'png' && (args.bg == null || args.bg == 'none'),
        });

        browser.loadURL(`file://${__dirname}/export3.html`);

        const contents = browser.webContents;
        let pageByPage = (args.format == 'pdf' && !args.print), from, to, pdfs;

        if (pageByPage) {
            from = args.allPages ? 0 : parseInt(args.from || 0);
            to = args.allPages ? 1000 : parseInt(args.to || 1000) + 1; //The 'to' will be corrected later
            pdfs = [];

            args.from = from;
            args.to = from;
            args.allPages = false;
        }

        contents.on('did-finish-load', function () {
            //Set finalize here since it is call in the reply below
            function finalize() {
                browser.destroy();
            }

            if (directFinalize === true) {
                event.finalize = finalize;
            } else {
                //Destroy the window after response being received by caller
                ipcMain.once('export-finalize', finalize);
            }

            function renderingFinishHandler(evt, renderInfo) {
                if (renderInfo == null) {
                    event.reply('export-error');
                    return;
                }

                let pageCount = renderInfo.pageCount, bounds = null;
                //For some reason, Electron 9 doesn't send this object as is without stringifying. Usually when variable is external to function own scope
                try {
                    bounds = JSON.parse(renderInfo.bounds);
                } catch (e) {
                    bounds = null;
                }

                let pdfOptions = {pageSize: 'A4'};
                let hasError = false;

                if (bounds == null || bounds.width < 5 || bounds.height < 5) //very small page size never return from printToPDF
                {
                    //A workaround to detect errors in the input file or being empty file
                    hasError = true;
                } else {
                    //Chrome generates Pdf files larger than requested pixels size and requires scaling
                    let fixingScale = 0.959;

                    let w = Math.ceil(bounds.width * fixingScale);

                    // +0.1 fixes cases where adding 1px below is not enough
                    // Increase this if more cropped PDFs have extra empty pages
                    let h = Math.ceil(bounds.height * fixingScale + 0.1);

                    pdfOptions = {
                        printBackground: true,
                        pageSize: {
                            width: w * MICRON_TO_PIXEL,
                            height: (h + 2) * MICRON_TO_PIXEL //the extra 2 pixels to prevent adding an extra empty page
                        },
                        marginsType: 1 // no margin
                    }
                }

                let base64encoded = args.base64 == '1';

                if (hasError) {
                    event.reply('export-error');
                } else if (args.format == 'png' || args.format == 'jpg' || args.format == 'jpeg') {
                    //Adds an extra pixel to prevent scrollbars from showing
                    let newBounds = {
                        width: Math.ceil(bounds.width + bounds.x) + 1,
                        height: Math.ceil(bounds.height + bounds.y) + 1
                    };
                    browser.setBounds(newBounds);

                    //TODO The browser takes sometime to show the graph (also after resize it takes some time to render)
                    //	 	1 sec is most probably enough (for small images, 5 for large ones) BUT not a stable solution
                    setTimeout(function () {
                        browser.capturePage().then(function (img) {
                            //Image is double the given bounds, so resize is needed!
                            let tScale = 1;

                            //If user defined width and/or height, enforce it precisely here. Height override width
                            if (args.h) {
                                tScale = args.h / newBounds.height;
                            } else if (args.w) {
                                tScale = args.w / newBounds.width;
                            }

                            newBounds.width *= tScale;
                            newBounds.height *= tScale;
                            img = img.resize(newBounds);

                            let data = args.format == 'png' ? img.toPNG() : img.toJPEG(args.jpegQuality || 90);

                            if (args.dpi != null && args.format == 'png') {
                                data = writePngWithText(data, 'dpi', args.dpi);
                            }

                            if (args.embedXml == "1" && args.format == 'png') {
                                data = writePngWithText(data, "mxGraphModel", args.xml, true,
                                    base64encoded);
                            } else {
                                if (base64encoded) {
                                    data = data.toString('base64');
                                }
                            }

                            event.reply('export-success', data);
                        });
                    }, bounds.width * bounds.height < LARGE_IMAGE_AREA ? 1000 : 5000);
                } else if (args.format == 'pdf') {
                    if (args.print) {
                        pdfOptions = {
                            scaleFactor: args.pageScale,
                            printBackground: true,
                            pageSize: {
                                width: args.pageWidth * MICRON_TO_PIXEL,
                                //This height adjustment fixes the output. TODO Test more cases
                                height: (args.pageHeight * 1.025) * MICRON_TO_PIXEL
                            },
                            marginsType: 1 // no margin
                        };

                        contents.print(pdfOptions, (success, errorType) => {
                            //Consider all as success
                            event.reply('export-success', {});
                        });
                    } else {
                        contents.printToPDF(pdfOptions).then(async (data) => {
                            pdfs.push(data);
                            to = to > pageCount ? pageCount : to;
                            from++;

                            if (from < to) {
                                args.from = from;
                                args.to = from;
                                ipcMain.once('render-finished', renderingFinishHandler);
                                contents.send('render', args);
                            } else {
                                data = await mergePdfs(pdfs, args.embedXml == '1' ? args.xml : null);
                                event.reply('export-success', data);
                            }
                        })
                            .catch((error) => {
                                event.reply('export-error', error);
                            });
                    }
                } else if (args.format == 'svg') {
                    contents.send('get-svg-data');

                    ipcMain.once('svg-data', (evt, data) => {
                        event.reply('export-success', data);
                    });
                } else {
                    event.reply('export-error', 'Error: Unsupported format');
                }
            }

            ipcMain.once('render-finished', renderingFinishHandler);

            if (args.format == 'xml') {
                ipcMain.once('xml-data', (evt, data) => {
                    event.reply('export-success', data);
                });

                ipcMain.once('xml-data-error', () => {
                    event.reply('export-error');
                });
            }

            args.border = args.border || 0;
            args.scale = args.scale || 1;

            contents.send('render', args);
        });
    } catch (e) {
        if (browser != null) {
            browser.destroy();
        }

        event.reply('export-error', e);
        console.log('export-error', e);
    }
}

ipcMain.on('export', exportDiagram);

//================================================================
// Renderer Helper functions
//================================================================

const {COPYFILE_EXCL} = fs.constants;
const DRAFT_PREFEX = '~$';
const DRAFT_EXT = '.dtmp';
const BKP_PREFEX = '~$';
const BKP_EXT = '.bkp';

function isConflict(origStat, stat) {
    return stat != null && origStat != null && stat.mtimeMs != origStat.mtimeMs;
}

function getDraftFileName(fileObject) {
    let filePath = fileObject.path;
    let draftFileName = '', counter = 1, uniquePart = '';

    do {
        draftFileName = path.join(path.dirname(filePath), DRAFT_PREFEX + path.basename(filePath) + uniquePart + DRAFT_EXT);
        uniquePart = '_' + counter++;
    } while (fs.existsSync(draftFileName));

    return draftFileName;
}

async function getFileDrafts(fileObject) {
    let filePath = fileObject.path;
    let draftsPaths = [], drafts = [], draftFileName, counter = 1, uniquePart = '';

    do {
        draftsPaths.push(draftFileName);
        draftFileName = path.join(path.dirname(filePath), DRAFT_PREFEX + path.basename(filePath) + uniquePart + DRAFT_EXT);
        uniquePart = '_' + counter++;
    } while (fs.existsSync(draftFileName)); //TODO this assume continuous drafts names

    for (let i = 1; i < draftsPaths.length; i++) {
        try {
            let stat = await fsProm.lstat(draftsPaths[i]);
            drafts.push({
                data: await fsProm.readFile(draftsPaths[i], 'utf8'),
                created: stat.ctimeMs,
                modified: stat.mtimeMs,
                path: draftsPaths[i]
            });
        } catch (e) {
        } // Ignore
    }

    return drafts;
}

async function saveDraft(fileObject, data) {
    if (data == null || data.length == 0) {
        throw new Error('empty data');
    } else {
        let draftFileName = fileObject.draftFileName || getDraftFileName(fileObject);
        await fsProm.writeFile(draftFileName, data, 'utf8');
        return draftFileName;
    }
}

async function saveFile(fileObject, data, origStat, overwrite, defEnc) {
    let retryCount = 0;
    let backupCreated = false;
    let bkpPath = path.join(path.dirname(fileObject.path), BKP_PREFEX + path.basename(fileObject.path) + BKP_EXT);

    let writeFile = async function () {
        if (data == null || data.length == 0) {
            throw new Error('empty data');
        } else {
            let writeEnc = defEnc || fileObject.encoding;

            await fsProm.writeFile(fileObject.path, data, writeEnc);
            let stat2 = await fsProm.stat(fileObject.path);
            let writtenData = await fsProm.readFile(fileObject.path, writeEnc);

            if (data != writtenData) {
                retryCount++;

                if (retryCount < 3) {
                    return await writeFile();
                } else {
                    throw new Error('all saving trials failed');
                }
            } else {
                if (backupCreated) {
                    fs.unlink(bkpPath, (err) => {
                    }); //Ignore errors!
                }

                return stat2;
            }
        }
    };

    async function doSaveFile() {
        try {
            await fsProm.copyFile(fileObject.path, bkpPath, COPYFILE_EXCL);
            backupCreated = true;
        } catch (e) {
        } //Ignore

        return await writeFile();
    }

    if (overwrite) {
        return await doSaveFile();
    } else {
        let stat = fs.existsSync(fileObject.path) ?
            await fsProm.stat(fileObject.path) : null;

        if (stat && isConflict(origStat, stat)) {
            new Error('conflict');
        } else {
            return await doSaveFile();
        }
    }
}

async function writeFile(path, data, enc) {
    return await fsProm.writeFile(path, data, enc);
}

function getAppDataFolder() {
    try {
        let appDataDir = app.getPath('appData');
        let drawioDir = appDataDir + '/' + config.name;

        if (!fs.existsSync(drawioDir)) //Usually this dir already exists
        {
            fs.mkdirSync(drawioDir);
        }

        return drawioDir;
    } catch (e) {
    }

    return '.';
}

function getDocumentsFolder() {
    try {
        return app.getPath('documents');
    } catch (e) {
    }

    return '.';
}

function checkFileExists(pathParts) {
    let filePath = path.join(...pathParts);
    return {exists: fs.existsSync(filePath), path: filePath};
}

async function showOpenDialog(defaultPath, filters, properties) {
    return dialog.showOpenDialogSync({
        defaultPath: defaultPath,
        filters: filters,
        properties: properties
    });
}

async function showSaveDialog(defaultPath, filters) {
    return dialog.showSaveDialogSync({
        defaultPath: defaultPath,
        filters: filters
    });
}

async function installPlugin(filePath) {
    let pluginsDir = path.join(getAppDataFolder(), '/plugins');

    if (!fs.existsSync(pluginsDir)) {
        fs.mkdirSync(pluginsDir);
    }

    let pluginName = path.basename(filePath);
    let dstFile = path.join(pluginsDir, pluginName);

    if (fs.existsSync(dstFile)) {
        throw new Error('fileExists');
    } else {
        await fsProm.copyFile(filePath, dstFile);
    }

    return {pluginName: pluginName, selDir: path.dirname(filePath)};
}

function uninstallPlugin(plugin) {
    let pluginsFile = path.join(getAppDataFolder(), '/plugins', plugin);

    if (fs.existsSync(pluginsFile)) {
        fs.unlinkSync(pluginsFile);
    }
    return null
}

function dirname(path_p) {
    return path.dirname(path_p);
}

async function readFile(filename, encoding) {
    return await fsProm.readFile(filename, encoding);
}

async function fileStat(file) {
    return await fsProm.stat(file);
}

async function isFileWritable(file) {
    try {
        await fsProm.access(file, fs.constants.W_OK);
        return true;
    } catch (e) {
        return false;
    }
}

function clipboardAction(method, data) {
    if (method == 'writeText') {
        clipboard.writeText(data);
    } else if (method == 'readText') {
        return clipboard.readText();
    } else if (method == 'writeImage') {
        clipboard.write({
            image:
                nativeImage.createFromDataURL(data.dataUrl), html: '<img src="' +
                data.dataUrl + '" width="' + data.w + '" height="' + data.h + '">'
        });
    }
}

async function deleteFile(file) {
    await fsProm.unlink(file);
}

function windowAction(method) {
    let win = BrowserWindow.getFocusedWindow();

    if (win) {
        if (method == 'minimize') {
            win.minimize();
        } else if (method == 'maximize') {
            win.maximize();
        } else if (method == 'unmaximize') {
            win.unmaximize();
        } else if (method == 'close') {
            win.close();
        } else if (method == 'isMaximized') {
            return win.isMaximized();
        } else if (method == 'removeAllListeners') {
            win.removeAllListeners();
        }
    }
}

function openExternal(url) {
    shell.openExternal(url).then(() => {}).catch(() => {});
    return null
}

function watchFile(path) {
    let win = BrowserWindow.getFocusedWindow();

    if (win) {
        fs.watchFile(path, (curr, prev) => {
            try {
                win.webContents.send('fileChanged', {
                    path: path,
                    curr: curr,
                    prev: prev
                });
            } catch (e) {
            } // Ignore
        });
    }
    return null
}

function unwatchFile(path) {
    fs.unwatchFile(path);
    return null
}

ipcMain.on("rendererReq", async (event, args) => {
    try {
        let ret = null;

        switch (args.action) {
            case 'saveFile':
                ret = await saveFile(args.fileObject, args.data, args.origStat, args.overwrite, args.defEnc);
                break;
            case 'writeFile':
                ret = await writeFile(args.path, args.data, args.enc);
                break;
            case 'saveDraft':
                ret = await saveDraft(args.fileObject, args.data);
                break;
            case 'getFileDrafts':
                ret = await getFileDrafts(args.fileObject);
                break;
            case 'getAppDataFolder':
                ret = getAppDataFolder();
                break;
            case 'getDocumentsFolder':
                ret = await getDocumentsFolder();
                break;
            case 'checkFileExists':
                ret = checkFileExists(args.pathParts);
                break;
            case 'showOpenDialog':
                ret = await showOpenDialog(args.defaultPath, args.filters, args.properties);
                break;
            case 'showSaveDialog':
                ret = await showSaveDialog(args.defaultPath, args.filters);
                break;
            case 'installPlugin':
                ret = await installPlugin(args.filePath);
                break;
            case 'uninstallPlugin':
                ret = await uninstallPlugin(args.plugin);
                break;
            case 'dirname':
                ret = dirname(args.path);
                break;
            case 'readFile':
                ret = await readFile(args.filename, args.encoding);
                break;
            case 'clipboardAction':
                ret = clipboardAction(args.method, args.data);
                break;
            case 'deleteFile':
                ret = await deleteFile(args.file);
                break;
            case 'fileStat':
                ret = await fileStat(args.file);
                break;
            case 'isFileWritable':
                ret = await isFileWritable(args.file);
                break;
            case 'windowAction':
                ret = windowAction(args.method);
                break;
            case 'openExternal':
                ret = await openExternal(args.url);
                break;
            case 'watchFile':
                ret = await watchFile(args.path);
                break;
            case 'unwatchFile':
                ret = await unwatchFile(args.path);
                break;
        }

        event.reply('mainResp', {success: true, data: ret, reqId: args.reqId});
    } catch (e) {
        event.reply('mainResp', {error: true, msg: e.message, e: e, reqId: args.reqId});
    }
});
