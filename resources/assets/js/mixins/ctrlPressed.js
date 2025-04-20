export const ctrlPressed = {
    data() {
        return {
            isCtrlCommandPressed: false
        };
    },

    created() {
        this.handlePointerdown = this.handlePointerdown.bind(this);
        this.handlePointerup = this.handlePointerup.bind(this);
    },

    mounted() {
        document.addEventListener('pointerdown', this.handlePointerdown);
        document.addEventListener('pointerup', this.handlePointerup);
    },

    beforeDestroy() {
        document.removeEventListener('pointerdown', this.handlePointerdown);
        document.removeEventListener('pointerup', this.handlePointerup);
    },

    methods: {
        handlePointerdown(event) {
            if (event.ctrlKey || event.metaKey) {
                this.isCtrlCommandPressed = true;
            }
        },

        handlePointerup(event) {
            if (!event.ctrlKey && !event.metaKey) {
                this.isCtrlCommandPressed = false;
            }
        }
    }
};
