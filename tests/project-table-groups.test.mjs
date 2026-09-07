import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import test from 'node:test';
import dayjs from 'dayjs';
import compiler from 'vue-template-compiler';
import {parse} from '@babel/parser';

const read = path => readFileSync(new URL(`../${path}`, import.meta.url), 'utf8');
const helpers = await import(`data:text/javascript;base64,${Buffer.from(read('resources/assets/js/utils/projectTableGroups.js')).toString('base64')}`);
const {projectTableGroups, normalizeProjectTableGroups, projectWeekRanges, taskMatchesProjectWeek, taskMatchesProjectGroup} = helpers;
const component = compiler.parseComponent(read('resources/assets/js/pages/manage/components/ProjectPanel.vue'));
const ast = parse(component.script.content, {sourceType: 'module'});
const stableScrolls = [];
const bindings = {
    ...helpers,
    scrollToStableTarget: options => {stableScrolls.push(options); return () => {};},
    mapState: () => ({}),
    mapGetters: () => ({}),
    $A: {
        dayjs,
        daytz: seconds => dayjs.unix(seconds),
        sortFloat: (a, b) => (parseFloat(a) || 0) - (parseFloat(b) || 0),
        sortDay: (a, b) => dayjs(a).valueOf() - dayjs(b).valueOf(),
        leftExists: (value, prefix) => String(value || '').startsWith(prefix),
        strExists: (value, search) => String(value).includes(search),
    },
};
let script = component.script.content;
// Load the real component's options without mounting unrelated application services.
for (const node of [...ast.program.body].reverse()) {
    if (node.type !== 'ImportDeclaration') continue;
    for (const specifier of node.specifiers) bindings[specifier.local.name] ??= {};
    script = script.slice(0, node.start) + script.slice(node.end);
}
const panel = new Function(...Object.keys(bindings), script.replace('export default', 'return'))(...Object.values(bindings));
const ranges = projectWeekRanges(dayjs('2026-09-09 12:00:00'));
const matches = (task, week = 0, kind = 'due') => taskMatchesProjectWeek(task, ranges[week], kind, dayjs);
const defaults = ['my', 'help', 'undone'];

test('old or invalid caches use the three default groups; valid choices stay isolated', () => {
    for (const value of [undefined, null, {}, [], ['unknown']]) {
        assert.deepEqual(normalizeProjectTableGroups(value), defaults);
    }
    assert.deepEqual(normalizeProjectTableGroups(['dueNextWeek', 'dueNextWeek', 'unknown']), ['dueNextWeek']);
    assert.deepEqual(normalizeProjectTableGroups(['completed']), ['completed']);
    assert.deepEqual(normalizeProjectTableGroups(['plannedThisWeek', 'plannedNextWeek']), ['plannedThisWeek', 'plannedNextWeek']);
    assert.equal(projectTableGroups.find(group => group.key === 'plannedThisWeek').title, '本周排期');
    assert.equal(projectTableGroups.find(group => group.key === 'plannedNextWeek').title, '下周排期');
    assert.deepEqual(normalizeProjectTableGroups(['overdue', 'unscheduled']), ['overdue', 'unscheduled']);
    const firstProject = normalizeProjectTableGroups();
    firstProject.push('completed');
    assert.deepEqual(normalizeProjectTableGroups(), defaults);
});

test('weeks start on Monday regardless of locale, including Sundays and year changes', () => {
    for (const date of ['2026-09-07', '2026-09-09', '2026-09-13']) {
        assert.deepEqual(projectWeekRanges(dayjs(date)), ranges);
    }
    assert.equal(dayjs(ranges[0].start).format('YYYY-MM-DD HH:mm'), '2026-09-07 00:00');
    assert.equal(ranges[0].end, ranges[1].start);
    const newYear = projectWeekRanges(dayjs('2027-01-01'));
    assert.equal(dayjs(newYear[0].start).format('YYYY-MM-DD'), '2026-12-28');
    assert.equal(dayjs(newYear[1].start).format('YYYY-MM-DD'), '2027-01-04');
});

test('due dates include Monday midnight but exclude the following Monday', () => {
    assert.equal(matches({end_at: '2026-09-06 23:59:59'}), false);
    assert.equal(matches({end_at: '2026-09-07 00:00:00'}), true);
    assert.equal(matches({end_at: '2026-09-13 23:59:59'}), true);
    assert.equal(matches({end_at: '2026-09-14 00:00:00'}), false);
    assert.equal(matches({end_at: '2026-09-14 00:00:00'}, 1), true);
    assert.equal(matches({end_at: '2026-09-21 00:00:00'}, 1), false);
});

