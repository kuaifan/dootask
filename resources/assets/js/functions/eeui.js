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

        eeuiAppSendMessage(object) {
            if (!$A.isEEUiApp) return;
            requireModuleJs("webview").sendMessage(object);
        },

        eeuiAppSetUrl(url) {
            if (!$A.isEEUiApp) return;
            requireModuleJs("webview").setUrl(url);
        },
    });

    window.$A = $;
})(window);
