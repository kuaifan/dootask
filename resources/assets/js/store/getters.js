export default {
    projectData(state) {
        let projectId = state.projectId;
        if (projectId == 0) {
            projectId = state.method.runNum(window.__projectId);
        }
        if (projectId > 0) {
            window.__projectId = projectId;
            const project = state.method.cloneJSON(state.projects.find(({id}) => id == projectId));
            if (project) {
                project.columns = state.method.cloneJSON(state.columns.filter(({project_id}) => {
                    return project_id == project.id
                })).sort((a, b) => {
                    if (a.sort != b.sort) {
                        return a.sort - b.sort;
                    }
                    return a.id - b.id;
                });
                project.columns.forEach((column) => {
                    column.tasks = state.method.cloneJSON(state.tasks.filter((task) => {
                        return task.column_id == column.id && task.parent_id == 0;
                    })).sort((a, b) => {
                        if (a.sort != b.sort) {
                            return a.sort - b.sort;
                        }
                        return a.id - b.id;
                    });
                })
                return Object.freeze(project);
            }
        }
        return {
            columns: [],
            project_user: []
        };
    },

    taskData(state) {
        let taskId = state.taskId;
        if (taskId == 0) {
            taskId = state.method.runNum(window.__taskId);
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

    tablePanel(state) {
        return function (key) {
            if (!state.projectId) {
                return false;
            }
            let cache = state.cacheTablePanel.find(({project_id}) => project_id == state.projectId);
            if (!cache) {
                cache = {
                    project_id: state.projectId,
                    card: true,
                    cardInit: false,
                    chat: false,
                    showMy: true,
                    showHelp: true,
                    showUndone: true,
                    showCompleted: false,
                    completedTask: false,
                }
                state.cacheTablePanel.push(cache);
            }
            return cache && !!cache[key];
        }
    },


    ownerTask(state) {
        return state.tasks.filter(({complete_at, parent_id, end_at, owner}) => {
            if (parent_id > 0) {
                const index = state.tasks.findIndex(data => {
                    if (data.id != parent_id) {
                        return false;
                    }
                    if (data.complete_at) {
                        return false;
                    }
                    if (!data.end_at) {
                        return false;
                    }
                    return data.owner;
                });
                if (index > -1) {
                    return false;
                }
            }
            if (complete_at) {
                return false;
            }
            if (!end_at) {
                return false;
            }
            return owner;
        }).map(task => {
            if (task.parent_id > 0) {
                const tmp = state.tasks.find(({id}) => id == task.parent_id);
                if (tmp) {
                    return Object.assign({}, tmp, {
                        id: task.id,
                        parent_id: task.parent_id,
                        name: task.name,
                        start_at: task.start_at,
                        end_at: task.end_at,
                        sub_num: 0,
                        top_task: true,
                    });
                }
            }
            return task;
        })
    },

    dashboardData(state, getters) {
        const todayStart = $A.Date($A.formatDate("Y-m-d 00:00:00")),
            todayEnd = $A.Date($A.formatDate("Y-m-d 23:59:59")),
            todayNow = $A.Date($A.formatDate("Y-m-d H:i:s"));
        const todayTasks = getters.ownerTask.filter(task => {
            const start = $A.Date(task.start_at),
                end = $A.Date(task.end_at);
            return (start <= todayStart && todayStart <= end) || (start <= todayEnd && todayEnd <= end) || (start > todayStart && todayEnd > end);
        })
        const overdueTasks = getters.ownerTask.filter(task => {
            return $A.Date(task.end_at) <= todayNow;
        })
        return {
            today: todayTasks,
            overdue: overdueTasks,
        }
    }
}
