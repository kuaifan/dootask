export const imageExtensions = ['jpg', 'jpeg', 'webp', 'png', 'gif', 'bmp'];

function extractExt(target) {
    if (!target) {
        return '';
    }
    if (typeof target === 'string') {
        return target.toLowerCase();
    }
    const ext = target.ext || '';
    if (ext) {
        return `${ext}`.toLowerCase();
    }
    if (target.name && target.name.includes('.')) {
        return target.name.split('.').pop().toLowerCase();
    }
    return '';
}

export function isImageFile(target) {
    return imageExtensions.includes(extractExt(target));
}

export function previewImageFromList(vm, items, currentItem) {
    if (!vm || !currentItem || !Array.isArray(items)) {
        return false;
    }
    if (!currentItem.image_url || !isImageFile(currentItem)) {
        return false;
    }
    const imageItems = items.filter(item => item && item.type === 'file' && isImageFile(item) && item.image_url);
    const index = imageItems.findIndex(item => item.id === currentItem.id);
    if (index === -1) {
        return false;
    }
    const previewList = imageItems.map(item => {
        if (item.image_width && item.image_height) {
            return {
                src: item.image_url,
                width: item.image_width,
                height: item.image_height,
            };
        }
        return item.image_url;
    });
    vm.$store.dispatch('previewImage', {index, list: previewList});
    return true;
}

export function openFileInClient(vm, item, options = {}) {
    if (!vm || !item) {
        return;
    }
    const path = options.path || `/single/file/${item.id}`;
    const baseTitle = options.title || item.name || vm.$L('查看');
    const sizeValue = options.size !== undefined ? options.size : item.size;
    const finalTitle = sizeValue ? `${baseTitle} (${$A.bytesToSize(sizeValue)})` : baseTitle;
    const windowName = options.windowName || `file-${item.id}`;
    const windowConfig = Object.assign({
        title: finalTitle,
        titleFixed: true,
        parent: null,
    }, options.windowConfig || {});

    if (vm.$Electron) {
        vm.$store.dispatch('openWindow', {
            name: windowName,
            path,
            title: windowConfig.title,
            titleFixed: windowConfig.titleFixed,
            width: windowConfig.width,
            height: windowConfig.height,
            force: options.force === undefined ? false : options.force,
        });
        return;
    }

    if (vm.$isEEUIApp) {
        vm.$store.dispatch('openAppChildPage', {
            pageType: 'app',
            pageTitle: finalTitle,
            url: 'web.js',
            params: Object.assign({
                titleFixed: true,
                url: $A.urlReplaceHash(path),
            }, options.appParams || {}),
        });
        return;
    }

    window.open($A.mainUrl(path.substring(1)));
}