test('planned tasks overlap either week and may appear in both', () => {
    const task = {start_at: '2026-09-05', end_at: '2026-09-18'};
    assert.equal(matches(task, 0, 'planned'), true);
    assert.equal(matches(task, 1, 'planned'), true);
    assert.equal(matches({start_at: '2026-09-06', end_at: '2026-09-07'}, 0, 'planned'), true);
    assert.equal(matches({start_at: '2026-09-14', end_at: '2026-09-15'}, 0, 'planned'), false);
    assert.equal(matches({start_at: '2026-09-07', end_at: '2026-09-07'}, 0, 'planned'), true);
});

test('missing, invalid or reversed plans do not enter planned groups', () => {
    for (const task of [
        {}, {start_at: '2026-09-08'}, {end_at: '2026-09-08'},
        {start_at: 'invalid', end_at: '2026-09-08'},
        {start_at: '2026-09-08', end_at: 'invalid'},
        {start_at: '2026-09-09', end_at: '2026-09-08'},
    ]) assert.equal(matches(task, 0, 'planned'), false);
    assert.equal(matches({end_at: 'invalid'}), false);
    assert.equal(matches({end_at: '2026-09-08'}), true);
});

test('selection cannot remove the last group and reselecting expands only that group', () => {
    const updates = [];
    const scrolls = [];
    const context = {
        projectId: 12,
        tableGroupOptions: projectTableGroups,
        visibleTableGroups: ['my'],
        toggleParameter: update => updates.push(update),
        scrollToTableGroup: key => scrolls.push(key),
        cancelTableGroupScroll() {},
    };
    panel.methods.setTableGroupVisible.call(context, projectTableGroups[0], false);
    assert.equal(updates.length, 0);
    assert.equal(scrolls.length, 0);
    panel.methods.setTableGroupVisible.call(context, projectTableGroups[4], true);
    assert.deepEqual(updates.pop(), {
        project_id: 12,
        key: {tableGroups: ['my', 'dueThisWeek'], showDueThisWeek: true},
    });
    context.visibleTableGroups = ['my', 'dueThisWeek'];
    panel.methods.setTableGroupVisible.call(context, projectTableGroups[0], false);
    assert.deepEqual(updates.pop(), {project_id: 12, key: {tableGroups: ['dueThisWeek']}});
    assert.deepEqual(scrolls, ['dueThisWeek']);
});

test('existing Vuex parameter action persists group choices per project without changing view or collapse state', () => {
    const stored = {};
    const api = {
        syncDispatch() {},
        isJson: value => !!value && typeof value === 'object' && !Array.isArray(value),
        IDBSave: (key, value) => { stored[key] = structuredClone(value); },
    };
    const method = (file, key) => {
        const source = read(file);
        const declaration = parse(source, {sourceType: 'module'}).program.body.find(node => node.type === 'ExportDefaultDeclaration').declaration;
        const property = declaration.properties.find(node => (node.key.name || node.key.value) === key);
        return new Function('$A', `return ({${source.slice(property.start, property.end)}})[${JSON.stringify(key)}]`)(api);
    };
    const action = method('resources/assets/js/store/actions.js', 'toggleProjectParameter');
    const mutation = method('resources/assets/js/store/mutations.js', 'project/parameter/splice');
    const getter = method('resources/assets/js/store/getters.js', 'projectData');
    const state = {
        projectId: 12,
        cacheProjects: [{id: 12}, {id: 13}],
        cacheProjectParameter: [
            {project_id: 12, menuType: 'table', showMy: false, showHelp: true},
            {project_id: 13, menuType: 'column', tableGroups: ['completed']},
        ],
    };
    const context = {
        projectId: 12, visibleTableGroups: defaults, tableGroupOptions: projectTableGroups,
        scrollToTableGroup() {},
        toggleParameter: update => action({state, commit: (type, payload) => {
            assert.equal(type, 'project/parameter/splice');
            mutation(state, payload);
        }}, update),
    };
    panel.methods.setTableGroupVisible.call(context, projectTableGroups[4], true);
    state.cacheProjectParameter = stored.cacheProjectParameter;
    const restored = getter(state).cacheParameter;
    assert.deepEqual(restored.tableGroups, ['my', 'help', 'undone', 'dueThisWeek']);
    assert.equal(restored.showMy, false);
    assert.equal(restored.showDueThisWeek, true);
    assert.equal(restored.menuType, 'table');
    state.projectId = 13;
    assert.deepEqual(getter(state).cacheParameter.tableGroups, ['completed']);
    assert.equal(getter(state).cacheParameter.menuType, 'column');
});

