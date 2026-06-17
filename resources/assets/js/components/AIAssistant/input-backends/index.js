/**
 * 输入后端选择
 *
 * - Electron 桌面端且 CDP 桥可用 → ElectronInputBackend（可信输入），失败回退 Web。
 * - 其他环境 → WebInputBackend（页面内合成事件，地板）。
 *
 * 描述/定位层（ariaSnapshot + ref→Element）与后端无关，由 collector/executor 负责；
 * 后端只接收已定位的 element + action + value。
 */

import { createWebBackend } from './web-backend';
import { createElectronBackend, isElectronInputAvailable } from './electron-backend';

let _web = null;
let _electron = null;

export function selectBackend() {
    if (isElectronInputAvailable()) {
        if (!_electron) _electron = createElectronBackend(() => getWebBackend());
        return _electron;
    }
    return getWebBackend();
}

export function getWebBackend() {
    if (!_web) _web = createWebBackend();
    return _web;
}
