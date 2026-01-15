const fs = require("fs");
const {BrowserWindow, shell, app, dialog, clipboard, nativeImage, ipcMain} = require("electron");
const fsProm = require("fs/promises");
const path = require("path");
const {spawn} = require("child_process");
const config = require("../package.json");
const electronDown = require("../electron-down");
const loger = require("electron-log");
const {allowedUrls, isWin} = require("./other");

const {O_SYNC, O_CREAT, O_WRONLY, O_TRUNC, O_RDONLY} = fs.constants;
const DRAFT_PREFEX = '.$';
const OLD_DRAFT_PREFEX = '~$';
const DRAFT_EXT = '.dtmp';
const BKP_PREFEX = '.$';
const OLD_BKP_PREFEX = '~$';
const BKP_EXT = '.bkp';

let enableStoreBkp = true,
    dialogOpen = false,
    enablePlugins = false;

const renderer = {
    checkFileContent(body, enc) {
        if (body != null) {
            let head, headBinay;

            if (typeof body === 'string') {
                if (enc == 'base64') {
                    headBinay = Buffer.from(body.substring(0, 22), 'base64');
                    head = headBinay.toString();
                } else {
                    head = body.substring(0, 16);
                    headBinay = Buffer.from(head);
                }
            } else {
                head = new TextDecoder("utf-8").decode(body.subarray(0, 16));
                headBinay = body;
            }

            let c1 = head[0],
                c2 = head[1],
                c3 = head[2],
                c4 = head[3],
                c5 = head[4],
                c6 = head[5],
                c7 = head[6],
                c8 = head[7],
                c9 = head[8],
                c10 = head[9],
                c11 = head[10],
                c12 = head[11],
                c13 = head[12],
                c14 = head[13],
                c15 = head[14],
                c16 = head[15];

            let cc1 = headBinay[0],
                cc2 = headBinay[1],
                cc3 = headBinay[2],
                cc4 = headBinay[3],
                cc5 = headBinay[4],
                cc6 = headBinay[5],
                cc7 = headBinay[6],
                cc8 = headBinay[7],
                cc9 = headBinay[8],
                cc10 = headBinay[9],
                cc11 = headBinay[10],
                cc12 = headBinay[11],
                cc13 = headBinay[12],
                cc14 = headBinay[13],
                cc15 = headBinay[14],
                cc16 = headBinay[15];

            if (c1 == '<') {
                // text/html
                if (c2 == '!'
                    || ((c2 == 'h'
                        && (c3 == 't' && c4 == 'm' && c5 == 'l'
                            || c3 == 'e' && c4 == 'a' && c5 == 'd')
                        || (c2 == 'b' && c3 == 'o' && c4 == 'd'
                            && c5 == 'y')))
                    || ((c2 == 'H'
                        && (c3 == 'T' && c4 == 'M' && c5 == 'L'
                            || c3 == 'E' && c4 == 'A' && c5 == 'D')
                        || (c2 == 'B' && c3 == 'O' && c4 == 'D'
                            && c5 == 'Y')))) {
                    return true;
                }

                // application/xml
                if (c2 == '?' && c3 == 'x' && c4 == 'm' && c5 == 'l'
                    && c6 == ' ') {
                    return true;
                }

                // application/svg+xml
                if (c2 == 's' && c3 == 'v' && c4 == 'g' && c5 == ' ') {
                    return true;
                }
            }

            // big and little (identical) endian UTF-8 encodings, with BOM
            // application/xml
            if (cc1 == 0xef && cc2 == 0xbb && cc3 == 0xbf) {
                if (c4 == '<' && c5 == '?' && c6 == 'x') {
                    return true;
                }
            }

            // big and little endian UTF-16 encodings, with byte order mark
            // application/xml
            if (cc1 == 0xfe && cc2 == 0xff) {
                if (cc3 == 0 && c4 == '<' && cc5 == 0 && c6 == '?' && cc7 == 0
                    && c8 == 'x') {
                    return true;
                }
            }

            // application/xml
            if (cc1 == 0xff && cc2 == 0xfe) {
                if (c3 == '<' && cc4 == 0 && c5 == '?' && cc6 == 0 && c7 == 'x'
                    && cc8 == 0) {
                    return true;
                }
            }

            // big and little endian UTF-32 encodings, with BOM
            // application/xml
            if (cc1 == 0x00 && cc2 == 0x00 && cc3 == 0xfe && cc4 == 0xff) {
                if (cc5 == 0 && cc6 == 0 && cc7 == 0 && c8 == '<' && cc9 == 0
                    && cc10 == 0 && cc11 == 0 && c12 == '?' && cc13 == 0
                    && cc14 == 0 && cc15 == 0 && c16 == 'x') {
                    return true;
                }
            }

            // application/xml
            if (cc1 == 0xff && cc2 == 0xfe && cc3 == 0x00 && cc4 == 0x00) {
                if (c5 == '<' && cc6 == 0 && cc7 == 0 && cc8 == 0 && c9 == '?'
                    && cc10 == 0 && cc11 == 0 && cc12 == 0 && c13 == 'x'
                    && cc14 == 0 && cc15 == 0 && cc16 == 0) {
                    return true;
                }
            }

            // application/pdf (%PDF-)
            if (cc1 == 37 && cc2 == 80 && cc3 == 68 && cc4 == 70 && cc5 == 45) {
                return true;
            }

            // image/png
            if ((cc1 == 137 && cc2 == 80 && cc3 == 78 && cc4 == 71 && cc5 == 13
                    && cc6 == 10 && cc7 == 26 && cc8 == 10) ||
                (cc1 == 194 && cc2 == 137 && cc3 == 80 && cc4 == 78 && cc5 == 71 && cc6 == 13 //Our embedded PNG+XML
                    && cc7 == 10 && cc8 == 26 && cc9 == 10)) {
                return true;
            }

            // image/jpeg
            if (cc1 == 0xFF && cc2 == 0xD8 && cc3 == 0xFF) {
                if (cc4 == 0xE0 || cc4 == 0xEE) {
                    return true;
                }

                /**
                 * File format used by digital cameras to store images.
                 * Exif Format can be read by any application supporting
                 * JPEG. Exif Spec can be found at:
                 * http://www.pima.net/standards/it10/PIMA15740/Exif_2-1.PDF
                 */
                if ((cc4 == 0xE1) && (c7 == 'E' && c8 == 'x' && c9 == 'i'
                    && c10 == 'f' && cc11 == 0)) {
                    return true;
                }
            }

            // vsdx, vssx (also zip, jar, odt, ods, odp, docx, xlsx, pptx, apk, aar)
            if (cc1 == 0x50 && cc2 == 0x4B && cc3 == 0x03 && cc4 == 0x04) {
                return true;
            } else if (cc1 == 0x50 && cc2 == 0x4B && cc3 == 0x03 && cc4 == 0x06) {
                return true;
            }

            // mxfile, mxlibrary, mxGraphModel
            if (c1 == '<' && c2 == 'm' && c3 == 'x') {
                return true;
            }
        }

        return false;
    },

    isConflict(origStat, stat) {
        return stat != null && origStat != null && stat.mtimeMs != origStat.mtimeMs;
    },

    getDraftFileName(fileObject) {
        let filePath = fileObject.path;
        let draftFileName = '', counter = 1, uniquePart = '';

        do {
            draftFileName = path.join(path.dirname(filePath), DRAFT_PREFEX + path.basename(filePath) + uniquePart + DRAFT_EXT);
            uniquePart = '_' + counter++;
        } while (fs.existsSync(draftFileName));

        return draftFileName;
    },

    async getFileDrafts(fileObject) {
        let filePath = fileObject.path;
        let draftsPaths = [], drafts = [], draftFileName, counter = 1, uniquePart = '';

        do {
            draftsPaths.push(draftFileName);
            draftFileName = path.join(path.dirname(filePath), DRAFT_PREFEX + path.basename(filePath) + uniquePart + DRAFT_EXT);
            uniquePart = '_' + counter++;
        } while (fs.existsSync(draftFileName)); //TODO this assume continuous drafts names

        //Port old draft files to new prefex
        counter = 1;
        uniquePart = '';
        let draftExists = false;

        do {
            draftFileName = path.join(path.dirname(filePath), OLD_DRAFT_PREFEX + path.basename(filePath) + uniquePart + DRAFT_EXT);
            draftExists = fs.existsSync(draftFileName);

            if (draftExists) {
                const newDraftFileName = path.join(path.dirname(filePath), DRAFT_PREFEX + path.basename(filePath) + uniquePart + DRAFT_EXT);
                await fsProm.rename(draftFileName, newDraftFileName);
                draftsPaths.push(newDraftFileName);
            }

            uniquePart = '_' + counter++;
        } while (draftExists); //TODO this assume continuous drafts names

        //Skip the first null element
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
    },

    async saveDraft(fileObject, data) {
        if (!renderer.checkFileContent(data)) {
            throw new Error('Invalid file data');
        } else {
            let draftFileName = fileObject.draftFileName || renderer.getDraftFileName(fileObject);
            await fsProm.writeFile(draftFileName, data, 'utf8');

            if (isWin) {
                try {
                    // Add Hidden attribute:
                    spawn('attrib', ['+h', draftFileName], {shell: true});
                } catch (e) {
                }
            }

            return draftFileName;
        }
    },

    async saveFile(fileObject, data, origStat, overwrite, defEnc) {
        if (!renderer.checkFileContent(data)) {
            throw new Error('Invalid file data');
        }

        let retryCount = 0;
        let backupCreated = false;
        let bkpPath = path.join(path.dirname(fileObject.path), BKP_PREFEX + path.basename(fileObject.path) + BKP_EXT);
        const oldBkpPath = path.join(path.dirname(fileObject.path), OLD_BKP_PREFEX + path.basename(fileObject.path) + BKP_EXT);
        let writeEnc = defEnc || fileObject.encoding;

        let writeFile = async function () {
            let fh;

            try {
                // O_SYNC is for sync I/O and reduce risk of file corruption
                fh = await fsProm.open(fileObject.path, O_SYNC | O_CREAT | O_WRONLY | O_TRUNC);
                await fsProm.writeFile(fh, data, writeEnc);
            } finally {
                await fh?.close();
            }

            let stat2 = await fsProm.stat(fileObject.path);
            // Workaround for possible writing errors is to check the written
            // contents of the file and retry 3 times before showing an error
            let writtenData = await fsProm.readFile(fileObject.path, writeEnc);

            if (data != writtenData) {
                retryCount++;

                if (retryCount < 3) {
                    return await writeFile();
                } else {
                    throw new Error('all saving trials failed');
                }
            } else {
                //We'll keep the backup file in case the original file is corrupted. TODO When should we delete the backup file?
                if (backupCreated) {
                    //fs.unlink(bkpPath, (err) => {}); //Ignore errors!

                    //Delete old backup file with old prefix
                    if (fs.existsSync(oldBkpPath)) {
                        fs.unlink(oldBkpPath, () => {
                            //Ignore errors
                        });
                    }
                }

                return stat2;
            }
        };

        async function doSaveFile(isNew) {
            if (enableStoreBkp && !isNew) {
                //Copy file to back up file (after conflict and stat is checked)
                let bkpFh;

                try {
                    //Use file read then write to open the backup file direct sync write to reduce the chance of file corruption
                    let fileContent = await fsProm.readFile(fileObject.path, writeEnc);
                    bkpFh = await fsProm.open(bkpPath, O_SYNC | O_CREAT | O_WRONLY | O_TRUNC);
                    await fsProm.writeFile(bkpFh, fileContent, writeEnc);
                    backupCreated = true;
                } catch (e) {
                    if (__DEV__) {
                        console.log('Backup file writing failed', e); //Ignore
                    }
                } finally {
                    await bkpFh?.close();

                    if (isWin) {
                        try {
                            // Add Hidden attribute:
                            spawn('attrib', ['+h', bkpPath], {shell: true});
                        } catch (e) {
                        }
                    }
                }
            }

            return await writeFile();
        }

        if (overwrite) {
            return await doSaveFile(true);
        } else {
            let stat = fs.existsSync(fileObject.path) ?
                await fsProm.stat(fileObject.path) : null;

            if (stat && renderer.isConflict(origStat, stat)) {
                throw new Error('conflict');
            } else {
                return await doSaveFile(stat == null);
            }
        }
    },

    async writeFile(path, data, enc) {
        if (!renderer.checkFileContent(data, enc)) {
            throw new Error('Invalid file data');
        } else {
            return await fsProm.writeFile(path, data, enc);
        }
    },

    getAppDataFolder() {
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
    },

    getDocumentsFolder() {
        //On windows, misconfigured Documents folder cause an exception
        try {
            return app.getPath('documents');
        } catch (e) {
        }

        return '.';
    },

    checkFileExists(pathParts) {
        let filePath = path.join(...pathParts);
        return {exists: fs.existsSync(filePath), path: filePath};
    },

    async showOpenDialog(defaultPath, filters, properties) {
        let win = BrowserWindow.getFocusedWindow();

        return dialog.showOpenDialog(win, {
            defaultPath: defaultPath,
            filters: filters,
            properties: properties
        });
    },

    async showSaveDialog(defaultPath, filters) {
        let win = BrowserWindow.getFocusedWindow();

        return dialog.showSaveDialog(win, {
            defaultPath: defaultPath,
            filters: filters
        });
    },

    async installPlugin(filePath) {
        if (!enablePlugins) return {};

        let pluginsDir = path.join(renderer.getAppDataFolder(), '/plugins');

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
    },

    getPluginFile(plugin) {
        if (!enablePlugins) return null;

        const prefix = path.join(renderer.getAppDataFolder(), '/plugins/');
        const pluginFile = path.join(prefix, plugin);

        if (pluginFile.startsWith(prefix) && fs.existsSync(pluginFile)) {
            return pluginFile;
        }

        return null;
    },

    async uninstallPlugin(plugin) {
        const pluginFile = renderer.getPluginFile(plugin);

        if (pluginFile != null) {
            fs.unlinkSync(pluginFile);
        }
    },

    dirname(path_p) {
        return path.dirname(path_p);
    },

    async readFile(filename, encoding) {
        let data = await fsProm.readFile(filename, encoding);

        if (renderer.checkFileContent(data, encoding)) {
            return data;
        }

        throw new Error('Invalid file data');
    },

    async fileStat(file) {
        return await fsProm.stat(file);
    },

    async isFileWritable(file) {
        try {
            await fsProm.access(file, fs.constants.W_OK);
            return true;
        } catch (e) {
            return false;
        }
    },

    clipboardAction(method, data) {
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
    },

    async deleteFile(file) {
        // Reading the header of the file to confirm it is a file we can delete
        let fh = await fsProm.open(file, O_RDONLY);
        let buffer = Buffer.allocUnsafe(16);
        await fh.read(buffer, 0, 16);
        await fh.close();

        if (renderer.checkFileContent(buffer)) {
            await fsProm.unlink(file);
        }
    },

    async windowAction(method) {
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
    },

    async openExternal(url) {
        //Only open http(s), mailto, tel, and callto links
        if (allowedUrls.test(url)) {
            await shell.openExternal(url)
        }
    },

    async watchFile(path) {
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
                    // Ignore
                }
            });
        }
    },

    async unwatchFile(path) {
        fs.unwatchFile(path);
    },
}

