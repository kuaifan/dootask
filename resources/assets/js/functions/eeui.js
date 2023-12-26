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
        eeuiAppAlert(object, callback) {
            if (!$A.isEEUiApp) return;
            if (typeof callback !== "function") callback = _ => {};
            requireModuleJs("eeui").alert(object, callback);
        },

        eeuiAppToast(object) {
            if (!$A.isEEUiApp) return;
            requireModuleJs("eeui").toast(object);
        },

        eeuiAppRewriteUrl(val) {
            if (!$A.isEEUiApp) return;
            return requireModuleJs("eeui").rewriteUrl(val);
        },

        eeuiAppOpenPage(object, callback) {
            if (!$A.isEEUiApp) return;
            if (typeof callback !== "function") callback = _ => {};
            requireModuleJs("eeui").openPage(object, callback);
        },

        eeuiAppOpenWeb(url) {
            if (!$A.isEEUiApp) return;
            requireModuleJs("eeui").openWeb(url)
        },

        eeuiAppSetPageBackPressed(object, callback) {
            if (!$A.isEEUiApp) return;
            if (typeof callback !== "function") callback = _ => {};
            requireModuleJs("eeui").setPageBackPressed(object, callback);
        },

        eeuiAppGoDesktop() {
            if (!$A.isEEUiApp) return;
            requireModuleJs("eeui").goDesktop();
        },

        eeuiAppKeepScreenOn() {
            if (!$A.isEEUiApp) return;
            requireModuleJs("eeui").keepScreenOn();
        },

        eeuiAppKeepScreenOff() {
            if (!$A.isEEUiApp) return;
            requireModuleJs("eeui").keepScreenOff();
        },

        eeuiAppKeyboardHide() {
            if (!$A.isEEUiApp) return;
            requireModuleJs("eeui").keyboardHide();
        },

        eeuiAppSendMessage(object) {
            if (!$A.isEEUiApp) return;
            requireModuleJs("webview").sendMessage(object);
        },

        eeuiAppSetUrl(url) {
            if (!$A.isEEUiApp) return;
            requireModuleJs("webview").setUrl(url);
        },

        eeuiAppScan(callback) {
            if (!$A.isEEUiApp) return;
            requireModuleJs("eeui").openScaner({}, (res)=>{
                switch (res.status) {
                    case "success":
                        callback(res.text);
                        break;
                }
            });
        },

        eeuiAppGetThemeName() {
            if (!$A.isEEUiApp) return;
            return requireModuleJs("eeui").getThemeName();
        },

        eeuiAppKeyboardStatus() {
            if (!$A.isEEUiApp) return;
            return requireModuleJs("eeui").keyboardStatus();
        },

        eeuiAppSetHapticBackEnabled(val) {
            if (!$A.isEEUiApp) return;
            return requireModuleJs("webview").setHapticBackEnabled(val);
        },

        eeuiAppSetDisabledUserLongClickSelect(val) {
            if (!$A.isEEUiApp) return;
            return requireModuleJs("webview").setDisabledUserLongClickSelect(val);
        },
    });

    window.$A = $;
})(window);
