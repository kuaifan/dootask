export const projectTableGroups = [
    {key: 'my', title: '我的任务', expandedKey: 'showMy', defaultVisible: true},
    {key: 'help', title: '协助的任务', expandedKey: 'showHelp', defaultVisible: true},
    {key: 'undone', title: '未完成任务', expandedKey: 'showUndone', defaultVisible: true},
    {key: 'completed', title: '已完成任务', expandedKey: 'showCompleted'},
    {key: 'dueThisWeek', title: '本周到期', expandedKey: 'showDueThisWeek', week: 0, kind: 'due'},
    {key: 'dueNextWeek', title: '下周到期', expandedKey: 'showDueNextWeek', week: 1, kind: 'due'},
    {key: 'plannedThisWeek', title: '本周排期', expandedKey: 'showPlannedThisWeek', week: 0, kind: 'planned'},
    {key: 'plannedNextWeek', title: '下周排期', expandedKey: 'showPlannedNextWeek', week: 1, kind: 'planned'},
    {key: 'overdue', title: '已逾期', expandedKey: 'showOverdue', kind: 'overdue'},
    {key: 'unscheduled', title: '未排期', expandedKey: 'showUnscheduled', kind: 'unscheduled'},
];

export function normalizeProjectTableGroups(value) {
    const selected = Array.isArray(value)
        ? projectTableGroups.filter(group => value.includes(group.key)).map(group => group.key)
        : [];
    return selected.length ? selected : projectTableGroups.filter(group => group.defaultVisible).map(group => group.key);
}

export function projectWeekRanges(now) {
    // Do not depend on the current translation locale's first day of the week.
    const monday = now.startOf('day').subtract((now.day() + 6) % 7, 'day');
    return [0, 1].map(week => ({
        start: monday.add(week * 7, 'day').valueOf(),
        end: monday.add((week + 1) * 7, 'day').valueOf(),
    }));
}

export function taskMatchesProjectWeek(task, range, kind, parseDate) {
    if (!task.end_at) return false;
    const end = parseDate(task.end_at).valueOf();
    if (!Number.isFinite(end)) return false;
    if (kind === 'due') return end >= range.start && end < range.end;
    if (!task.start_at) return false;
    const start = parseDate(task.start_at).valueOf();
    return Number.isFinite(start) && start <= end && start < range.end && end >= range.start;
}

export function taskMatchesProjectGroup(task, group, ranges, now, parseDate) {
    if (group.kind === 'unscheduled') {
        return !task.complete_at && (!task.start_at || !task.end_at);
    }
    if (group.kind === 'overdue') {
        return !task.complete_at && !!task.end_at && parseDate(task.end_at).valueOf() < now;
    }
    return taskMatchesProjectWeek(task, ranges[group.week], group.kind, parseDate);
}