const onRenderer = (getMainWindow) => {
    ipcMain.on("rendererReq", async (event, args) => {
        try {
            let ret = null;

            switch (args.action) {
                case 'saveFile':
                    ret = await renderer.saveFile(args.fileObject, args.data, args.origStat, args.overwrite, args.defEnc);
                    break;
                case 'writeFile':
                    ret = await renderer.writeFile(args.path, args.data, args.enc);
                    break;
                case 'saveDraft':
                    ret = await renderer.saveDraft(args.fileObject, args.data);
                    break;
                case 'getFileDrafts':
                    ret = await renderer.getFileDrafts(args.fileObject);
                    break;
                case 'getDocumentsFolder':
                    ret = await renderer.getDocumentsFolder();
                    break;
                case 'checkFileExists':
                    ret = renderer.checkFileExists(args.pathParts);
                    break;
                case 'showOpenDialog':
                    dialogOpen = true;
                    ret = await renderer.showOpenDialog(args.defaultPath, args.filters, args.properties);
                    ret = ret.filePaths;
                    dialogOpen = false;
                    break;
                case 'showSaveDialog':
                    dialogOpen = true;
                    ret = await renderer.showSaveDialog(args.defaultPath, args.filters);
                    ret = ret.canceled ? null : ret.filePath;
                    dialogOpen = false;
                    break;
                case 'installPlugin':
                    ret = await renderer.installPlugin(args.filePath);
                    break;
                case 'uninstallPlugin':
                    ret = await renderer.uninstallPlugin(args.plugin);
                    break;
                case 'getPluginFile':
                    ret = renderer.getPluginFile(args.plugin);
                    break;
                case 'isPluginsEnabled':
                    ret = enablePlugins;
                    break;
                case 'dirname':
                    ret = await renderer.dirname(args.path);
                    break;
                case 'readFile':
                    ret = await renderer.readFile(args.filename, args.encoding);
                    break;
                case 'clipboardAction':
                    ret = renderer.clipboardAction(args.method, args.data);
                    break;
                case 'deleteFile':
                    ret = await renderer.deleteFile(args.file);
                    break;
                case 'fileStat':
                    ret = await renderer.fileStat(args.file);
                    break;
                case 'isFileWritable':
                    ret = await renderer.isFileWritable(args.file);
                    break;
                case 'windowAction':
                    ret = await renderer.windowAction(args.method);
                    break;
                case 'openExternal':
                    ret = await renderer.openExternal(args.url);
                    break;
                case 'openDownloadWindow':
                    ret = await electronDown.open(args.language || 'zh', args.theme || 'light');
                    break;
                case 'updateDownloadWindow':
                    ret = await electronDown.updateWindow(args.language, args.theme);
                    break;
                case 'createDownload':
                    ret = await electronDown.createDownload(getMainWindow(), args.url, args.options || {});
                    break;
                case 'watchFile':
                    ret = await renderer.watchFile(args.path);
                    break;
                case 'unwatchFile':
                    ret = await renderer.unwatchFile(args.path);
                    break;
                case 'getCurDir':
                    ret = __dirname;
                    break;
            }

            event.reply('mainResp', {success: true, data: ret, reqId: args.reqId});
        } catch (e) {
            event.reply('mainResp', {error: true, msg: e.message, e: e, reqId: args.reqId});
            loger.error('Renderer request error', e.message, e.stack);
        }
    });
}

module.exports = { renderer, onRenderer };
