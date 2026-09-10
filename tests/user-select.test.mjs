import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import test from 'node:test';
import compiler from 'vue-template-compiler';

const source = readFileSync(new URL('../resources/assets/js/components/UserSelect.vue', import.meta.url), 'utf8');
const component = compiler.parseComponent(source);
const options = new Function('mapState', component.script.content
    .replace(/import .*?;\n/g, '')
    .replace('export default', 'return'))(() => ({}));

function fixture(selects = [1, 2, 3], uncancelable = []) {
    const element = {scrollLeft: 0, scrollWidth: 500};
    const ticks = [];
    const vm = {
        ...options.data(), selects, uncancelable,
        $refs: {selected: {scrollElement: () => element}},
        $nextTick: fn => ticks.push(fn),
    };
    for (const [key, method] of Object.entries(options.methods)) vm[key] = method.bind(vm);
    return {vm, element, flush: () => ticks.splice(0).forEach(fn => fn())};
}

test('template compiles and removal button is guarded and stops click propagation', () => {
    assert.deepEqual(compiler.compile(component.template.content).errors, []);
    assert.match(component.template.content, /v-if="!isUncancelable\(item.userid\)"[\s\S]*?@click.stop="onRemoveItem\(item.userid\)"/);
});

test('mouse avatar clicks do not remove; touch and pen do; fallback follows last input', () => {
    const {vm} = fixture();
    vm.onSelectedAvatarClick({pointerType: 'mouse'}, 1);
    assert.deepEqual(vm.selects, [1, 2, 3]);
    vm.onSelectedAvatarClick({pointerType: 'touch'}, 1);
    vm.onSelectedAvatarClick({pointerType: 'pen'}, 2);
    vm.selectedPointerType = 'touch';
    vm.onSelectedAvatarClick({}, 3);
    assert.deepEqual(vm.selects, []);
});

test('uncancelable members survive both avatar and direct removal', () => {
    const {vm} = fixture([1, 2], [2]);
    vm.onSelectedAvatarClick({pointerType: 'touch'}, 2);
    vm.onRemoveItem(2);
    vm.onRemoveItem(1);
    assert.deepEqual(vm.selects, [2]);
});

test('Backspace skips locked tail and scrolls to the updated end after rendering', () => {
    const {vm, element, flush} = fixture([1, 2, 3], [3]);
    vm.onKeydown({key: 'Backspace'});
    vm.onKeyup({key: 'Backspace'});
    assert.deepEqual(vm.selects, [1, 3]);
    assert.equal(element.scrollLeft, 0);
    element.scrollWidth = 380;
    flush();
    assert.equal(element.scrollLeft, 380);
});

test('Backspace does not remove while searching, composing, or when all members are locked', () => {
    for (const kind of ['search', 'composition', 'locked']) {
        const {vm, element, flush} = fixture([1], kind === 'locked' ? [1] : []);
        if (kind === 'search') vm.searchKey = 'a';
        const event = {key: 'Backspace', isComposing: kind === 'composition'};
        vm.onKeydown(event);
        vm.onKeyup(event);
        flush();
        assert.deepEqual(vm.selects, [1]);
        assert.equal(element.scrollLeft, 0);
    }
});

test('deleting the final member or closing before next tick safely skips scrolling', () => {
    const {vm, flush} = fixture([1]);
    vm.onKeydown({key: 'Backspace'});
    vm.onKeyup({key: 'Backspace'});
    assert.deepEqual(vm.selects, []);
    delete vm.$refs.selected;
    assert.doesNotThrow(flush);
});
