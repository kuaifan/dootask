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
            const project = state.cacheProjects.find(({id}) => id == projectId);
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
            if (key === 'menuType' && typeof cache[key] === "undefined") {
                return 'column'
            }
            return cache[key];
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
            const task = state.cacheTasks.find(({id}) => id == taskId);
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
                    const data = state.cacheTasks.find(({id}) => id == task.parent_id);
                    if (data) {
                        return Object.assign({}, data, {
                            id: task.id,
                            parent_id: task.parent_id,
                            name: task.name,
                            start_at: task.start_at,
                            end_at: task.end_at,
                            complete_at: task.complete_at,
                            _time: task._time,

                            flow_item_id: task.flow_item_id,
                            flow_item_name: task.flow_item_name,
                            flow_item_status: task.flow_item_status,

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
     * 仪表盘任务数据
     * @param state
     * @returns {{overdue: *, today: *,all:*}}
     */
    dashboardTask(state) {
        const todayStart = $A.Date($A.formatDate("Y-m-d 00:00:00")),
            todayEnd = $A.Date($A.formatDate("Y-m-d 23:59:59")),
            todayNow = $A.Date($A.formatDate("Y-m-d H:i:s"));
        const filterTask = (task, chackCompleted = true) => {
            if (task.archived_at) {
                return false;
            }
            if (task.complete_at && chackCompleted === true) {
                return false;
            }
            return task.owner;
        }
        let array = state.cacheTasks.filter(task => filterTask(task));
        if (state.taskCompleteTemps.length > 0) {
            let tmps = state.cacheTasks.filter(task => state.taskCompleteTemps.includes(task.id) && filterTask(task, false));
            if (tmps.length > 0) {
                array = $A.cloneJSON(array)
                array.push(...tmps);
            }
        }
        const todayTasks = array.filter(task => {
            const start = $A.Date(task.start_at),
                end = $A.Date(task.end_at);
            return (start <= todayStart && todayStart <= end) || (start <= todayEnd && todayEnd <= end) || (start > todayStart && todayEnd > end);
        })
        const overdueTasks = array.filter(task => {
            return task.end_at && $A.Date(task.end_at) <= todayNow;
        })

        return {
            today: todayTasks,
            overdue: overdueTasks,
            all: array
        }
    },
}
