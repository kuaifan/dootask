/**
 * EEUI App 专用
 */
import {languageName} from "../language";

(function (window) {
    const $ = window.$A;

    /**
     * =============================================================================
     * ****************************   EEUI App extra   *****************************
     * =============================================================================
     */
    $.extend({
        // 获取eeui模块
        eeuiModule(name = 'eeui') {
            if (typeof requireModuleJs === "function") {
                return requireModuleJs(name);
            }
            return null;
        },

        // 获取eeui模块（Promise）
        eeuiModulePromise(name = 'eeui') {
            return new Promise((resolve, reject) => {
                try {
                    const eeui = $A.eeuiModule(name);
                    if (!eeui) {
                        return reject({msg: "module not found"});
                    }
                    resolve(eeui);
                } catch (e) {
                    reject({msg: e.message});
                }
            })
        },

        // 获取eeui版本号
        eeuiAppVersion() {
            return $A.eeuiModule()?.getVersion();
        },

        // 获取本地软件版本号
        eeuiAppLocalVersion() {
            return $A.eeuiModule()?.getLocalVersion();
        },

        // Alert
        eeuiAppAlert(object, callback) {
            if (typeof callback !== "function") callback = _ => {};
            $A.eeuiModule()?.alert(object, callback);
        },

        // Toast
        eeuiAppToast(object) {
            $A.eeuiModule()?.toast(object);
        },

        // 相对地址基于当前地址补全
        eeuiAppRewriteUrl(val) {
            return $A.eeuiModule()?.rewriteUrl(val);
        },

        // 获取页面信息
        eeuiAppGetPageInfo(pageName) {
            return $A.eeuiModule()?.getPageInfo(pageName || "");
        },

        // 打开app新页面
        eeuiAppOpenPage(object, callback) {
            if (typeof callback !== "function") {
                callback = _ => {};
            }
            if (typeof object.callback === "function") {
                callback = object.callback;
                delete object.callback
            }
            $A.eeuiModule()?.openPage(Object.assign({
                softInputMode: "resize",
            }, object), callback);
        },

        // 使用系统浏览器打开网页
        eeuiAppOpenWeb(url) {
            $A.eeuiModule()?.openWeb(url)
        },

        // 拦截返回按键事件（仅支持android、iOS无效）
        eeuiAppSetPageBackPressed(object, callback) {
            if (typeof callback !== "function") callback = _ => {};
            $A.eeuiModule()?.setPageBackPressed(object, callback);
        },

        // 返回手机桌面
        eeuiAppGoDesktop() {
            $A.eeuiModule()?.goDesktop();
        },

        // 打开屏幕常亮
        eeuiAppKeepScreenOn() {
            $A.eeuiModule()?.keepScreenOn();
        },

        // 关闭屏幕常亮
        eeuiAppKeepScreenOff() {
            $A.eeuiModule()?.keepScreenOff();
        },

        // 隐藏软键盘
        eeuiAppKeyboardHide() {
            $A.eeuiModule()?.keyboardHide();
        },

        // 给app发送消息
        eeuiAppSendMessage(object) {
            $A.eeuiModule("webview")?.sendMessage(object);
        },

        // 设置浏览器地址
        eeuiAppSetUrl(url) {
            $A.eeuiModule("webview")?.setUrl(url);
        },

        // 生成webview快照
        eeuiAppGetWebviewSnapshot(callback) {
            $A.eeuiModule("webview")?.createSnapshot(callback);
        },

        // 显示webview快照
        eeuiAppShowWebviewSnapshot() {
            $A.eeuiModule("webview")?.showSnapshot();
        },

        // 隐藏webview快照
        eeuiAppHideWebviewSnapshot() {
            $A.eeuiModule("webview")?.hideSnapshot();
        },

        // 扫码
        eeuiAppScan(callback) {
            $A.eeuiModule()?.openScaner({}, (res) => {
                switch (res.status) {
                    case "success":
                        callback(res.text);
                        break;
                }
            });
        },

        // 检查更新
        eeuiAppCheckUpdate() {
            $A.eeuiModule()?.checkUpdate();
        },

        // 获取主题名称 light|dark
        eeuiAppGetThemeName() {
            return $A.eeuiModule()?.getThemeName();
        },

        // 判断软键盘是否可见
        eeuiAppKeyboardStatus() {
            return $A.eeuiModule()?.keyboardStatus();
        },

        // 设置全局变量
        eeuiAppSetVariate(key, value) {
            $A.eeuiModule()?.setVariate(key, value);
        },

        // 获取全局变量
        eeuiAppGetVariate(key, defaultVal = "") {
            return $A.eeuiModule()?.getVariate(key, defaultVal);
        },

        // 设置缓存数据
        eeuiAppSetCachesString(key, value, expired = 0) {
            $A.eeuiModule()?.setCachesString(key, value, expired);
        },

        // 获取缓存数据
        eeuiAppGetCachesString(key, defaultVal = "") {
            return $A.eeuiModule()?.getCachesString(key, defaultVal);
        },

        // 是否长按内容震动（仅支持android、iOS无效）
        eeuiAppSetHapticBackEnabled(val) {
            $A.eeuiModule("webview").setHapticBackEnabled(val);
        },

        // 禁止长按选择（仅支持android、iOS无效）
        eeuiAppSetDisabledUserLongClickSelect(val) {
            const webview = $A.eeuiModule("webview");
            $A.__disabledUserLongClickSelectTimer && clearTimeout($A.__disabledUserLongClickSelectTimer);
            if (!/^\d+$/.test(val)) {
                webview.setDisabledUserLongClickSelect(val);
                return;
            }
            webview.setDisabledUserLongClickSelect(true);
            $A.__disabledUserLongClickSelectTimer = setTimeout(() => {
                $A.__disabledUserLongClickSelectTimer = null;
                webview.setDisabledUserLongClickSelect(false);
            }, val);
        },
        __disabledUserLongClickSelectTimer: null,

        // 复制文本
        eeuiAppCopyText(text) {
            $A.eeuiModule()?.copyText(text)
        },

        // 设置是否禁止滚动
        eeuiAppSetScrollDisabled(disabled) {
            if (disabled) {
                $A.__setScrollDisabledNum++
            } else {
                $A.__setScrollDisabledNum--
            }
            $A.eeuiModule("webview")?.setScrollEnabled($A.__setScrollDisabledNum <= 0);
        },
        __setScrollDisabledNum: 0,

        // 设置应用程序级别的摇动撤销（仅支持iOS、android无效）
        eeuiAppShakeToEditEnabled(enabled) {
            if (enabled) {
                $A.eeuiModule()?.shakeToEditOn();
            } else {
                $A.eeuiModule()?.shakeToEditOff();
            }
        },

        // 获取最新一张照片
        eeuiAppGetLatestPhoto(expiration = 60, timeout = 10) {
            return new Promise(async (resolve, reject) => {
                try {
                    const eeui = await $A.eeuiModule();

                    const timer = timeout > 0 ? setTimeout(() => {
                        reject({msg: "timeout"});
                    }, timeout * 1000) : null;

                    eeui.getLatestPhoto(result => {
                        timer && clearTimeout(timer);
                        if (
                            result.status !== 'success' ||
                            result.thumbnail.width < 10 || !result.thumbnail.base64 ||
                            result.original.width < 10 || !result.original.path
                        ) {
                            return reject({msg: result.error || "no photo"});
                        }
                        if (expiration > 0 && (result.created + expiration) < $A.dayjs().unix()) {
                            return reject({msg: "photo expired"});
                        }
                        if ($A.__latestPhotoCreated && $A.__latestPhotoCreated === result.created) {
                            return reject({msg: "photo expired"});
                        }
                        $A.__latestPhotoCreated = result.created;
                        resolve(result);
                    });
                } catch (e) {
                    reject(e);
                }
            })
        },
        __latestPhotoCreated: null,

        // 上传照片（通过 eeuiAppGetLatestPhoto 获取到的path，params 参数：{url,data,headers,path,fieldName}）
        eeuiAppUploadPhoto(params, timeout = 30) {
            return new Promise(async (resolve, reject) => {
                try {
                    const eeui = await $A.eeuiModulePromise();

                    const timer = timeout > 0 ? setTimeout(() => {
                        reject({msg: "timeout"});
                    }, timeout * 1000) : null;

                    if (!$A.isJson(params)) {
                        return reject({msg: "params error"});
                    }

                    let onReady = null;
                    if (typeof params.onReady !== "undefined") {
                        onReady = params.onReady;
                        delete params.onReady;
                    }

                    eeui.uploadPhoto(params, result => {
                        if (result.status === 'ready') {
                            typeof onReady === "function" && onReady(result.id)
                            return
                        }

                        timer && clearTimeout(timer);
                        if (result.status !== 'success') {
                            return reject({msg: result.error || "upload failed"});
                        }
                        if (result.data.ret !== 1) {
                            return reject({msg: result.data.msg || "upload failed"});
                        }
                        resolve(result.data.data);
                    });
                } catch (e) {
                    reject(e);
                }
            })
        },

        // 取消上传照片
        eeuiAppCancelUploadPhoto(id) {
            return new Promise(async (resolve, reject) => {
                try {
                    const eeui = await $A.eeuiModulePromise();
                    eeui.cancelUploadPhoto(id, result => {
                        if (result.status !== 'success') {
                            return reject({msg: result.error || "cancel failed"});
                        }
                        resolve(result);
                    });
                } catch (e) {
                    reject(e);
                }
            })
        },

        // 获取导航栏和状态栏高度
        eeuiAppGetSafeAreaInsets() {
            return new Promise(async (resolve, reject) => {
                try {
                    const eeui = await $A.eeuiModulePromise();
                    eeui.getSafeAreaInsets(result => {
                        if (result.status !== 'success') {
                            return reject({msg: result.error || "get failed"});
                        }
                        resolve(result);
                    });
                } catch (e) {
                    reject(e);
                }
            })
        },

        // 获取当前语言
        eeuiAppConvertLanguage() {
            const specialMappings = {
                "zh": "zh-Hans",
                "zh-CHT": "zh-Hant"
            };
            return specialMappings[languageName] || languageName;
        },

        // 获取设备信息
        eeuiAppGetDeviceInfo() {
            return new Promise(async (resolve, reject) => {
                try {
                    const eeui = await $A.eeuiModulePromise();
                    eeui.getDeviceInfo(result => {
                        if (result.status !== 'success') {
                            return reject({msg: result.error || "get failed"});
                        }
                        resolve(result);
                    });
                } catch (e) {
                    reject(e);
                }
            })
        },

        // 判断是否窗口化
        eeuiAppIsWindowed() {
            return new Promise(async resolve => {
                try {
                    const eeui = await $A.eeuiModulePromise();
                    resolve(eeui.isFullscreen() === false || eeui.isFullscreen() === 0);
                } catch (e) {
                    resolve(false);
                }
            })
        }
    });

    window.$A = $;
})(window);
