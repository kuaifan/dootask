export default {
    /**
     * 切换项目聊天显隐
     * @param state
     */
    toggleProjectChatShow(state) {
        state.projectChatShow = !state.projectChatShow
        state.setStorage('projectChatShow', state.projectChatShow);
    },

    /**
     * 切换项目面板显示类型
     * @param state
     */
    toggleProjectListPanel(state) {
        state.projectListPanel = !state.projectListPanel
        state.setStorage('projectListPanel', state.projectListPanel);
    },

    /**
     * 切换项目面板显示显示我的任务
     * @param state
     */
    toggleTaskMyShow(state) {
        state.taskMyShow = !state.taskMyShow
        state.setStorage('taskMyShow', state.taskMyShow);
    },

    /**
     * 切换项目面板显示显示未完成任务
     * @param state
     */
    toggleTaskUndoneShow(state) {
        state.taskUndoneShow = !state.taskUndoneShow
        state.setStorage('taskUndoneShow', state.taskUndoneShow);
    },

    /**
     * 切换项目面板显示显示已完成任务
     * @param state
     */
    toggleTaskCompletedShow(state) {
        state.taskCompletedShow = !state.taskCompletedShow
        state.setStorage('taskCompletedShow', state.taskCompletedShow);
    },

    /**
     * 获取任务优先级预设数据
     * @param state
     * @param callback
     */
    getTaskPriority(state, callback) {
        $A.apiAjax({
            url: 'system/priority',
            success: ({ret, data, msg}) => {
                if (ret === 1) {
                    state.taskPriority = data;
                    typeof callback === "function" && callback(data);
                }
            },
        });
        return state.userInfo;
    },

    /**
     * 获取/更新会员信息
     * @param state
     * @param callback
     */
    getUserInfo(state, callback) {
        $A.apiAjax({
            url: 'users/info',
            error: () => {
                $A.userLogout();
            },
            success: ({ret, data, msg}) => {
                if (ret === 1) {
                    this.commit('setUserInfo', data);
                    typeof callback === "function" && callback(data);
                }
            },
        });
        return state.userInfo;
    },

    /**
     * 更新会员信息
     * @param state
     * @param info
     */
    setUserInfo(state, info) {
        const userInfo = state._cloneJSON(info);
        userInfo.userid = state._runNum(userInfo.userid);
        userInfo.token = userInfo.userid > 0 ? (userInfo.token || state.userToken) : '';
        state.userInfo = userInfo;
        state.userId = userInfo.userid;
        state.userToken = userInfo.token;
        state.setStorage('userInfo', state.userInfo);
    },

    /**
     * 获取项目信息
     * @param state
     * @param project_id
     */
    getProjectDetail(state, project_id) {
        if (state._runNum(project_id) === 0) {
            return;
        }
        if (state._isJson(state.cacheProject[project_id])) {
            state.projectDetail = state.cacheProject[project_id];
        }
        state.projectDetail.id = project_id;
        //
        if (state.cacheProject[project_id + "::load"]) {
            return;
        }
        state.cacheProject[project_id + "::load"] = true;
        //
        state.projectLoad++;
        $A.apiAjax({
            url: 'project/detail',
            data: {
                project_id: project_id,
            },
            complete: () => {
                state.projectLoad--;
                state.cacheProject[project_id + "::load"] = false;
            },
            success: ({ret, data, msg}) => {
                if (ret === 1) {
                    state.cacheProject[project_id] = data;
                    if (state.projectDetail.id === project_id) {
                        state.projectDetail = data;
                    }
                } else {
                    $A.modalError(msg);
                }
            }
        });
    },

    /**
     * 获取用户基本信息
     * @param state
     * @param params {userid, success, complete}
     */
    getUserBasic(state, params) {
        if (!state._isJson(params)) {
            return;
        }
        const {userid, success, complete} = params;
        const time = Math.round(new Date().getTime() / 1000);
        const array = [];
        (state._isArray(userid) ? userid : [userid]).some((uid) => {
            if (state.cacheUserBasic[uid]) {
                typeof success === "function" && success(state.cacheUserBasic[uid].data, false);
                if (time - state.cacheUserBasic[uid].time <= 10) {
                    return false;
                }
            }
            array.push(uid);
        });
        if (array.length === 0) {
            typeof complete === "function" && complete()
            return;
        }
        //
        if (state.cacheUserBasic["::loading"] === true) {
            setTimeout(() => {
                this.commit('getUserBasic', params);
            }, 20);
            return;
        }
        state.cacheUserBasic["::loading"] = true;
        $A.apiAjax({
            url: 'users/basic',
            data: {
                userid: array
            },
            complete: () => {
                state.cacheUserBasic["::loading"] = false;
                typeof complete === "function" && complete()
            },
            success: ({ret, data, msg}) => {
                if (ret === 1) {
                    data.forEach((item) => {
                        state.cacheUserBasic[item.userid] = {
                            time,
                            data: item
                        };
                        typeof success === "function" && success(item, true)
                    });
                } else {
                    $A.modalError(msg);
                }
            }
        });
    }
}
