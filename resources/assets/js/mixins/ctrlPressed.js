export const ctrlPressed = {
    data() {
        return {
            isCtrlCommandPressed: false
        };
    },

    created() {
        this.handleKeyDown = this.handleKeyDown.bind(this);
        this.handleKeyUp = this.handleKeyUp.bind(this);
        this.handleBlur = this.handleBlur.bind(this);
    },

    mounted() {
        document.addEventListener('keydown', this.handleKeyDown);
        document.addEventListener('keyup', this.handleKeyUp);
        window.addEventListener('blur', this.handleBlur);
    },

    beforeDestroy() {
        document.removeEventListener('keydown', this.handleKeyDown);
        document.removeEventListener('keyup', this.handleKeyUp);
        window.removeEventListener('blur', this.handleBlur);
    },

    methods: {
        handleKeyDown(event) {
            if (event.ctrlKey || event.metaKey) {
                this.isCtrlCommandPressed = true;
            }
        },

        handleKeyUp(event) {
            if (!event.ctrlKey && !event.metaKey) {
                this.isCtrlCommandPressed = false;
            }
        },

        handleBlur() {
            this.isCtrlCommandPressed = false;
        }
    }
};
