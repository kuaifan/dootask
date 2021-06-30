export default {
    add(text, level) {
        const anchor = `toc${level}${++this.index}`;
        const item = {anchor, level, text};
        const items = this.tocItems;

        if (item.level <= 5) {
            items.push(item);
        }

        return anchor;
    },
    reset: function () {
        this.tocItems = [];
        this.index = 0;
    },
    tocItems: [],
    index: 0
};
