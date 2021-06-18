export default {
    /**
     * 添加列表
     * @param state
     * @param data
     */
    columnAddSuccess(state, data) {
        if (state.projectDetail.id == data.project_id) {
            let index = state.projectDetail.project_column.findIndex(({id}) => id === data.id);
            if (index === -1) {
                state.projectDetail.project_column.push(data);
            }
        }
    },

    /**
     * 更新列表
     * @param state
     * @param data
     */
    columnUpdateSuccess(state, data) {
        if (state.projectDetail.id == data.project_id) {
            let index = state.projectDetail.project_column.findIndex(({id}) => id === data.id);
            if (index > -1) {
                state.projectDetail.project_column.splice(index, 1, Object.assign({}, state.projectDetail.project_column[index], data));
            }
        }
    },

    /**
     * 删除列表
     * @param state
     * @param data {id, project_id}
     */
    columnDeleteSuccess(state, data) {
        if (state.projectDetail.id == data.project_id) {
            let index = state.projectDetail.project_column.findIndex(({id}) => id === data.id);
            if (index > -1) {
                state.projectDetail.project_column.splice(index, 1);
            }
            this.dispatch("getProjectBasic", {id: data.project_id});
        }
    },

    /**
     * 添加任务
     * @param state
     * @param data
     */
    taskAddSuccess(state, data) {
        const {task, in_top, new_column} = data;
        if (task.parent_id == 0) {
            // 添加任务
            if (state.projectDetail.id == task.project_id) {
                if (new_column) {
                    state.projectDetail.project_column.push(new_column);
                }
                const column = state.projectDetail.project_column.find(({id}) => id === task.column_id);
                if (column) {
                    let index = column.project_task.findIndex(({id}) => id === task.id)
                    if (index === -1) {
                        if (in_top) {
                            column.project_task.unshift(task);
                        } else {
                            column.project_task.push(task);
                        }
                    }
                }
            }
            this.dispatch("getProjectBasic", {id: task.project_id});
        } else {
            // 添加子任务
            if (state.projectDetail.id == task.project_id) {
                const column = state.projectDetail.project_column.find(({id}) => id === task.column_id);
                if (column) {
                    const project_task = column.project_task.find(({id}) => id === task.parent_id)
                    if (project_task && project_task.sub_task) {
                        let index = project_task.sub_task.findIndex(({id}) => id === task.id)
                        if (index === -1) {
                            project_task.sub_task.push(task);
                        }
                    }
                }
            }
            if (task.parent_id == state.projectOpenTask.id) {
                let index = state.projectOpenTask.sub_task.findIndex(({id}) => id === task.id)
                if (index === -1) {
                    state.projectOpenTask.sub_task.push(task);
                }
            }
            this.dispatch("getTaskBasic", task.parent_id);
        }
        this.dispatch("saveTask", task);
    },

    /**
     * 更新任务
     * @param state
     * @param data
     */
    taskUpdateSuccess(state, data) {
        if (data.parent_id) {
            this.dispatch("getTaskBasic", data.parent_id);
        }
        if (data.is_update_complete === true) {
            this.dispatch("getProjectBasic", {id: data.project_id});
        }
        if (data.is_update_content === true) {
            this.dispatch("getTaskContent", data.id);
        }
        this.dispatch("saveTask", data);
    },

    /**
     * 任务上传附件
     * @param state
     * @param data
     */
    taskUploadSuccess(state, data) {
        if (state.projectOpenTask.id == data.task_id) {
            let index = state.projectOpenTask.files.findIndex(({id}) => id == data.id);
            if (index > -1) {
                state.projectOpenTask.files.splice(index, 1, data);
            } else {
                state.projectOpenTask.files.push(data)
            }
        }
        state.projectDetail.project_column.some(({project_task}) => {
            let task = project_task.find(({id}) => id === data.task_id);
            if (task) {
                if (!state.method.isJson(task._file_tmp)) task._file_tmp = {}
                if (task._file_tmp[data.id] !== true) {
                    task._file_tmp[data.id] = true;
                    this.dispatch("saveTask", {
                        id: task.id,
                        file_num: task.file_num + 1,
                    });
                }
                return true;
            }
        });
    },

    /**
     * 任务打开聊天
     * @param state
     * @param data
     */
    taskDialogSuccess(state, data) {
        this.dispatch("saveTask", data);
        this.dispatch("getDialogMsgList", data.dialog_id);
    },

    /**
     * 删除任务
     * @param state
     * @param data
     */
    taskDeleteSuccess(state, data) {
        const column = state.projectDetail.project_column.find(({id}) => id === data.column_id);
        if (column) {
            let index = column.project_task.findIndex(({id}) => id === data.id);
            if (index > -1) {
                column.project_task.splice(index, 1);
            }
        }
        if (data.id == state.projectOpenTask.id) {
            state.projectOpenTask = Object.assign({}, state.projectOpenTask, {_show: false});
        } else if (data.parent_id == state.projectOpenTask.id && state.projectOpenTask.sub_task) {
            let index = state.projectOpenTask.sub_task.findIndex(({id}) => id === data.id);
            if (index > -1) {
                state.projectOpenTask.sub_task.splice(index, 1)
            }
        }
        let index = state.calendarTask.findIndex(({id}) => id === data.id);
        if (index > -1) {
            state.calendarTask.splice(index, 1)
        }
        this.dispatch("getProjectBasic", {id: data.project_id});
    },

    /**
     * 会话消息列表
     * @param state
     * @param data
     */
    dialogMsgListSuccess(state, data) {
        const dialog = data.dialog;
        const list = data.data;
        // 更新当前会话消息
        if (state.dialogId == dialog.id) {
            state.dialogDetail = dialog;
            list.forEach((item) => {
                let index = state.dialogMsgList.findIndex(({id}) => id == item.id);
                if (index === -1) {
                    state.dialogMsgList.unshift(item);
                } else {
                    state.dialogMsgList.splice(index, 1, item);
                }
            })
        }
        // 页数数据
        state.dialogMsgCurrentPage = data.current_page;
        state.dialogMsgHasMorePages = data.current_page < data.last_page;
        // 更新会话数据
        this.dispatch("saveDialog", dialog);
    },
}
