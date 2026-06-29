import Vue from 'vue'

// 无角标时的共享只读对象，避免 badge getter 每次调用都新建对象
const EMPTY_BADGE = Object.freeze({count: 0, dot: false})

/**
 * 应用菜单角标（插件 / 自定义微应用）
 *
 * 结构：map[app_id][menu_key] = { count, dot }
 * - menu_key 为空串表示该应用的第一个菜单
 * - 初始值由 updateMicroAppsStatus 通过 hydrateMap 填充（apps/badge/list 一次返回插件 + 自定义全部角标）
 * - 运行时由 websocket 消息 appBadge 经 set 增量更新
 */
export default {
    namespaced: true,

    state: () => ({
        map: {},
    }),

    mutations: {
        /**
         * 增量设置单个菜单角标
         * @param state
         * @param appid
         * @param menu_key
         * @param count
         * @param dot
         */
        set(state, {appid, menu_key, count, dot}) {
            if (!appid) {
                return
            }
            const key = menu_key || ''
            if (!state.map[appid]) {
                Vue.set(state.map, appid, {})
            }
            Vue.set(state.map[appid], key, {
                count: Number(count) || 0,
                dot: !!dot,
            })
        },

        /**
         * 由后端角标快照整体初始化（apps/badge/list 返回，含插件 + 自定义微应用）
         * @param state
         * @param map 形如 { app_id: { menu_key: {count, dot} } }
         */
        hydrateMap(state, map) {
            const next = {}
            if (map && typeof map === 'object') {
                Object.keys(map).forEach(appid => {
                    const menus = map[appid]
                    if (!appid || !menus || typeof menus !== 'object') {
                        return
                    }
                    Object.keys(menus).forEach(key => {
                        const item = menus[key] || {}
                        const count = Number(item.count) || 0
                        const dot = !!item.dot
                        if (count > 0 || dot) {
                            if (!next[appid]) {
                                next[appid] = {}
                            }
                            next[appid][key || ''] = {count, dot}
                        }
                    })
                })
            }
            state.map = next
        },

        /**
         * 清除某应用某菜单的角标
         */
        clearMenu(state, {appid, menu_key}) {
            const key = menu_key || ''
            if (appid && state.map[appid]) {
                Vue.delete(state.map[appid], key)
            }
        },

        /**
         * 清除某应用全部角标（卸载/更新时）
         */
        clearApp(state, appid) {
            if (appid && state.map[appid]) {
                Vue.delete(state.map, appid)
            }
        },
    },

    getters: {
        /**
         * 取单个菜单角标
         * @returns {function(appid, menuKey): {count, dot}}
         */
        badge: (state) => (appid, menuKey) => {
            return (state.map[appid] && state.map[appid][menuKey || '']) || EMPTY_BADGE
        },

        /**
         * 所有应用角标数字之和
         */
        totalCount: (state) => {
            let total = 0
            Object.keys(state.map).forEach(appid => {
                Object.keys(state.map[appid]).forEach(key => {
                    total += Number(state.map[appid][key].count) || 0
                })
            })
            return total
        },

        /**
         * 是否存在任一红点
         */
        anyDot: (state) => {
            return Object.keys(state.map).some(appid => {
                return Object.keys(state.map[appid]).some(key => !!state.map[appid][key].dot)
            })
        },

        /**
         * 父『应用』入口聚合数字：工作报告未读 + 所有插件角标数字之和
         */
        applicationCount: (state, getters, rootState) => {
            return (Number(rootState.reportUnreadNumber) || 0) + getters.totalCount
        },

        /**
         * 父『应用』入口聚合红点：总数为 0 时，任一插件存在红点则显示
         */
        applicationDot: (state, getters) => getters.anyDot,
    },
}
