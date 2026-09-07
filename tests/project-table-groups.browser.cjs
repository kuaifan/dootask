// Isolated UI fixture: real panel markup, Dropdown and styles; no backend or dev server.
// PLAYWRIGHT_MODULE=/path/to/playwright BROWSER_PATH=/path/to/chrome node tests/project-table-groups.browser.cjs
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {parse} = require('@babel/parser');
const compiler = require('vue-template-compiler');
const sass = require('sass');
const {chromium} = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const root = path.resolve(__dirname, '..');
const read = file => fs.readFileSync(path.join(root, file), 'utf8');
const sfc = compiler.parseComponent(read('resources/assets/js/pages/manage/components/ProjectPanel.vue'));
const sourceTemplate = sfc.template.content.trim();
const templateAst = compiler.compile(sourceTemplate, {outputSourceRange: true}).ast;
function find(node, predicate) {
    if (predicate(node)) return node;
    for (const child of node.children || []) {
        const match = find(child, predicate);
        if (match) return match;
    }
    for (const branch of (node.ifConditions || []).slice(1)) {
        const match = find(branch.block, predicate);
        if (match) return match;
    }
}
const switchNode = find(templateAst, node => node.attrsMap?.class === 'project-switch-button');
const settingsNode = find(templateAst, node => node.attrsMap?.class === 'project-table-settings');
const flowNode = find(templateAst, node => node.attrsMap?.class === 'project-select');
const tableNode = find(templateAst, node => node.attrsMap?.class === 'project-table');
const switchTemplate = sourceTemplate.slice(switchNode.start, switchNode.end);
const settingsTemplate = sourceTemplate.slice(settingsNode.start, settingsNode.end);
const flowTemplate = sourceTemplate.slice(flowNode.start, flowNode.end);
const tableTemplate = sourceTemplate.slice(tableNode.start, tableNode.end).replace('v-else-if=', 'v-if=');
const template = `<div class="project-panel"><div class="project-subbox"><div class="project-subtitle"></div><div class="project-switch">${flowTemplate}${settingsTemplate}${switchTemplate}</div></div>${tableTemplate}</div>`;
const scriptAst = parse(sfc.script.content, {sourceType: 'module'});
let script = sfc.script.content;
const imports = [];
for (const node of [...scriptAst.program.body].reverse()) {
    if (node.type !== 'ImportDeclaration') continue;
    if (!['projectTableGroups', 'scrollToStableTarget'].some(name => node.source.value.endsWith(name))) {
        imports.push(...node.specifiers.map(specifier => specifier.local.name));
    }
    script = script.slice(0, node.start) + script.slice(node.end);
}
const declarations = imports.map(name => `const ${name} = ${['mapState', 'mapGetters'].includes(name) ? '()=>({})' : '{}'};`).join('\n');
const factory = declarations + read('resources/assets/js/utils/projectTableGroups.js').replace(/export /g, '') + read('resources/assets/js/utils/scrollToStableTarget.js').replace(/export /g, '') + script.replace('export default', 'return');
const dropdownComponent = compiler.parseComponent(read('resources/assets/js/pages/manage/components/ProjectTableGroupDropdown.vue'));
let dropdownScript = dropdownComponent.script.content;
for (const node of [...parse(dropdownScript, {sourceType: 'module'}).program.body].reverse()) {
    if (node.type === 'ImportDeclaration') dropdownScript = dropdownScript.slice(0, node.start) + dropdownScript.slice(node.end);
}
const dropdownFactory = read('resources/assets/js/utils/projectTableGroups.js').replace(/export /g, '') + dropdownScript.replace('export default', 'return');
const css = sass.compileString(read('resources/assets/sass/var.scss') + read('resources/assets/sass/pages/components/project-panel.scss')).css;
const output = path.join(root, 'tests/playwright-results/project-table-groups');
fs.mkdirSync(output, {recursive: true});

