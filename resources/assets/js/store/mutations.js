import emitter from "./events";

export default {
    // 路由加载
    'route/loading': function(state, load) {
        if (load) {
            state.routeLoading++
        } else {
            state.routeLoading--
        }
    },

    // 会员管理
    'user/push': function(state, data) {
        state.cacheUserBasic.push(data)
        $A.IDBSave("cacheUserBasic", state.cacheUserBasic, 600)
    },

    'user/splice': function(state, {index, data, count = 1}) {
        if (typeof data === "undefined") {
            state.cacheUserBasic.splice(index, count)
        } else {
            state.cacheUserBasic.splice(index, count, data)
        }
        $A.IDBSave("cacheUserBasic", state.cacheUserBasic, 600)
    },

    'user/save': function(state, data) {
        state.cacheUserBasic = data
        $A.IDBSave("cacheUserBasic", state.cacheUserBasic, 600)
    },

    // 共同群聊
    'common/dialog/count/save': function(state, {userid, total, updatedAt = Date.now()}) {
        if (!userid) {
            return;
        }
        const key = String(userid);
        const cache = Object.assign({}, state.dialogCommonCountCache);
        const parsedTotal = Number(total);
        cache[key] = {
            total: Number.isNaN(parsedTotal) ? 0 : parsedTotal,
            updated_at: updatedAt,
        };
        state.dialogCommonCountCache = cache;
        $A.IDBSave("dialogCommonCountCache", state.dialogCommonCountCache, 600);
    },

    'common/dialog/count/clear': function(state, userid) {
        if (typeof userid === 'number' || typeof userid === 'string') {
            const cache = Object.assign({}, state.dialogCommonCountCache);
            delete cache[String(userid)];
            state.dialogCommonCountCache = cache;
        } else {
            state.dialogCommonCountCache = {};
        }
        $A.IDBSave("dialogCommonCountCache", state.dialogCommonCountCache, 600);
    },

    // 消息管理
    'message/push': function(state, data) {
        state.dialogMsgs.push(data)
        $A.IDBSave("dialogMsgs", state.dialogMsgs, 600)
    },

    'message/splice': function(state, {index, data, count = 1}) {
        if (typeof data === "undefined") {
            state.dialogMsgs.splice(index, count)
        } else {
            state.dialogMsgs.splice(index, count, data)
        }
        $A.IDBSave("dialogMsgs", state.dialogMsgs, 600)
    },

    'message/save': function(state, data) {
        state.dialogMsgs = data
        $A.IDBSave("dialogMsgs", state.dialogMsgs, 600)
    },

    // 任务管理
    'task/push': function(state, data) {
        state.cacheTasks.push(data)
        $A.IDBSave("cacheTasks", state.cacheTasks, 600)
    },

    'task/splice': function(state, {index, data, count = 1}) {
        if (typeof data === "undefined") {
            state.cacheTasks.splice(index, count)
        } else {
            state.cacheTasks.splice(index, count, data)
        }
        $A.IDBSave("cacheTasks", state.cacheTasks, 600)
    },

    // 任务内容
    'task/content/push': function(state, data) {
        state.taskContents.push(data)
    },

    'task/content/splice': function(state, {index, data, count = 1}) {
        if (typeof data === "undefined") {
            state.taskContents.splice(index, count)
        } else {
            state.taskContents.splice(index, count, data)
        }
    },

    // 任务关联
    'task/related/save': function(state, {taskId, list, updatedAt = Date.now()}) {
        const cache = Object.assign({}, state.taskRelatedCache);
        cache[taskId] = {
            list,
            updated_at: updatedAt,
        };
        state.taskRelatedCache = cache;
        $A.IDBSave("taskRelatedCache", state.taskRelatedCache, 600)
    },

    'task/related/clear': function(state, taskId) {
        if (typeof taskId === 'number' || typeof taskId === 'string') {
            const cache = Object.assign({}, state.taskRelatedCache);
            delete cache[taskId];
            state.taskRelatedCache = cache;
        } else {
            state.taskRelatedCache = {};
        }
        $A.IDBSave("taskRelatedCache", state.taskRelatedCache, 600)
    },

    // 对话管理
    'dialog/push': function(state, data) {
        state.cacheDialogs.push(data)
        $A.IDBSave("cacheDialogs", state.cacheDialogs, 600)
    },

    'dialog/splice': function(state, {index, data, count = 1}) {
        if (typeof data === "undefined") {
            state.cacheDialogs.splice(index, count)
        } else {
            state.cacheDialogs.splice(index, count, data)
        }
        $A.IDBSave("cacheDialogs", state.cacheDialogs, 600)
    },

    // dialogIns
    'dialog/in/push': function(state, data) {
        state.dialogIns.push(data)
    },

    'dialog/in/splice': function(state, {index, data, count = 1}) {
        if (typeof data === "undefined") {
            state.dialogIns.splice(index, count)
        } else {
            state.dialogIns.splice(index, count, data)
        }
    },

    // dialogHistory
    'dialog/history/push': function(state, data) {
        state.dialogHistory.push(data)
    },

    'dialog/history/save': function(state, data) {
        state.dialogHistory = data
    },

    // dialogMsgTops
    'dialog/msg/top/push': function(state, data) {
        state.dialogMsgTops.push(data)
    },

    'dialog/msg/top/splice': function(state, {index, data, count = 1}) {
        if (typeof data === "undefined") {
            state.dialogMsgTops.splice(index, count)
        } else {
            state.dialogMsgTops.splice(index, count, data)
        }
    },

    'dialog/msg/top/save': function(state, data) {
        state.dialogMsgTops = data
    },

    // dialogTodos
    'dialog/todo/push': function(state, data) {
        state.dialogTodos.push(data)
    },

    'dialog/todo/splice': function(state, {index, data, count = 1}) {
        if (typeof data === "undefined") {
            state.dialogTodos.splice(index, count)
        } else {
            state.dialogTodos.splice(index, count, data)
        }
    },

    'dialog/todo/save': function(state, data) {
        state.dialogTodos = data
    },

    // 项目管理
    'project/push': function(state, data) {
        state.cacheProjects.push(data)
        $A.IDBSave("cacheProjects", state.cacheProjects);
    },

    'project/splice': function(state, {index, data, count = 1}) {
        if (typeof data === "undefined") {
            state.cacheProjects.splice(index, count)
        } else {
            state.cacheProjects.splice(index, count, data)
        }
        $A.IDBSave("cacheProjects", state.cacheProjects);
    },

    // cacheColumns
    'project/column/push': function(state, data) {
        state.cacheColumns.push(data)
        $A.IDBSave("cacheColumns", state.cacheColumns);
    },

    'project/column/splice': function(state, {index, data, count = 1}) {
        if (typeof data === "undefined") {
            state.cacheColumns.splice(index, count)
        } else {
            state.cacheColumns.splice(index, count, data)
        }
        $A.IDBSave("cacheColumns", state.cacheColumns);
    },

    'project/column/save': function(state, data) {
        state.cacheColumns = data
        $A.IDBSave("cacheColumns", state.cacheColumns);
    },

    // cacheProjectParameter
    'project/parameter/push': function(state, data) {
        state.cacheProjectParameter.push(data)
        $A.IDBSave("cacheProjectParameter", state.cacheProjectParameter);
    },

    'project/parameter/splice': function(state, {index, data, count = 1}) {
        if (typeof data === "undefined") {
            state.cacheProjectParameter.splice(index, count)
        } else {
            state.cacheProjectParameter.splice(index, count, data)
        }
        $A.IDBSave("cacheProjectParameter", state.cacheProjectParameter);
    },

    // 文件管理
    'file/push': function(state, data) {
        state.fileLists.push(data)
        $A.IDBSave("fileLists", state.fileLists, 600)
    },

    'file/splice': function(state, {index, data, count = 1}) {
        if (typeof data === "undefined") {
            state.fileLists.splice(index, count)
        } else {
            state.fileLists.splice(index, count, data)
        }
        $A.IDBSave("fileLists", state.fileLists, 600)
    },

    'file/save': function(state, data) {
        state.fileLists = data
        $A.IDBSave("fileLists", state.fileLists, 600)
    },

    // 草稿管理
    'draft/set': function(state, {id, content}) {
        const index = state.dialogDrafts.findIndex(item => item.id === id)
        const item = {
            id,
            content: $A.filterInvalidLine(content),
            time: new Date().getTime()
        }
        if (index === -1 && !item.content) {
            return
        }

        if (state.dialogId == id) {
            item.tag = index !== -1 ? state.dialogDrafts[index].tag : false
        } else {
            item.tag = !!item.content
        }

        if (index !== -1) {
            state.dialogDrafts.splice(index, 1, item)
        } else {
            state.dialogDrafts.push(item)
        }

        $A.IDBSave("dialogDrafts", state.dialogDrafts)
    },

    'draft/tag': function(state, id) {
        if (state.dialogId == id) {
            return
        }
        const index = state.dialogDrafts.findIndex(item => item.id === id)
        if (index !== -1) {
            state.dialogDrafts[index].tag = !!state.dialogDrafts[index].content
            $A.IDBSave("dialogDrafts", state.dialogDrafts)
        }
    },

    // 引用管理
    'quote/set': function(state, {id, type, content}) {
        const index = state.dialogQuotes.findIndex(item => item.id === id)
        const item = {
            id,
            type,
            content,
            time: new Date().getTime()
        }
        if (index === -1 && !item.content) {
            return
        }

        if (index !== -1) {
            state.dialogQuotes.splice(index, 1, item)
        } else {
            state.dialogQuotes.push(item)
        }

        $A.IDBSave("dialogQuotes", state.dialogQuotes)
    },

    'quote/remove': function(state, id) {
        const index = state.dialogQuotes.findIndex(item => item.id === id)
        if (index !== -1) {
            state.dialogQuotes.splice(index, 1)
            $A.IDBSave("dialogQuotes", state.dialogQuotes)
        }
    },

    // 长按事件
    'longpress/set': function(state, {type, data, element}) {
        state.longpressData = {type, data, element}
    },

    'longpress/clear': function(state) {
        state.longpressData = {type: '', data: null, element: null}
    },

    // 通用菜单
    'menu/operation': function(state, data) {
        state.menuOperation = data || {}
    },

    // 微应用管理
    'microApps/push': function(state, data) {
        state.microApps.push(data)
    },

    'microApps/update': function(state, {name, data}) {
        const app = state.microApps.find(item => item.name == name)
        if (app) {
            Object.assign(app, data)
        }
    },

    'microApps/keepAlive': function(state, keepAliveNum) {
        const keepAliveApps = state.microApps.filter(app => app.keep_alive)
        if (keepAliveApps.length <= keepAliveNum) {
            return
        }
        keepAliveApps
            .sort((a, b) => a.lastOpenAt - b.lastOpenAt)
            .slice(0, keepAliveApps.length - keepAliveNum)
            .forEach(app => {
                app.keepAliveBackup = true
                app.keep_alive = false
            })
    },

    'microApps/splice': function(state, {index, data, count = 1}) {
        if (typeof data === "undefined") {
            state.microApps.splice(index, count)
        } else {
            state.microApps.splice(index, count, data)
        }
    },

    'microApps/data': function(state, data) {
        // 添加应用商店
        if (state.userIsAdmin) {
            data.unshift({
                id: 'appstore',
                version: '1.0.0',
                menu_items: [{
                    location: "application/admin",
                    label: $A.L("应用商店"),
                    icon: $A.mainUrl("images/application/appstore.svg"),
                    url: 'appstore/internal?language={system_lang}&theme={system_theme}',
                    disable_scope_css: true,
                    auto_dark_theme: false,
                    capsule: {
                        visible: true,
                        top: 18,
                        right: 18,
                    }
                }]
            })
        }
        // 找出已卸载的应用和版本更新的应用
        const updatedOrUninstalledApps = state.microAppsInstalled
            .filter((oldApp) => !data.some((newApp) => newApp.id === oldApp.id))
            .map((app) => ({type: 'uninstall', id: app.id}));
        state.microAppsInstalled.forEach((oldApp) => {
            const newApp = data.find((app) => app.id === oldApp.id);
            if (newApp) {
                if (oldApp.version !== newApp.version) {
                    updatedOrUninstalledApps.push({type: 'update', id: oldApp.id});
                } else if (oldApp.install_at !== newApp.install_at) {
                    updatedOrUninstalledApps.push({type: 'reinstall', id: oldApp.id});
                }
            }
        });
        state.microAppsInstalled = data;
        emitter.emit('observeMicroApp:updatedOrUninstalled', updatedOrUninstalledApps);
        // 更新菜单
        const menus = [];
        data.forEach((item) => {
            if (Array.isArray(item.menu_items) && item.menu_items.length > 0) {
                menus.push(...item.menu_items.map(menu => Object.assign({}, menu, {id: item.id})));
            }
        })
        menus.forEach(item => {
            let name = item.id
            if (menus.filter(m => m.id === item.id).length > 1) {
                name += "_" + `${item.url}`
                    .replace(/^https?:\/\/.*?\//, '')
                    .replace(/[^a-zA-Z0-9]/g, '_');
            }
            if (menus.find(m => m.name === name)) {
                name += "_" + $A.randomString(8)
            }
            item.name = name;
        })
        $A.IDBSave("microAppsIds", state.microAppsIds = data.map(item => item.id));
        $A.IDBSave("microAppsMenus", state.microAppsMenus = menus);
    },

    // MCP 服务器状态
    'mcp/server/status': function(state, data) {
        $A.IDBSave("mcpServerStatus", state.mcpServerStatus = data);
    },
}