test('new group scrolling waits for DOM updates and replaces or skips stale requests', () => {
    const callbacks = [];
    stableScrolls.length = 0;
    const container = {
        scrollTop: 100, scrollLeft: 48, clientTop: 1,
        getBoundingClientRect: () => ({top: 20}),
        querySelector: selector => selector === '[data-table-group="my"]' ? {getBoundingClientRect: () => ({top: 221})} : null,
    };
    const context = {
        projectId: 12, tabTypeActive: 'table', visibleTableGroups: ['my'],
        $refs: {projectTableScroll: {scrollElement: () => container}},
        $nextTick: callback => callbacks.push(callback),
    };
    context.cancelTableGroupScroll = panel.methods.cancelTableGroupScroll.bind(context);
    const schedule = () => panel.methods.scrollToTableGroup.call(context, 'my');
    schedule();
    assert.equal(stableScrolls.length, 0);
    callbacks.pop()();
    const initial = stableScrolls.pop();
    assert.equal(initial.container, container);
    assert.equal(initial.isActive(), true);
    schedule();
    assert.equal(initial.isActive(), false);
    schedule();
    callbacks.shift()();
    assert.equal(stableScrolls.length, 0);
    callbacks.pop()();
    assert.equal(stableScrolls.length, 1);
    stableScrolls.length = 0;
    for (const invalidate of [
        () => { context.projectId = 13; },
        () => { context.tabTypeActive = 'column'; },
        () => { context.visibleTableGroups = []; },
        () => { context.$refs = {}; },
    ]) {
        context.projectId = 12;
        context.tabTypeActive = 'table';
        context.visibleTableGroups = ['my'];
        schedule();
        invalidate();
        callbacks.pop()();
        assert.equal(stableScrolls.length, 0);
    }
});

function weeklyContext() {
    const task = {
        id: 1, parent_id: 0, name: 'alpha', desc: '', start_at: '2026-09-07', end_at: '2026-09-10',
        p_level: 1, task_tag: [{name: 'tag'}], task_user: [{userid: 2, owner: 1}], flow_item_id: 3,
    };
    const context = {
        tableGroupOptions: projectTableGroups,
        visibleTableGroups: ['dueThisWeek', 'plannedThisWeek'],
        tableWeekRanges: ranges,
        nowTime: dayjs('2026-09-09 12:00:00').unix(),
        allTask: [task, {...task, id: 2, p_level: 2, end_at: '2026-09-08'},
            {...task, id: 3, parent_id: 1}, {...task, id: 4, complete_at: '2026-09-08'}],
        searchText: '', flowInfo: {}, sortType: 'desc', sortField: 'end_at',
        projectData: {cacheParameter: {completedTask: false}},
    };
    context.parentTask = panel.computed.parentTask.call(context);
    context.panelTask = list => panel.computed.panelTask.call(context).call(context, list);
    return context;
}

test('weekly groups reuse completion, search, owner, tag and workflow filters', () => {
    const context = weeklyContext();
    const ids = () => panel.computed.additionalTableGroups.call(context)[0].tasks.map(task => task.id);
    assert.deepEqual(ids(), [2, 1]);
    context.projectData.cacheParameter.completedTask = true;
    assert.deepEqual(ids(), [2, 1, 4]);
    context.searchText = 'alpha';
    assert.equal(ids().length, 3);
    context.searchText = '2';
    assert.deepEqual(ids(), [2]);
    context.searchText = 'not found';
    assert.deepEqual(ids(), []);
    context.searchText = '';
    for (const flowInfo of [{value: 'tag:tag', tag_name: 'missing'}, {value: 'user:99', userid: 99}, {value: 4}, {value: -1}]) {
        context.flowInfo = flowInfo;
        assert.deepEqual(ids(), []);
    }
    for (const flowInfo of [{value: 'tag:tag', tag_name: 'tag'}, {value: 'user:2', userid: 2}, {value: 3}]) {
        context.flowInfo = flowInfo;
        assert.equal(ids().length, 3);
    }
});

test('weekly groups follow priority and due-date sorting in both directions', () => {
    const context = weeklyContext();
    const ids = () => panel.computed.additionalTableGroups.call(context)[0].tasks.map(task => task.id);
    assert.deepEqual(ids(), [2, 1]);
    context.sortType = 'asc';
    assert.deepEqual(ids(), [1, 2]);
    context.sortField = 'level';
    assert.deepEqual(ids(), [2, 1]);
    context.sortType = 'desc';
    assert.deepEqual(ids(), [1, 2]);
});

test('selected empty weekly groups remain present; deselected groups disappear', () => {
    const context = weeklyContext();
    context.searchText = 'not found';
    const groups = panel.computed.additionalTableGroups.call(context);
    assert.equal(groups.length, 2);
    assert.ok(groups.every(group => group.tasks.length === 0));
    context.visibleTableGroups = ['my'];
    assert.deepEqual(panel.computed.additionalTableGroups.call(context), []);
});

