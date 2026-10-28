// 按滚动容器可视区（上下各多算一个任务高度）更新任务的可见状态，未可见的任务只渲染占位
// 首次可见的任务逐个等待渲染，让后续任务按真实高度判断
export async function updateTaskVisibilitys(vm, target, visibilitys) {
    if (!target) {
        return
    }
    const targetItem = target.querySelectorAll('.task-item');
    if (!targetItem.length) {
        return
    }
    const targetRect = target.getBoundingClientRect();
    for (const item of targetItem) {
        const dataId = item.getAttribute('data-id');
        if (!dataId) {
            continue;
        }
        const taskRect = item.getBoundingClientRect();
        const originalVisible = visibilitys[dataId]?.visible || false;
        const currentVisible = (
            taskRect.top >= targetRect.top - taskRect.height &&
            taskRect.bottom <= targetRect.bottom + taskRect.height
        );
        if (currentVisible === originalVisible) {
            continue;
        }
        const firstVisible = visibilitys[dataId] === undefined && currentVisible;
        vm.$set(visibilitys, dataId, {
            visible: currentVisible,
            height: taskRect.height
        });
        if (firstVisible) {
            await vm.$nextTick();
        }
    }
}
