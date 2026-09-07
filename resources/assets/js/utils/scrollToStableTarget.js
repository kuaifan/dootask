// Lazy-rendered rows can move a target after the browser has started scrolling.
export function scrollToStableTarget({container, target, inputRoot = container, render, isActive}) {
    let active = true;
    let timer;
    let deadline;
    const startedAt = Date.now();
    let stableSince = startedAt;
    let previousTop = null;
    let previousHeight = null;
    const document = container.ownerDocument;

    const desiredTop = () => Math.max(0, Math.min(
        container.scrollHeight - container.clientHeight,
        container.scrollTop + target.getBoundingClientRect().top - container.getBoundingClientRect().top - container.clientTop,
    ));
    const move = (top, behavior) => container.scrollTo({top, left: container.scrollLeft, behavior});
    const valid = () => active && container.isConnected && target.isConnected && isActive();
    const finish = (stopMotion = false) => {
        if (!active) return;
        active = false;
        clearTimeout(timer);
        clearTimeout(deadline);
        for (const event of ['wheel', 'touchstart', 'pointerdown']) inputRoot.removeEventListener(event, cancel, true);
        document.removeEventListener('keydown', onKeyDown, true);
        if (stopMotion && container.isConnected) move(container.scrollTop, 'instant');
    };
    const cancel = () => finish(true);
    const onKeyDown = event => {
        if (event.target?.closest?.('input, textarea, select, [contenteditable="true"]')) return;
        if (['ArrowUp', 'ArrowDown', 'PageUp', 'PageDown', 'Home', 'End', ' '].includes(event.key)) cancel();
    };
    const tick = async () => {
        if (!valid()) return cancel();
        try {
            await render();
            if (!valid()) return cancel();
            const now = Date.now();
            const top = desiredTop();
            const changed = previousTop === null || Math.abs(top - previousTop) > 1 || container.scrollHeight !== previousHeight;
            const aligned = Math.abs(container.scrollTop - top) <= 1;
            if (changed || !aligned) stableSince = now;
            previousTop = top;
            previousHeight = container.scrollHeight;
            // Give the first smooth scroll time to finish; corrections must not start another animation.
            if (!aligned && now - startedAt >= 600) move(top, 'instant');
            if (aligned && now - startedAt >= 600 && now - stableSince >= 240) return finish();
            timer = setTimeout(tick, 80);
        } catch {
            cancel();
        }
    };

    if (!valid()) return cancel;
    for (const event of ['wheel', 'touchstart', 'pointerdown']) inputRoot.addEventListener(event, cancel, {capture: true, passive: true});
    document.addEventListener('keydown', onKeyDown, true);
    deadline = setTimeout(cancel, 2500);
    move(desiredTop(), 'smooth');
    timer = setTimeout(tick, 80);
    return cancel;
}
