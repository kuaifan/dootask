// 项目任务筛选：同组内为「或」，跨组为「且」
// flows: 工作流状态 id（-1 表示未计划）；tags: 标签名；users: 负责人 userid
export const projectFlowFilterKeys = ['flows', 'tags', 'users'];

export function emptyProjectFlowFilter() {
    return {flows: [], tags: [], users: []};
}

export function projectFlowFilterCount(filter) {
    return projectFlowFilterKeys.reduce((total, key) => total + (filter?.[key]?.length || 0), 0);
}

export function taskMatchesProjectFlowFilter(task, filter) {
    if (!filter) {
        return true;
    }
    const {flows = [], tags = [], users = []} = filter;
    if (flows.length > 0) {
        const matchFlow = flows.includes(task.flow_item_id);
        const matchUnplanned = flows.includes(-1) && !task.start_at;
        if (!matchFlow && !matchUnplanned) {
            return false;
        }
    }
    if (tags.length > 0 && !(task.task_tag || []).some(({name}) => tags.includes(name))) {
        return false;
    }
    if (users.length > 0 && !(task.task_user || []).some(({userid, owner}) => owner && users.includes(userid))) {
        return false;
    }
    return true;
}
