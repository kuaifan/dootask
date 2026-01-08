const path = require('path')
const {BrowserWindow, ipcMain} = require('electron')
const loger = require("electron-log");
const crc = require("crc");
const zlib = require('zlib');

const PDFDocument = require('pdf-lib').PDFDocument;
const config = require('../package.json');

const MICRON_TO_PIXEL = 264.58 		//264.58 micron = 1 pixel
const PIXELS_PER_INCH = 100.117		// Usually it is 100 pixels per inch but this give better results
const PNG_CHUNK_IDAT = 1229209940;
const LARGE_IMAGE_AREA = 30000000;

const pdfExport = {
    //NOTE: Key length must not be longer than 79 bytes (not checked)
    writePngWithText(origBuff, key, text, compressed, base64encoded) {
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
            loger.error(e.message, {stack: e.stack});
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
            loger.error(e.message, {stack: e.stack});
            throw e;
        }
    },

    //TODO Create a lightweight html file similar to export3.html for exporting to vsdx
    exportVsdx(event, args, directFinalize) {
        let win = new BrowserWindow({
            width: 1280,
            height: 800,
            show: false,
            webPreferences: {
                preload: path.join(__dirname, 'electron-preload.js'),
                webSecurity: true,
                nodeIntegration: true,
                contextIsolation: true,
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
    },

    async mergePdfs(pdfFiles, xml) {
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
    },

    exportDiagram(event, args, directFinalize) {
        if (args.format == 'vsdx') {
            pdfExport.exportVsdx(event, args, directFinalize);
            return;
        }

        let browser = null;

        try {
            browser = new BrowserWindow({
                webPreferences: {
                    preload: path.join(__dirname, 'electron-preload.js'),
                    backgroundThrottling: false,
                    contextIsolation: true,
                    disableBlinkFeatures: 'Auxclick' // Is this needed?
                },
                show: false,
                frame: false,
                enableLargerThanScreen: true,
                transparent: args.format == 'png' && (args.bg == null || args.bg == 'none'),
            });

            if (serverUrl) {
                browser.loadURL(serverUrl + 'drawio/webapp/export3.html').then(_ => { }).catch(_ => { })
            } else {
                browser.loadFile('./public/drawio/webapp/export3.html').then(_ => { }).catch(_ => { })
            }

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
                        pdfOptions = {
                            printBackground: true,
                            pageSize: {
                                width: bounds.width / PIXELS_PER_INCH,
                                height: (bounds.height + 2) / PIXELS_PER_INCH //the extra 2 pixels to prevent adding an extra empty page
                            },
                            margins: {
                                top: 0,
                                bottom: 0,
                                left: 0,
                                right: 0
                            } // no margin
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
                                    data = pdfExport.writePngWithText(data, 'dpi', args.dpi);
                                }

                                if (args.embedXml == "1" && args.format == 'png') {
                                    data = pdfExport.writePngWithText(data, "mxGraphModel", args.xml, true,
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
                                    data = await pdfExport.mergePdfs(pdfs, args.embedXml == '1' ? args.xml : null);
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
    },
}

const onExport = () => {
    ipcMain.on('export', pdfExport.exportDiagram);
}

module.exports = {pdfExport, onExport};
