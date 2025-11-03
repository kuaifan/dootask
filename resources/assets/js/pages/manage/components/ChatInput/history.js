const HISTORY_LIMIT = 50;
const HISTORY_STORAGE_KEY = 'chat-input-history';

export default {
    data() {
        return {
            historyList: [],
            historyIndex: 0,
            historyCurrent: '',
        };
    },

    methods: {
        refreshHistoryContext() {
            this.historyCurrent = '';
            this.historyList = [];
            this.historyIndex = 0;
            this.loadInputHistory();
        },

        async loadInputHistory() {
            try {
                const history = await $A.IDBValue(HISTORY_STORAGE_KEY);
                if (Array.isArray(history)) {
                    this.historyList = history;
                } else if (history && typeof history === 'object') {
                    this.historyList = Object.values(history).filter(item => typeof item === 'string');
                } else {
                    this.historyList = [];
                }
            } catch (error) {
                this.historyList = [];
            }
            this.historyIndex = this.historyList.length;
        },

        persistInputHistory(content) {
            if (!content || $A.filterInvalidLine(content) === '') {
                return;
            }
            const history = Array.isArray(this.historyList) ? [...this.historyList] : [];
            const last = history[history.length - 1];
            if (last === content) {
                this.historyIndex = history.length;
                this.historyCurrent = '';
                return;
            }
            const existIndex = history.indexOf(content);
            if (existIndex !== -1) {
                history.splice(existIndex, 1);
            }
            history.push(content);
            if (history.length > HISTORY_LIMIT) {
                history.splice(0, history.length - HISTORY_LIMIT);
            }
            this.historyList = history;
            this.historyIndex = history.length;
            this.historyCurrent = '';
            $A.IDBSet(HISTORY_STORAGE_KEY, history).catch(() => {});
        },

        applyHistoryContent(content) {
            if (!this.quill) {
                return;
            }
            if (content) {
                this.setContent(content);
            } else {
                this.quill.setText('');
            }
            this._content = content || '';
            this.$emit('input', this._content);
            this.$nextTick(() => {
                const length = this.quill.getLength();
                this.quill.setSelection(Math.max(length - 1, 0), 0);
            });
        },

        navigateHistory(direction, range) {
            if (!this.quill || !this.historyList.length) {
                return true;
            }
            if (!range || range.length !== 0) {
                return true;
            }
            if (direction === 'up') {
                if (range.index > 0) {
                    return true;
                }
                if (this.historyIndex === this.historyList.length) {
                    this.historyCurrent = this.value;
                }
                if (this.historyIndex > 0) {
                    this.historyIndex--;
                } else {
                    this.historyIndex = 0;
                }
                this.applyHistoryContent(this.historyList[this.historyIndex] || '');
                return false;
            }
            if (direction === 'down') {
                const endIndex = Math.max(this.quill.getLength() - 1, 0);
                if (range.index < endIndex) {
                    return true;
                }
                if (this.historyIndex === this.historyList.length) {
                    return true;
                }
                if (this.historyIndex < this.historyList.length - 1) {
                    this.historyIndex++;
                    this.applyHistoryContent(this.historyList[this.historyIndex] || '');
                } else {
                    this.historyIndex = this.historyList.length;
                    this.applyHistoryContent(this.historyCurrent || '');
                }
                return false;
            }
            return true;
        },
    },
};
