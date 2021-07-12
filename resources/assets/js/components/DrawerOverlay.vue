<template>
    <div :class="['drawer-overlay', placement, value ? 'overlay-visible' : 'overlay-hide']" @click="mask">
        <div class="overlay-body" :style="bodyStyle">
            <div class="overlay-close">
                <a href="javascript:void(0)" @click.stop="close">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 26 26" fill="none" role="img" class="icon fill-current">
                        <path d="M8.28596 6.51819C7.7978 6.03003 7.00634 6.03003 6.51819 6.51819C6.03003 7.00634 6.03003 7.7978 6.51819 8.28596L11.2322 13L6.51819 17.714C6.03003 18.2022 6.03003 18.9937 6.51819 19.4818C7.00634 19.97 7.7978 19.97 8.28596 19.4818L13 14.7678L17.714 19.4818C18.2022 19.97 18.9937 19.97 19.4818 19.4818C19.97 18.9937 19.97 18.2022 19.4818 17.714L14.7678 13L19.4818 8.28596C19.97 7.7978 19.97 7.00634 19.4818 6.51819C18.9937 6.03003 18.2022 6.03003 17.714 6.51819L13 11.2322L8.28596 6.51819Z" fill="currentColor"></path>
                    </svg>
                </a>
            </div>
            <div class="overlay-content" @click.stop="">
                <slot/>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        name: 'DrawerOverlay',
        props: {
            value: {
                type: Boolean,
                default: false
            },
            maskClosable: {
                type: Boolean,
                default: true
            },
            escClosable: {
                type: Boolean,
                default: true
            },
            placement: {
                validator (value) {
                    return ['right', 'bottom'].includes(value)
                },
                default: 'bottom'
            },
            size: {
                type: [Number, String],
                default: "100%"
            },
        },

        data() {
            return {

            }
        },

        mounted () {
            document.addEventListener('keydown', this.escClose);
        },

        beforeDestroy () {
            document.removeEventListener('keydown', this.escClose);
        },

        computed: {
            bodyStyle() {
                let size = parseInt(this.size);
                size = size <= 100 ? `${size}%` : `${size}px`
                if (this.placement == 'right') {
                    return {
                        width: size,
                        height: "100%"
                    }
                } else {
                    return {
                        width: "100%",
                        height: size,
                    }
                }
            }
        },

        methods: {
            mask () {
                if (this.maskClosable) {
                    this.close()
                }
            },
            close() {
                this.$emit("input", !this.value)
            },
            escClose(e) {
                if (this.value && this.escClosable) {
                    if (e.keyCode === 27) {
                        this.close()
                    }
                }
            }
        }
    }
</script>