(async () => {
    const browser = await chromium.launch({executablePath: process.env.BROWSER_PATH, headless: true, args: ['--no-sandbox']});
    try {
        for (const mobile of [false, true]) {
            const context = await browser.newContext({viewport: mobile ? {width: 390, height: 844} : {width: 1440, height: 900}, hasTouch: mobile});
            const page = await context.newPage();
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            await page.setContent('<html><body><div id="app"></div></body></html>');
            await page.addStyleTag({path: path.join(root, 'node_modules/view-design-hi/dist/styles/iview.css')});
            await page.addStyleTag({content: css + '\nbody{font-family:Arial,sans-serif}.project-panel{height:100vh}.project-subbox{padding-top:24px!important}.project-table{overflow:auto}'});
            const font = fs.readFileSync(path.join(root, 'resources/assets/statics/public/css/fonts/taskfont/iconfont.woff2')).toString('base64');
            await page.addStyleTag({content: `@font-face{font-family:taskfont;src:url(data:font/woff2;base64,${font}) format('woff2')}.taskfont{font-family:taskfont!important;font-style:normal}`});
            const iconFont = fs.readFileSync(path.join(root, 'node_modules/view-design-hi/dist/styles/fonts/ionicons.woff2')).toString('base64');
            await page.addStyleTag({content: `@font-face{font-family:Ionicons;src:url(data:font/woff2;base64,${iconFont}) format('woff2')}`});
            await page.addScriptTag({path: path.join(root, 'node_modules/vue/dist/vue.js')});
            await page.addScriptTag({path: path.join(root, 'node_modules/dayjs/dayjs.min.js')});
            await page.addScriptTag({path: path.join(root, 'node_modules/view-design-hi/dist/iview.min.js')});
            await page.evaluate(({factory, template, mobile, dropdownFactory, dropdownTemplate}) => {
                Vue.use(iview);
                Vue.prototype.$L = value => value;
                Vue.prototype.$isEEUIApp = false;
                const helpers = {
                    dayjs, daytz: value => value === undefined ? dayjs() : dayjs.unix(value),
                    leftExists: (value, prefix) => String(value || '').startsWith(prefix),
                    sortDay: (a, b) => dayjs(a).valueOf() - dayjs(b).valueOf(),
                    sortFloat: (a, b) => a - b,
                };
                const options = new Function('$A', factory)(helpers);
                const dropdown = {...new Function(dropdownFactory)(), template: dropdownTemplate};
                const computed = {};
                for (const key of ['tableGroupOptions', 'visibleTableGroups', 'tableWeekRanges', 'additionalTableGroups', 'tabTypeActive', 'tabTypeStyle', 'panelTask']) computed[key] = options.computed[key];
                window.fixture = new Vue({
                    el: '#app', template,
                    data: {
                        ...options.data(), windowTouch: mobile, projectId: 12,
                        flowTitle: '全部', flowData: [{value: 'all', label: '全部'}],
                        projectData: {task_num: 0, cacheParameter: {menuType: 'column', showMy: true, showHelp: true, showUndone: true, showCompleted: false}},
                        myList: [], helpList: [], unList: [], completedList: [], parentTask: [], isDepartmentReadonly: false,
                    },
                    computed,
                    watch: {tabTypeActive: options.watch.tabTypeActive},
                    components: {
                        ProjectTableGroupDropdown: dropdown,
                        Scrollbar: {template: '<div><slot/></div>', methods: {scrollElement() {return this.$el;}}},
                        TaskRow: {props: ['list'], template: '<div class="task-rows"><div v-for="task in list" :key="task.id">{{task.name}}</div></div>'},
                    },
                    methods: {
                        setTableGroupVisible: options.methods.setTableGroupVisible,
                        scrollToTableGroup: options.methods.scrollToTableGroup,
                        cancelTableGroupScroll: options.methods.cancelTableGroupScroll,
                        tabTypeChange: options.methods.tabTypeChange,
                        flowChange: options.methods.flowChange,
                        handleColumnDebounce() {},
                        toggleParameter(update) {
                            const patch = typeof update === 'string' ? {[update]: !this.projectData.cacheParameter[update]} : typeof update.key === 'string' ? {[update.key]: update.value} : update.key;
                            this.projectData.cacheParameter = {...this.projectData.cacheParameter, ...patch};
                        },
                        transforTasks: list => list,
                        addTaskOpen() {}, handleTaskScroll() {}, onSort() {},
                    },
                });
            }, {factory, template, mobile, dropdownFactory, dropdownTemplate: dropdownComponent.template.content});
            const flow = page.locator('.project-flow');
            const flowDropdown = page.locator('.project-panel-flow-cascader:visible');
            await flow.hover();
            if (mobile) {
                assert.equal(await flowDropdown.count(), 0);
                await flow.tap();
            }
            await flowDropdown.waitFor({state: 'visible'});
            const surfaceStyle = element => {
                const style = getComputedStyle(element);
                return {background: style.backgroundColor, radius: style.borderRadius, shadow: style.boxShadow};
            };
            const flowSurface = await flowDropdown.evaluate(surfaceStyle);
            if (!mobile) {
                await flow.click();
                await flowDropdown.hover();
                await page.waitForTimeout(250);
                assert.equal(await flowDropdown.count(), 1);
                await page.mouse.move(1, 1);
                await flowDropdown.waitFor({state: 'hidden'});
                await flow.hover();
                await flowDropdown.waitFor({state: 'visible'});
            }
            await flowDropdown.locator('.ivu-cascader-menu-item:visible').getByText('全部', {exact: true}).click();
            await flowDropdown.waitFor({state: 'hidden'});
            assert.equal(await page.evaluate(() => fixture.flowInfo.value), 'all');
            await page.evaluate(() => { fixture.flowInfo = {}; });
            const icon = page.locator('.project-switch-button > div').nth(2);
            const settings = page.locator('.project-table-settings-icon');
            const dropdown = page.locator('.project-table-groups-dropdown:visible');
            await icon.hover();
            await page.waitForTimeout(250);
            assert.equal(await dropdown.count(), 0);
            assert.equal(await settings.count(), 0);
            await icon.click();
            await settings.waitFor({state: 'visible'});
            await icon.hover();
            await icon.click();
            await page.waitForTimeout(250);
            assert.equal(await dropdown.count(), 0);
            if (mobile) await settings.tap();
            else await settings.hover();
            await dropdown.waitFor({state: 'visible'});
            assert.deepEqual(await dropdown.evaluate(surfaceStyle), flowSurface);
            assert.equal(await dropdown.locator('.ivu-poptip-arrow, .ivu-poptip-title').count(), 0);
            const settingsBounds = await settings.boundingBox();
            assert.equal(await page.locator('.project-switch-button').count(), 1, JSON.stringify({errors, html: await page.locator('.project-switch').innerHTML()}));
            const switchBounds = await page.locator('.project-switch-button').boundingBox();
            assert.ok(settingsBounds.x + settingsBounds.width <= switchBounds.x);
            const boxes = dropdown.locator('input[type=checkbox]');
            assert.equal(await boxes.count(), 10);
            assert.equal(await dropdown.locator('input:checked').count(), 3);
            await dropdown.getByText('本周到期', {exact: true}).click();
            assert.equal(await boxes.nth(4).isChecked(), true);
            assert.equal(await page.locator('.project-table-body').count(), 4);
            assert.equal(await page.locator('.project-table-empty').count(), 4);
            for (const label of ['本周排期', '下周排期', '已逾期', '未排期']) {
                await dropdown.getByText(label, {exact: true}).click();
                assert.equal(await page.locator('.row-h1').getByText(label, {exact: true}).count(), 1);
                await dropdown.getByText(label, {exact: true}).click();
                assert.equal(await page.locator('.row-h1').getByText(label, {exact: true}).count(), 0);
            }
            for (const label of ['我的任务', '协助的任务', '未完成任务']) await dropdown.getByText(label, {exact: true}).click();
            assert.equal(await boxes.nth(4).isDisabled(), true);
            assert.equal(await page.locator('.project-table-body').count(), 1);
            await page.waitForFunction(() => fixture.visibleTableGroups.length === 1);
            const bounds = await dropdown.boundingBox();
            const viewport = page.viewportSize();
            assert.ok(bounds.x >= 0 && bounds.y >= 0 && bounds.x + bounds.width <= viewport.width + 1 && bounds.y + bounds.height <= viewport.height);
            await page.screenshot({path: path.join(output, mobile ? 'mobile.png' : 'desktop.png'), animations: 'disabled'});
            if (mobile) await icon.tap();
            else await icon.hover();
            await dropdown.waitFor({state: 'hidden'});
            assert.equal(await dropdown.count(), 0);
            assert.equal(await page.evaluate(() => fixture.tableGroupsOpen), false);
            if (mobile) await settings.tap();
            else await settings.hover();
            await dropdown.waitFor({state: 'visible'});
            assert.equal(await dropdown.locator('input:checked').count(), 1);
            await dropdown.getByText('已完成任务', {exact: true}).click();
            assert.equal(await page.evaluate(() => fixture.projectData.cacheParameter.showCompleted), true);
            await page.locator('.row-title .taskfont').first().click();
            assert.equal(await page.evaluate(() => fixture.projectData.cacheParameter.showCompleted), false);
            await page.evaluate(async mobile => {
                fixture.myList = Array.from({length: 80}, (_, id) => ({id, name: `Task ${id + 1}`}));
                fixture.projectData.cacheParameter = {...fixture.projectData.cacheParameter, tableGroups: ['my'], showMy: true};
                await fixture.$nextTick();
                const container = fixture.$refs.projectTableScroll.scrollElement();
                container.scrollTop = 0;
                container.scrollLeft = mobile ? 48 : 0;
                window.scrollTest = {left: container.scrollLeft, pageTop: window.scrollY, calls: []};
                const scrollTo = container.scrollTo.bind(container);
                container.scrollTo = options => {scrollTest.calls.push(options); scrollTo(options);};
            }, mobile);
            if (mobile) await settings.tap();
            else await settings.hover();
            await dropdown.waitFor({state: 'visible'});
            for (const [label, key] of [['未排期', 'unscheduled'], ['已完成任务', 'completed']]) {
                if (!mobile) await dropdown.hover();
                const before = await page.evaluate(() => scrollTest.calls.length);
                await dropdown.getByText(label, {exact: true}).click();
                await page.waitForFunction(key => {
                    const container = fixture.$refs.projectTableScroll.scrollElement();
                    const target = container.querySelector(`[data-table-group="${key}"]`);
                    const bounds = container.getBoundingClientRect();
                    const heading = target?.querySelector('.row-title').getBoundingClientRect();
                    return container.scrollTop > 0 && heading && heading.top >= bounds.top - 1 && heading.bottom <= bounds.top + container.clientHeight + 1;
                }, key);
                await page.waitForTimeout(1000);
                assert.ok(await page.evaluate(before => scrollTest.calls.length >= before + 1, before));
                assert.equal(await dropdown.count(), 1, JSON.stringify(await page.evaluate(() => ({open: fixture.tableGroupsOpen, pageTop: window.scrollY, before: scrollTest, settings: document.querySelector('.project-table-settings').getBoundingClientRect().toJSON(), container: fixture.$refs.projectTableScroll.scrollElement().getBoundingClientRect().toJSON()}))));
                assert.ok(await page.evaluate(() => {
                    const container = fixture.$refs.projectTableScroll.scrollElement();
                    return container.scrollLeft === scrollTest.left && window.scrollY === scrollTest.pageTop;
                }));
                if (key === 'unscheduled') await page.screenshot({path: path.join(output, mobile ? 'mobile-scroll.png' : 'desktop-scroll.png'), animations: 'disabled'});
                const settled = await page.evaluate(() => scrollTest.calls.length);
                await dropdown.getByText(label, {exact: true}).click();
                await page.waitForTimeout(200);
                assert.equal(await page.evaluate(() => scrollTest.calls.length), settled);
            }
            await page.evaluate(async () => {
                fixture.projectData.cacheParameter = {...fixture.projectData.cacheParameter, tableGroups: ['my', 'unscheduled'], showUnscheduled: true};
                await fixture.$nextTick();
                const container = fixture.$refs.projectTableScroll.scrollElement();
                const spacer = document.createElement('div');
                spacer.style.height = '1000px';
                container.appendChild(spacer);
                const rows = container.querySelector('[data-table-group="my"] .task-rows');
                window.lazyScrollTest = {renders: 0};
                fixture.handleTaskScroll = async () => {
                    lazyScrollTest.renders++;
                    if (lazyScrollTest.renders === 3) rows.style.paddingTop = '350px';
                    if (lazyScrollTest.renders === 9) rows.style.paddingTop = '180px';
                    await fixture.$nextTick();
                };
                container.scrollTop = 0;
                fixture.scrollToTableGroup('unscheduled');
            });
            await page.waitForFunction(() => {
                const container = fixture.$refs.projectTableScroll.scrollElement();
                const target = container.querySelector('[data-table-group="unscheduled"]');
                return lazyScrollTest.renders >= 10 && Math.abs(target.getBoundingClientRect().top - container.getBoundingClientRect().top - container.clientTop) <= 1;
            });
            await page.waitForTimeout(350);
            assert.ok(await page.evaluate(() => {
                const container = fixture.$refs.projectTableScroll.scrollElement();
                return Math.abs(container.querySelector('[data-table-group="unscheduled"]').getBoundingClientRect().top - container.getBoundingClientRect().top - container.clientTop) <= 1;
            }));
            await page.screenshot({path: path.join(output, mobile ? 'mobile-corrected.png' : 'desktop-corrected.png'), animations: 'disabled'});
            await page.evaluate(() => {
                fixture.handleTaskScroll = async () => fixture.$nextTick();
                fixture.$refs.projectTableScroll.scrollElement().scrollTop = 0;
                fixture.scrollToTableGroup('unscheduled');
            });
            await page.waitForTimeout(100);
            if (mobile) {
                await page.locator('.project-table').dispatchEvent('touchstart');
            } else {
                const bounds = await page.locator('.project-table').boundingBox();
                await page.mouse.move(bounds.x + 10, bounds.y + bounds.height / 2);
                await page.mouse.wheel(0, -100);
            }
            await page.waitForTimeout(200);
            const interrupted = await page.evaluate(() => ({calls: scrollTest.calls.length, top: fixture.$refs.projectTableScroll.scrollElement().scrollTop}));
            await page.waitForTimeout(800);
            assert.deepEqual(await page.evaluate(() => ({calls: scrollTest.calls.length, top: fixture.$refs.projectTableScroll.scrollElement().scrollTop})), interrupted);
            for (const mode of ['column', 'gantt']) {
                await page.evaluate(mode => fixture.tabTypeChange(mode), mode);
                await icon.hover();
                await page.waitForTimeout(250);
                assert.equal(await dropdown.count(), 0);
                assert.equal(await settings.count(), 0);
                assert.equal(await page.evaluate(() => fixture.tableGroupsOpen), false);
            }
            assert.deepEqual(errors, []);
            console.log(`${mobile ? 'Mobile' : 'Desktop'}: Cascader trigger and selection, group selection, relayout correction, manual cancellation, unchanged horizontal/page position and viewport bounds passed`);
            await context.close();
        }
    } finally {
        await browser.close();
    }
})().catch(error => {console.error(error); process.exitCode = 1;});