test('overdue requires an unfinished task strictly past its due time', () => {
    const group = projectTableGroups.find(group => group.key === 'overdue');
    const now = dayjs('2026-09-09 12:00:00').valueOf();
    const match = task => taskMatchesProjectGroup(task, group, ranges, now, dayjs);
    assert.equal(match({end_at: '2026-09-09 11:59:59'}), true);
    assert.equal(match({end_at: '2026-09-09 12:00:00'}), false);
    assert.equal(match({end_at: '2026-09-09 12:00:01'}), false);
    assert.equal(match({end_at: '2026-09-08', complete_at: '2026-09-08'}), false);
    assert.equal(match({}), false);
    assert.equal(match({end_at: 'invalid'}), false);
});

test('unscheduled includes either missing endpoint but never completed tasks', () => {
    const group = projectTableGroups.find(group => group.key === 'unscheduled');
    const match = task => taskMatchesProjectGroup(task, group, ranges, 0, dayjs);
    assert.equal(match({}), true);
    assert.equal(match({start_at: '2026-09-08'}), true);
    assert.equal(match({end_at: '2026-09-08'}), true);
    assert.equal(match({start_at: '2026-09-08', end_at: '2026-09-09'}), false);
    assert.equal(match({complete_at: '2026-09-08'}), false);
});

test('attention groups exclude completed tasks even when enabled and retain existing filters', () => {
    const context = weeklyContext();
    context.visibleTableGroups = ['overdue', 'unscheduled'];
    context.projectData.cacheParameter.completedTask = true;
    context.parentTask.push({...context.parentTask[0], id: 5, start_at: null},
        {...context.parentTask[0], id: 6, end_at: null},
        {...context.parentTask[0], id: 7, end_at: null, complete_at: '2026-09-08'});
    const groups = () => panel.computed.additionalTableGroups.call(context);
    assert.deepEqual(groups().map(group => group.tasks.map(task => task.id)), [[2], [5, 6]]);
    context.nowTime = dayjs('2026-09-11').unix();
    assert.deepEqual(groups()[0].tasks.map(task => task.id), [2, 1, 5]);
    context.searchText = '6';
    assert.deepEqual(groups().map(group => group.tasks.map(task => task.id)), [[], [6]]);
    context.searchText = '';
    context.flowInfo = {value: 'tag:missing', tag_name: 'missing'};
    assert.ok(groups().every(group => group.tasks.length === 0));
});

test('Vue template compiles and legacy groups use selection rather than task counts', () => {
    const compiled = compiler.compile(component.template.content);
    assert.deepEqual(compiled.errors, []);
    for (const key of ['my', 'help', 'undone', 'completed']) {
        assert.ok(component.template.content.includes(`v-if="visibleTableGroups.includes('${key}')"`));
    }
    assert.ok(!component.template.content.includes('v-if="projectData.task_num > 0"'));
    assert.ok(!component.template.content.includes('v-if="helpList.length"'));
});

test('settings Dropdown uses boolean visibility and is the only group settings entry', () => {
    const sfc = compiler.parseComponent(read('resources/assets/js/pages/manage/components/ProjectTableGroupDropdown.vue'));
    assert.deepEqual(compiler.compile(sfc.template.content).errors, []);
    const menu = compiler.compile(sfc.template.content).ast;
    assert.equal(menu.tag, 'Dropdown');
    assert.equal(menu.attrsMap[':visible'], 'open');
    assert.equal(menu.attrsMap['@on-visible-change'], 'open = $event');
    assert.equal(menu.attrsMap[':trigger'], "touch ? 'click' : 'hover'");
    let script = sfc.script.content;
    for (const node of [...parse(script, {sourceType: 'module'}).program.body].reverse()) {
        if (node.type === 'ImportDeclaration') script = script.slice(0, node.start) + script.slice(node.end);
    }
    const options = new Function('projectTableGroups', script.replace('export default', 'return'))(projectTableGroups);
    const emitted = [];
    const context = {value: false, $emit: (...args) => emitted.push(args)};
    assert.equal(options.computed.open.get.call(context), false);
    options.computed.open.set.call(context, true);
    assert.deepEqual(emitted.pop(), ['input', true]);
    context.value = true;
    assert.equal(options.computed.open.get.call(context), true);
    options.computed.open.set.call(context, false);
    assert.deepEqual(emitted.pop(), ['input', false]);
    assert.equal((component.template.content.match(/<ProjectTableGroupDropdown\b/g) || []).length, 1);
    const panelContext = {tableGroupsOpen: true, cancelTableGroupScroll() {}};
    panel.watch.tabTypeActive.call(panelContext, 'column');
    assert.equal(panelContext.tableGroupsOpen, false);
});
