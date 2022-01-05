export default {
    /**
     * 当前打开的项目
     * @param state
     * @returns {{}|{readonly id?: *}}
     */
    projectData(state) {
        let projectId = state.projectId;
        if (projectId == 0) {
            projectId = $A.runNum(window.__projectId);
        }
        if (projectId > 0) {
            window.__projectId = projectId;
            const project = state.projects.find(({id}) => id == projectId);
            if (project) {
                return project;
            }
        }
        return {};
    },

    /**
     * 当前打开的项目面板参数
     * @param state
     * @returns {(function(*): (boolean|*))|*}
     */
    projectParameter(state) {
        return function (key) {
            if (!state.projectId) {
                return false;
            }
            let cache = state.cacheProjectParameter.find(({project_id}) => project_id == state.projectId);
            if (!cache) {
                cache = $A.projectParameterTemplate(state.projectId)
                state.cacheProjectParameter.push(cache);
            }
            return cache && !!cache[key];
        }
    },

    /**
     * 当前打开的任务
     * @param state
     * @returns {{}|{readonly id?: *}}
     */
    taskData(state) {
        let taskId = state.taskId;
        if (taskId == 0) {
            taskId = $A.runNum(window.__taskId);
        }
        if (taskId > 0) {
            window.__taskId = taskId;
            const task = state.tasks.find(({id}) => id == taskId);
            if (task) {
                return task;
            }
        }
        return {};
    },

    /**
     * 转换任务列表
     * @returns {function(*): *}
     */
    transforTasks(state) {
        return function (list) {
            return list.filter(({parent_id}) => {
                if (parent_id > 0) {
                    if (list.find(({id}) => id == parent_id)) {
                        return false;
                    }
                }
                return true;
            }).map(task => {
                if (task.parent_id > 0) {
                    // 子任务
                    const data = state.tasks.find(({id}) => id == task.parent_id);
                    if (data) {
                        return Object.assign({}, data, {
                            id: task.id,
                            parent_id: task.parent_id,
                            name: task.name,
                            start_at: task.start_at,
                            end_at: task.end_at,
                            complete_at: task.complete_at,

                            sub_top: true,
                            sub_my: [],
                        });
                    } else {
                        return Object.assign({}, task, {
                            sub_top: true,
                            sub_my: [],
                        });
                    }
                } else {
                    // 主任务
                    return Object.assign({}, task, {
                        sub_top: false,
                        sub_my: list.filter(({parent_id}) => parent_id == task.id),
                    });
                }
            })
        }
    },

    /**
     * 我所有的任务（未完成）
     * @param state
     * @returns {unknown[]}
     */
    ownerTasks(state) {
        return state.tasks.filter(({complete_at, owner}) => {
            if (complete_at) {
                return false;
            }
            return owner;
        })
    },

    /**
     * 仪表盘任务数据
     * @param state
     * @param getters
     * @returns {{overdue: *, today: *}}
     */
    dashboardTask(state, getters) {
        const todayStart = $A.Date($A.formatDate("Y-m-d 00:00:00")),
            todayEnd = $A.Date($A.formatDate("Y-m-d 23:59:59")),
            todayNow = $A.Date($A.formatDate("Y-m-d H:i:s"));
        const todayTasks = getters.ownerTasks.filter(task => {
            if (!task.end_at) {
                return false;
            }
            const start = $A.Date(task.start_at),
                end = $A.Date(task.end_at);
            return (start <= todayStart && todayStart <= end) || (start <= todayEnd && todayEnd <= end) || (start > todayStart && todayEnd > end);
        })
        const overdueTasks = getters.ownerTasks.filter(task => {
            if (!task.end_at) {
                return false;
            }
            return $A.Date(task.end_at) <= todayNow;
        })
        return {
            today: todayTasks,
            overdue: overdueTasks,
        }
    },
}
