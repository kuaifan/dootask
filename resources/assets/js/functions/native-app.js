/**
 * Native App 桥接（替代旧 functions/eeui.js）
 *
 * 底层从 weex 的 requireModuleJs 切换为新 RN 桥接 window.__nativeBridge（src/bridge/injected.ts 注入）。
 * 方法名规则：$A.nativeAppXxx(args) → __nativeBridge.call('xxx', args)，其中 xxx 为首字母小写。
 *
 * 契约见 resources/mobile/src/bridge/CONTRACT.md。
 */
import {languageName} from "../language";

(function (window) {
    const $ = window.$A;

    /** 通用调用入口：返回 Promise；桥接未就绪则 resolve undefined（避免抛错炸主流程） */
    function call(method, args) {
        const bridge = window.__nativeBridge;
        if (!bridge || typeof bridge.call !== "function") {
            return Promise.resolve(undefined);
        }
        return bridge.call(method, args === undefined ? null : args);
    }

    /** 同步调用包装：调用方需要立刻拿到值的场景（仅适用于在 native 端有缓存的 method）。
     * 注意：所有桥接调用本质是异步；同步方法目前用兜底默认值返回。 */
    function syncDefault(_method, defaultValue) { return defaultValue; }

    $.extend({
        // === 设备/系统 ===
        nativeAppVersion() { return syncDefault('getAppVersion', undefined); },
        nativeAppVersionAsync() { return call('getAppVersion'); },
        nativeAppLocalVersion() { return syncDefault('getAppLocalVersion', 0); },
        nativeAppLocalVersionAsync() { return call('getAppLocalVersion'); },
        nativeAppGetPageInfo() { return {}; },
        nativeAppGetThemeName() { return syncDefault('getThemeName', undefined); },
        nativeAppKeyboardStatus() { return syncDefault('keyboardStatus', undefined); },
        nativeAppRewriteUrl(val) { return val; },

        // === UI 容器控制 ===
        nativeAppAlert(object, callback) {
            if (typeof callback !== "function") callback = _ => {};
            call('alert', object).then(callback).catch(callback);
        },
        nativeAppToast(object) { call('toast', typeof object === 'string' ? { message: object } : object); },
        nativeAppKeyboardHide() { call('keyboardHide'); },
        nativeAppKeepScreenOn() { call('keepScreenOn'); },
        nativeAppKeepScreenOff() { call('keepScreenOff'); },
        nativeAppSetPageBackPressed(object, callback) {
            if (typeof callback !== "function") callback = _ => {};
            // 旧契约：原生回调由 setPageBackPressed 触发；新契约用全局事件 __onBackPressed
            window.__onBackPressed = callback;
            call('setPageBackPressed', object);
        },
        nativeAppGoDesktop() { call('goDesktop'); },
        nativeAppSetHapticBackEnabled(val) { call('setHapticBackEnabled', !!val); },
        nativeAppSetDisabledUserLongClickSelect(val) {
            $.__disabledUserLongClickSelectTimer && clearTimeout($.__disabledUserLongClickSelectTimer);
            if (!/^\d+$/.test(val)) {
                call('setScrollDisabled', !!val);
                return;
            }
            call('setScrollDisabled', true);
            $.__disabledUserLongClickSelectTimer = setTimeout(() => {
                $.__disabledUserLongClickSelectTimer = null;
                call('setScrollDisabled', false);
            }, parseInt(val));
        },
        __disabledUserLongClickSelectTimer: null,
        nativeAppSetScrollDisabled(disabled) {
            if (disabled) {
                $.__setScrollDisabledNum++
            } else {
                $.__setScrollDisabledNum--
            }
            call('setScrollDisabled', $.__setScrollDisabledNum > 0);
        },
        __setScrollDisabledNum: 0,
        nativeAppShakeToEditEnabled(_enabled) { /* iOS 摇动撤销在 RN 由系统默认支持，no-op */ },

        // === 存储 ===
        nativeAppSetVariate(key, value) { call('setVariate', { key, value }); },
        nativeAppGetVariate(_key, defaultVal = "") {
            // 异步返回；同步签名兼容兜底（不推荐使用）
            return defaultVal;
        },
        nativeAppGetVariateAsync(key, defaultVal = "") {
            return call('getVariate', { key, defaultVal });
        },
        nativeAppSetCachesString(key, value, expired = 0) { call('setCache', { key, value, expired }); },
        nativeAppGetCachesString(_key, defaultVal = "") { return defaultVal; },
        nativeAppGetCachesStringAsync(key, defaultVal = "") {
            return call('getCache', { key, defaultVal });
        },

        // === 媒体 ===
        nativeAppScan(callback) {
            call('scan').then((text) => { typeof callback === 'function' && callback(text); });
        },
        nativeAppCopyText(text) { call('copyText', text); },
        nativeAppGetLatestPhoto(expiration = 60, _timeout = 10) {
            return call('getLatestPhoto', { expiration }).then((res) => {
                // 旧契约外形：{thumbnail:{base64,width,height}, original:{path,width,height}}
                // 新最小实现只返回 {path,width,height,created}，包装为兼容形态。
                if (!res || !res.path) return Promise.reject({ msg: 'no photo' });
                return {
                    thumbnail: { base64: '', width: res.width, height: res.height },
                    original: { path: res.path, width: res.width, height: res.height },
                    created: res.created,
                };
            });
        },
        nativeAppUploadPhoto(params, _timeout = 30) {
            const onReady = params && params.onReady;
            if (typeof onReady === 'function') delete params.onReady;
            return call('uploadPhoto', params).then((data) => {
                if (typeof onReady === 'function') onReady();
                return data;
            });
        },
        nativeAppCancelUploadPhoto(id) { return call('cancelUploadPhoto', { id }); },

        // === 交互/外链 ===
        nativeAppOpenPage(object, callback) {
            if (typeof callback !== "function") callback = _ => {};
            if (typeof object.callback === "function") {
                callback = object.callback;
                delete object.callback
            }
            call('openPage', object).then(callback).catch(callback);
        },
        nativeAppOpenWeb(url) { call('openWeb', url); },
        nativeAppSetUrl(url) { call('setUrl', url); },
        nativeAppGetWebviewSnapshot(callback) { typeof callback === 'function' && callback({status: 'noop'}); },
        nativeAppShowWebviewSnapshot() { /* 新移动端不再用 weex 快照机制 */ },
        nativeAppHideWebviewSnapshot() { /* 同上 */ },
        nativeAppCheckUpdate() { call('checkUpdate'); },

        // === 信息（异步）===
        nativeAppGetDeviceInfo() {
            return call('getDeviceInfo').then((info) => {
                if (!info) return Promise.reject({ msg: 'no device info' });
                return info;
            });
        },
        nativeAppGetSafeAreaInsets() {
            return call('getSafeAreaInsets').then((insets) => {
                if (!insets) return Promise.reject({ msg: 'no safe area' });
                return { status: 'success', top: insets.top, bottom: insets.bottom, data: insets };
            });
        },
        nativeAppIsWindowed() { return call('isWindowed').then((v) => !!v); },

        // === 平台无关：纯前端语言映射（不走桥接） ===
        nativeAppConvertLanguage() {
            const specialMappings = {"zh": "zh-Hans", "zh-CHT": "zh-Hant"};
            return specialMappings[languageName] || languageName;
        },

        // === sendMessage：拆解为独立 method ===
        // 旧 $A.nativeAppSendMessage({action:'xxx', ...}) 全部路由到对应桥接 method。
        nativeAppSendMessage(object) {
            if (!object || typeof object !== 'object') return;
            const action = object.action;
            const rest = Object.assign({}, object); delete rest.action;
            switch (action) {
                case 'updateTheme':       return call('updateTheme', rest);
                case 'windowSize':        return call('windowSize', rest);
                case 'setBadgeNum':
                case 'setBdageNotify':    return call('setBadge', { count: parseInt(rest.bdage) || 0 });
                case 'setVibrate':        return call('vibrate', { time: parseInt(rest.time) || 0 });
                case 'callTel':           return call('callTel', { tel: rest.tel });
                case 'picturePreview':    return call('picturePreview', rest);
                case 'videoPreview':      return call('videoPreview', rest);
                case 'clearAllNotify':    return call('clearAllNotify');
                case 'gotoSetting':       return call('gotoNotificationSetting');
                case 'getNotificationPermission': return call('getNotificationPermission');
                case 'startMeeting':      return call('startMeeting', rest);
                case 'updateMeetingInfo': return call('updateMeetingInfo', rest);
                // 推送注册：新链路 — 不再走 sendMessage，外层逻辑改用 registerPush；
                // 旧 action 静默忽略以兼容尚未迁移的调用点
                case 'initApp':
                case 'setUmengAlias':
                case 'delUmengAlias':
                    return;
                default:
                    // 未识别 action：调试时打日志，避免静默失败
                    if (window.systemInfo && window.systemInfo.debug === 'yes') {
                        console.warn('[nativeApp] unhandled sendMessage action:', action, rest);
                    }
            }
        },
    });

    window.$A = $;
})(window);
