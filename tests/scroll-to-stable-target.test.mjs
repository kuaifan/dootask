import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import test from 'node:test';

const source = readFileSync(new URL('../resources/assets/js/utils/scrollToStableTarget.js', import.meta.url), 'utf8');

function fixture(render = () => {}) {
    let now = 0;
    let sequence = 0;
    const timers = new Map();
    const events = () => ({
        listeners: new Map(),
        addEventListener(type, fn) {this.listeners.set(type, fn);},
        removeEventListener(type) {this.listeners.delete(type);},
        dispatch(type, event = {}) {this.listeners.get(type)?.(event);},
    });
    const document = events();
    const root = events();
    const scrolls = [];
    const container = {
        ownerDocument: document, isConnected: true, scrollTop: 0, scrollLeft: 48,
        clientTop: 0, clientHeight: 400, scrollHeight: 4000,
        getBoundingClientRect: () => ({top: 20}),
        scrollTo(options) {scrolls.push({...options, at: now}); this.scrollTop = options.top;},
    };
    const target = {isConnected: true, top: 1000, getBoundingClientRect: () => ({top: 20 + target.top - container.scrollTop})};
    const start = new Function('setTimeout', 'clearTimeout', 'Date', `${source.replace('export ', '')}; return scrollToStableTarget;`)(
        (fn, delay) => {const id = ++sequence; timers.set(id, {fn, at: now + delay}); return id;},
        id => timers.delete(id), {now: () => now},
    );
    let live = true;
    const cancel = start({container, target, inputRoot: root, render: () => render({now, container, target}), isActive: () => live});
    const advance = async until => {
        while (timers.size) {
            const [id, timer] = [...timers].sort((a, b) => a[1].at - b[1].at)[0];
            if (timer.at > until) break;
            now = timer.at;
            timers.delete(id);
            timer.fn();
            for (let i = 0; i < 5; i++) await Promise.resolve();
        }
        now = until;
    };
    return {container, target, root, document, scrolls, timers, advance, cancel, invalidate: () => {live = false;}};
}

test('corrects growing and shrinking lazy rows, then stops after stable alignment', async () => {
    const f = fixture(({now, target, container}) => {
        target.top = now < 320 ? 1000 : now < 800 ? 1600 : 1200;
        container.scrollHeight = target.top + 3000;
    });
    await f.advance(3000);
    assert.equal(f.scrolls[0].behavior, 'smooth');
    assert.deepEqual(f.scrolls.slice(1).map(({top, behavior}) => [top, behavior]), [[1600, 'instant'], [1200, 'instant']]);
    assert.equal(f.container.scrollTop, 1200);
    assert.ok(f.scrolls.every(scroll => scroll.left === 48));
    assert.equal(f.timers.size, 0);
    assert.equal(f.root.listeners.size + f.document.listeners.size, 0);
});

test('clamps bottom targets and finishes without fighting the scroll limit', async () => {
    const f = fixture(({target}) => {target.top = 3950;});
    await f.advance(3000);
    assert.equal(f.container.scrollTop, 3600);
    assert.equal(f.timers.size, 0);
});

test('wheel, touch, scrollbar pointer and navigation keys cancel without further corrections', async () => {
    for (const event of ['wheel', 'touchstart', 'pointerdown', 'keydown']) {
        const f = fixture(({target}) => {target.top += 100;});
        await f.advance(160);
        if (event === 'keydown') f.document.dispatch(event, {key: 'PageDown'});
        else f.root.dispatch(event);
        const count = f.scrolls.length;
        assert.equal(f.scrolls.at(-1).behavior, 'instant');
        f.container.scrollTop = 75;
        await f.advance(4000);
        assert.equal(f.scrolls.length, count);
        assert.equal(f.container.scrollTop, 75);
        assert.equal(f.root.listeners.size + f.document.listeners.size, 0);
    }
});

test('hard deadline stops continuous relayout and also a pending render promise', async () => {
    for (const render of [({target}) => {target.top += 10;}, () => new Promise(() => {})]) {
        const f = fixture(render);
        await f.advance(4000);
        assert.equal(f.scrolls.at(-1).at, 2500);
        assert.equal(f.timers.size, 0);
        assert.equal(f.root.listeners.size + f.document.listeners.size, 0);
    }
});

test('replacement, removal and explicit cancellation clean up the old request', async () => {
    for (const invalidate of [f => f.invalidate(), f => {f.target.isConnected = false;}, f => f.cancel()]) {
        const f = fixture();
        invalidate(f);
        await f.advance(3000);
        assert.equal(f.timers.size, 0);
        assert.equal(f.root.listeners.size + f.document.listeners.size, 0);
        assert.equal(f.scrolls.length, 2);
    }
});

test('render errors stop safely and do not leave timers or listeners', async () => {
    const f = fixture(() => {throw new Error('unmounted');});
    await f.advance(3000);
    assert.equal(f.timers.size, 0);
    assert.equal(f.root.listeners.size + f.document.listeners.size, 0);
});
