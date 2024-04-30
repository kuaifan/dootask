export default {
    /**
     * 是否加载中
     * @param state
     * @returns {function(*)}
     */
    isLoad(state) {
        return function (key) {
            const load = state.loads.find(item => item.key === key);
            return !!(load && load.num > 0)
        }
    },

    /**
     * 当前打开的项目
     * @param state
     * @returns {{cacheParameter: {}}}
     */
    projectData(state) {
        if (state.projectId > 0) {
            let data = state.cacheProjects.find(({id}) => id == state.projectId);
            if (data) {
                let cacheParameter = state.cacheProjectParameter.find(({project_id}) => project_id == state.projectId);
                if (!cacheParameter) {
                    cacheParameter = $A.projectParameterTemplate(state.projectId)
                    state.cacheProjectParameter.push(cacheParameter);
                }
                if (cacheParameter.menuType === undefined) {
                    cacheParameter.menuType = 'column'
                }
                data.cacheParameter = cacheParameter;
                return data;
            }
        }
        return {
            cacheParameter: {}
        };
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
            if (task.start_at && $A.Date(task.start_at) > todayNow) {
                return false;
            }
            return task.owner == 1;
        }
        let array = state.cacheTasks.filter(task => filterTask(task));
        let tmpCount = 0;
        if (state.taskCompleteTemps.length > 0) {
            let tmps = state.cacheTasks.filter(task => state.taskCompleteTemps.includes(task.id) && filterTask(task, false));
            if (tmps.length > 0) {
                tmpCount = tmps.length
                array = $A.cloneJSON(array)
                array.push(...tmps);
            }
        }
        const todayTasks = array.filter(task => {
            const end = $A.Date(task.end_at);
            return todayStart <= end && end <= todayEnd;
        })
        const overdueTasks = array.filter(task => {
            return task.end_at && $A.Date(task.end_at) <= todayNow;
        })
        const result = {
            today: todayTasks,
            today_count: todayTasks.length,

            overdue: overdueTasks,
            overdue_count: overdueTasks.length,

            all: array,
            all_count: array.length,
        };
        if (tmpCount > 0) {
            result.today_count -= todayTasks.filter(task => state.taskCompleteTemps.includes(task.id)).length
            result.overdue_count -= overdueTasks.filter(task => state.taskCompleteTemps.includes(task.id)).length
            result.all_count -= tmpCount
        }
        return result
    },

    /**
     * 协助任务
     * @param state
     * @returns {*}
     */
    assistTask(state) {
        const filterTask = (task, chackCompleted = true) => {
            if (task.archived_at) {
                return false;
            }
            if (task.complete_at && chackCompleted === true) {
                return false;
            }
            return task.assist && task.owner === 0;
        }
        let array = state.cacheTasks.filter(task => filterTask(task));
        if (state.taskCompleteTemps.length > 0) {
            let tmps = state.cacheTasks.filter(task => state.taskCompleteTemps.includes(task.id) && filterTask(task, false));
            if (tmps.length > 0) {
                array = $A.cloneJSON(array)
                array.push(...tmps);
            }
        }
        return array
    },
}
