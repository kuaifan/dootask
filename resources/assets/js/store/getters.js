export default {
    taskData(state) {
        let taskId = state.taskId;
        if (taskId == 0) {
            taskId = state.method.runNum(window.__taskId);
        }
        if (taskId > 0) {
            window.__taskId = taskId;
            const task = state.tasks.find(({id}) => id == taskId);
            if (task) {
                const content = state.taskContents.find(({task_id}) => task_id == taskId);
                task.content = content ? content.content : '';
                task.files = state.taskFiles.filter(({task_id}) => task_id == taskId);
                task.sub_task = state.tasks.filter(({parent_id}) => parent_id == taskId);
                return task;
            }
        }
        return {};
    },
}
