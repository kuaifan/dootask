export default {
    /**
     * 添加任务完成
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
                    if (in_top) {
                        column.project_task.unshift(task);
                    } else {
                        column.project_task.push(task);
                    }
                }
            }
            this.dispatch("getProjectOne", task.project_id);
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
            this.dispatch("getTaskOne", task.parent_id);
        }
        this.dispatch("saveTask", task);
    },
}
