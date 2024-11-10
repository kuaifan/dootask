/**
 * EEUI App 专用
 */
(function (window) {
    const $ = window.$A;

    /**
     * =============================================================================
     * *******************************   App extra   *******************************
     * =============================================================================
     */
    $.extend({
        eeuiModule(name, count = 0) {
            return new Promise((resolve) => {
                if (typeof requireModuleJs === "function") {
                    resolve(requireModuleJs(name));
                    return;
                }
                setTimeout(() => {
                    if (count < 20) {
                        resolve(this.eeuiModuleJs(name, ++count));
                    } else {
                        resolve(null);
                    }
                }, 500)
            });
        },

        eeuiModuleSync(name) {
            if (typeof requireModuleJs === "function") {
                return requireModuleJs(name);
            }
            return null;
        },

        // 获取eeui版本号
        eeuiAppVersion() {
            if (!$A.isEEUiApp) return;
            return $A.eeuiModuleSync("eeui").getVersion();
        },

        // 获取本地软件版本号
        eeuiAppLocalVersion() {
            if (!$A.isEEUiApp) return;
            return $A.eeuiModuleSync("eeui").getLocalVersion();
        },

        // alert
        eeuiAppAlert(object, callback) {
            if (!$A.isEEUiApp) return;
            if (typeof callback !== "function") callback = _ => {};
            $A.eeuiModule("eeui").then(obj => {
                obj.alert(object, callback);
            })
        },

        // toast
        eeuiAppToast(object) {
            if (!$A.isEEUiApp) return;
            $A.eeuiModule("eeui").then(obj => {
                obj.toast(object);
            })
        },

        // 相对地址基于当前地址补全
        eeuiAppRewriteUrl(val) {
            if (!$A.isEEUiApp) return;
            return $A.eeuiModuleSync("eeui").rewriteUrl(val);
        },

        // 获取页面信息
        eeuiAppGetPageInfo(pageName) {
            if (!$A.isEEUiApp) return;
            return $A.eeuiModuleSync("eeui").getPageInfo(pageName);
        },

        // 打开app新页面
        eeuiAppOpenPage(object, callback) {
            if (!$A.isEEUiApp) return;
            if (typeof callback !== "function") {
                callback = _ => {};
            }
            if (typeof object.callback === "function") {
                callback = object.callback;
                delete object.callback
            }
            $A.eeuiModule("eeui").then(obj => {
                obj.openPage(Object.assign({
                    softInputMode: "resize",
                }, object), callback);
            })
        },

        // 使用系统浏览器打开网页
        eeuiAppOpenWeb(url) {
            if (!$A.isEEUiApp) return;
            $A.eeuiModule("eeui").then(obj => {
                obj.openWeb(url)
            })
        },

        // 拦截返回按键事件（仅支持android、iOS无效）
        eeuiAppSetPageBackPressed(object, callback) {
            if (!$A.isEEUiApp) return;
            if (typeof callback !== "function") callback = _ => {};
            $A.eeuiModule("eeui").then(obj => {
                obj.setPageBackPressed(object, callback);
            })
        },

        // 返回手机桌面
        eeuiAppGoDesktop() {
            if (!$A.isEEUiApp) return;
            $A.eeuiModule("eeui").then(obj => {
                obj.goDesktop();
            })
        },

        // 打开屏幕常亮
        eeuiAppKeepScreenOn() {
            if (!$A.isEEUiApp) return;
            $A.eeuiModule("eeui").then(obj => {
                obj.keepScreenOn();
            })
        },

        // 关闭屏幕常亮
        eeuiAppKeepScreenOff() {
            if (!$A.isEEUiApp) return;
            $A.eeuiModule("eeui").then(obj => {
                obj.keepScreenOff();
            })
        },

        // 隐藏软键盘
        eeuiAppKeyboardHide() {
            if (!$A.isEEUiApp) return;
            $A.eeuiModule("eeui").then(obj => {
                obj.keyboardHide();
            })
        },

        // 给app发送消息
        eeuiAppSendMessage(object) {
            if (!$A.isEEUiApp) return;
            $A.eeuiModule("webview").then(obj => {
                obj.sendMessage(object);
            })
        },

        // 设置浏览器地址
        eeuiAppSetUrl(url) {
            if (!$A.isEEUiApp) return;
            $A.eeuiModule("webview").then(obj => {
                obj.setUrl(url);
            })
        },

        // 扫码
        eeuiAppScan(callback) {
            if (!$A.isEEUiApp) return;
            $A.eeuiModule("eeui").then(obj => {
                obj.openScaner({}, (res) => {
                    switch (res.status) {
                        case "success":
                            callback(res.text);
                            break;
                    }
                });
            })
        },

        // 检查更新
        eeuiAppCheckUpdate() {
            if (!$A.isEEUiApp) return;
            $A.eeuiModule("eeui").then(obj => {
                obj.checkUpdate();
            })
        },

        // 获取主题名称 light|dark
        eeuiAppGetThemeName() {
            if (!$A.isEEUiApp) return;
            return $A.eeuiModuleSync("eeui").getThemeName();
        },

        // 判断软键盘是否可见
        eeuiAppKeyboardStatus() {
            if (!$A.isEEUiApp) return;
            return $A.eeuiModuleSync("eeui").keyboardStatus();
        },

        // 设置全局变量
        eeuiAppSetVariate(key, value) {
            if (!$A.isEEUiApp) return;
            $A.eeuiModuleSync("eeui").setVariate(key, value);
        },

        // 获取全局变量
        eeuiAppGetVariate(key, defaultVal = "") {
            if (!$A.isEEUiApp) return;
            return $A.eeuiModuleSync("eeui").getVariate(key, defaultVal);
        },

        // 设置缓存数据
        eeuiAppSetCachesString(key, value, expired = 0) {
            if (!$A.isEEUiApp) return;
            $A.eeuiModuleSync("eeui").setCachesString(key, value, expired);
        },

        // 获取缓存数据
        eeuiAppGetCachesString(key, defaultVal = "") {
            if (!$A.isEEUiApp) return;
            return $A.eeuiModuleSync("eeui").getCachesString(key, defaultVal);
        },

        // 长按内容震动（仅支持android、iOS无效）
        eeuiAppSetHapticBackEnabled(val) {
            if (!$A.isEEUiApp) return;
            $A.eeuiModuleSync("webview").setHapticBackEnabled(val);
        },

        // 禁止长按选择（仅支持android、iOS无效）
        eeuiAppSetDisabledUserLongClickSelect(val) {
            if (!$A.isEEUiApp) return;
            $A.__disabledUserLongClickSelectTimer && clearTimeout($A.__disabledUserLongClickSelectTimer);
            if (/^\d+$/.test(val)) {
                $A.eeuiModuleSync("webview").setDisabledUserLongClickSelect(true);
                $A.__disabledUserLongClickSelectTimer = setTimeout(() => {
                    $A.__disabledUserLongClickSelectTimer = null;
                    $A.eeuiModuleSync("webview").setDisabledUserLongClickSelect(false);
                }, val);
            } else {
                $A.eeuiModuleSync("webview").setDisabledUserLongClickSelect(val);
            }
        },
        __disabledUserLongClickSelectTimer: null,

        // 复制文本
        eeuiAppCopyText(text) {
            if (!$A.isEEUiApp) return;
            $A.eeuiModuleSync("eeui").copyText(text)
        },

        // 设置是否允许滚动
        eeuiAppSetScrollEnabled(enabled) {
            if (!$A.isEEUiApp) return;
            $A.eeuiModule("webview").then(obj => {
                obj.setScrollEnabled(enabled);
            })
        },
    });

    window.$A = $;
})(window);
