/**
 * AI 回复内联深链目录（可执行映射）
 *
 * AI 在回复正文里用标准 markdown 链接把"可定位的页面/面板"包成深链：
 *   [系统设置](dootask://link/setting_system)
 * 渲染时 markdown.js 用 isDeepLinkId 校验（非法 id 退化为纯文字，绝不渲染死链），
 * 点击时 DialogMarkdown 调 openDeepLink 直接导航到那一屏。
 *
 * 这里是「可执行映射」：id → 怎么打开。语义目录（id/title/aliases，供 AI 选择）在
 * resources/ai-kb/_meta/page-links.yaml —— 两份的 id 集合必须一致（CI 校验见
 * tests/.. 或 page-links 解析）。本期均为无需运行时 id 的纯导航目的地。
 */

// id → 打开方式。route 走 window.$A.goForward（与 action-executor.js 一致）。
const LINKS = {
    // 顶层功能页
    dashboard:    { route: { name: 'manage-dashboard' } },
    messenger:    { route: { name: 'manage-messenger' } },
    calendar:     { route: { name: 'manage-calendar' } },
    files:        { route: { name: 'manage-file' } },
    application:  { route: { name: 'manage-application' } },
    project_list: { route: { name: 'manage-project' } },

    // 设置（12 个 manage-setting-* 独立路由）
    setting_personal: { route: { name: 'manage-setting-personal' } },
    setting_checkin:  { route: { name: 'manage-setting-checkin' } },
    setting_language: { route: { name: 'manage-setting-language' } },
    setting_theme:    { route: { name: 'manage-setting-theme' } },
    setting_keyboard: { route: { name: 'manage-setting-keyboard' } },
    setting_license:  { route: { name: 'manage-setting-license' } },
    setting_password: { route: { name: 'manage-setting-password' } },
    setting_email:    { route: { name: 'manage-setting-email' } },
    setting_system:   { route: { name: 'manage-setting-system', query: { tab: 'setting' } } },
    setting_device:   { route: { name: 'manage-setting-device' } },
    setting_version:  { route: { name: 'manage-setting-version' } },
    setting_delete:   { route: { name: 'manage-setting-delete' } },

    // 系统设置内的二级 Tab（system.vue 据 query.tab 切换）
    setting_system_task_priority:   { route: { name: 'manage-setting-system', query: { tab: 'taskPriority' } } },
    setting_system_column_template: { route: { name: 'manage-setting-system', query: { tab: 'columnTemplate' } } },
    setting_system_file:            { route: { name: 'manage-setting-system', query: { tab: 'fileSetting' } } },
};

export const CATALOG_IDS = Object.keys(LINKS);

/**
 * 校验 id 是否在目录内（渲染与点击两端都用，防死链）
 * @param {string} id
 * @returns {boolean}
 */
export function isDeepLinkId(id) {
    return Object.prototype.hasOwnProperty.call(LINKS, id);
}

/**
 * 打开深链目的地
 * @param {string} id 目录 id
 * @returns {boolean} 是否成功派发导航
 */
export function openDeepLink(id) {
    const entry = LINKS[id];
    if (!entry) {
        return false;
    }
    if (entry.route && typeof window !== 'undefined' && window.$A?.goForward) {
        // 浅拷贝避免 goForward 内部改动污染目录
        window.$A.goForward({
            name: entry.route.name,
            ...(entry.route.params ? { params: { ...entry.route.params } } : {}),
            ...(entry.route.query ? { query: { ...entry.route.query } } : {}),
        });
        return true;
    }
    return false;
}

// 暴露调试钩子供 Playwright/E2E 验证（对标原 __startAiGuide）
if (typeof window !== 'undefined') {
    window.__openDeepLink = (id) => openDeepLink(id);
}

export default openDeepLink;
