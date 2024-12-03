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
     * @returns {{
     *   overdue: Array,            // 超期任务列表
     *   overdue_count: number,     // 超期任务数量
     *   today: Array,              // 今日任务列表
     *   today_count: number,       // 今日任务数量
     *   todo: Array,               // 待办任务列表
     *   todo_count: number         // 待办任务数量
     * }}
     */
    dashboardTask(state) {
        const todayStart = $A.daytz().startOf('day'),
            todayEnd = $A.daytz().endOf('day'),
            todayNow = $A.daytz();
            
        const filterTask = (task, checkCompleted = true) => {
            if (task.archived_at) {
                return false;
            }
            if (task.complete_at && checkCompleted === true) {
                return false;
            }
            if (task.start_at && $A.dayjs(task.start_at) > todayNow) {
                return false;
            }
            return task.owner == 1;
        }
        
        // 获取所有未完成的任务
        let array = state.cacheTasks.filter(task => filterTask(task));
        
        // 处理临时完成的任务
        let tmpCount = 0;
        if (state.taskCompleteTemps.length > 0) {
            let tmps = state.cacheTasks.filter(task => state.taskCompleteTemps.includes(task.id) && filterTask(task, false));
            if (tmps.length > 0) {
                tmpCount = tmps.length;
                array = $A.cloneJSON(array);
                array.push(...tmps);
            }
        }

        // 使用一次遍历完成任务分类
        const result = {
            overdue: [],
            today: [],
            todo: [],
            overdue_count: 0,
            today_count: 0,
            todo_count: 0
        };

        // 遍历任务进行分类
        array.forEach(task => {
            const isTemp = state.taskCompleteTemps.includes(task.id);
            
            if (task.end_at && $A.dayjs(task.end_at) <= todayNow) {
                // 超期任务
                result.overdue.push(task);
                if (!isTemp) {
                    result.overdue_count++;
                }
            } else if (task.end_at) {
                const end = $A.dayjs(task.end_at);
                if (todayStart <= end && end <= todayEnd) {
                    // 今日任务
                    result.today.push(task);
                    if (!isTemp) {
                        result.today_count++;
                    }
                } else {
                    // 待办任务
                    result.todo.push(task);
                    if (!isTemp) {
                        result.todo_count++;
                    }
                }
            } else {
                // 无截止日期的任务归类为待办
                result.todo.push(task);
                if (!isTemp) {
                    result.todo_count++;
                }
            }
        });

        return result;
    },

    /**
     * 协助任务
     * @param state
     * @returns {Array}               // 协助任务列表
     */
    assistTask(state) {
        const filterTask = (task, checkCompleted = true) => {
            if (task.archived_at) {
                return false;
            }
            if (task.complete_at && checkCompleted === true) {
                return false;
            }
            return task.assist && task.owner === 0;
        }

        // 获取所有未完成的协助任务
        let array = state.cacheTasks.filter(task => filterTask(task));
        
        // 处理临时完成的任务
        if (state.taskCompleteTemps.length > 0) {
            const tmps = state.cacheTasks.filter(task => 
                state.taskCompleteTemps.includes(task.id) && 
                filterTask(task, false)
            );
            
            if (tmps.length > 0) {
                array = $A.cloneJSON(array);
                array.push(...tmps);
            }
        }

        // 按截止时间排序：无截止时间的任务排在最后
        return array.sort((a, b) => {
            const timeA = a.end_at ? $A.dayjs(a.end_at) : $A.dayjs('2099-12-31 23:59:59');
            const timeB = b.end_at ? $A.dayjs(b.end_at) : $A.dayjs('2099-12-31 23:59:59');
            return timeA - timeB;
        });
    },
}
