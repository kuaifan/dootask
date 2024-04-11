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

        eeuiAppVersion() {
            if (!$A.isEEUiApp) return;
            return $A.eeuiModuleSync("eeui").getVersion();
        },

        eeuiAppLocalVersion() {
            if (!$A.isEEUiApp) return;
            return $A.eeuiModuleSync("eeui").getLocalVersion();
        },

        eeuiAppAlert(object, callback) {
            if (!$A.isEEUiApp) return;
            if (typeof callback !== "function") callback = _ => {};
            $A.eeuiModule("eeui").then(obj => {
                obj.alert(object, callback);
            })
        },

        eeuiAppToast(object) {
            if (!$A.isEEUiApp) return;
            $A.eeuiModule("eeui").then(obj => {
                obj.toast(object);
            })
        },

        eeuiAppRewriteUrl(val) {
            if (!$A.isEEUiApp) return;
            return $A.eeuiModuleSync("eeui").rewriteUrl(val);
        },

        eeuiAppOpenPage(object, callback) {
            if (!$A.isEEUiApp) return;
            if (typeof callback !== "function") callback = _ => {};
            $A.eeuiModule("eeui").then(obj => {
                obj.openPage(object, callback);
            })
        },

        eeuiAppOpenWeb(url) {
            if (!$A.isEEUiApp) return;
            $A.eeuiModule("eeui").then(obj => {
                obj.openWeb(url)
            })
        },

        eeuiAppSetPageBackPressed(object, callback) {
            if (!$A.isEEUiApp) return;
            if (typeof callback !== "function") callback = _ => {};
            $A.eeuiModule("eeui").then(obj => {
                obj.setPageBackPressed(object, callback);
            })
        },

        eeuiAppGoDesktop() {
            if (!$A.isEEUiApp) return;
            $A.eeuiModule("eeui").then(obj => {
                obj.goDesktop();
            })
        },

        eeuiAppKeepScreenOn() {
            if (!$A.isEEUiApp) return;
            $A.eeuiModule("eeui").then(obj => {
                obj.keepScreenOn();
            })
        },

        eeuiAppKeepScreenOff() {
            if (!$A.isEEUiApp) return;
            $A.eeuiModule("eeui").then(obj => {
                obj.keepScreenOff();
            })
        },

        eeuiAppKeyboardHide() {
            if (!$A.isEEUiApp) return;
            $A.eeuiModule("eeui").then(obj => {
                obj.keyboardHide();
            })
        },

        eeuiAppSendMessage(object) {
            if (!$A.isEEUiApp) return;
            $A.eeuiModule("webview").then(obj => {
                obj.sendMessage(object);
            })
        },

        eeuiAppSetUrl(url) {
            if (!$A.isEEUiApp) return;
            $A.eeuiModule("webview").then(obj => {
                obj.setUrl(url);
            })
        },

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

        eeuiAppGetThemeName() {
            if (!$A.isEEUiApp) return;
            return $A.eeuiModuleSync("eeui").getThemeName();
        },

        eeuiAppKeyboardStatus() {
            if (!$A.isEEUiApp) return;
            return $A.eeuiModuleSync("eeui").keyboardStatus();
        },

        eeuiAppSetVariate(key, value) {
            if (!$A.isEEUiApp) return;
            $A.eeuiModuleSync("eeui").setVariate(key, value);
        },

        eeuiAppSetHapticBackEnabled(val) {
            if (!$A.isEEUiApp) return;
            $A.eeuiModuleSync("webview").setHapticBackEnabled(val);
        },

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

        eeuiAppCopyText(text) {
            if (!$A.isEEUiApp) return;
            $A.eeuiModuleSync("eeui").copyText(text)
        },
    });

    window.$A = $;
})(window);
