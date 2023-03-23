<template>
    <div ref="body" class="overlay-body" :style="bodyStyle">
        <div class="overlay-close">
            <a href="javascript:void(0)" @click.stop="onClose">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 26 26" fill="none" role="img" class="icon fill-current">
                    <path d="M8.28596 6.51819C7.7978 6.03003 7.00634 6.03003 6.51819 6.51819C6.03003 7.00634 6.03003 7.7978 6.51819 8.28596L11.2322 13L6.51819 17.714C6.03003 18.2022 6.03003 18.9937 6.51819 19.4818C7.00634 19.97 7.7978 19.97 8.28596 19.4818L13 14.7678L17.714 19.4818C18.2022 19.97 18.9937 19.97 19.4818 19.4818C19.97 18.9937 19.97 18.2022 19.4818 17.714L14.7678 13L19.4818 8.28596C19.97 7.7978 19.97 7.00634 19.4818 6.51819C18.9937 6.03003 18.2022 6.03003 17.714 6.51819L13 11.2322L8.28596 6.51819Z" fill="currentColor"></path>
                </svg>
            </a>
        </div>
        <ResizeLine
            v-if="resize"
            class="overlay-resize"
            v-model="dynamicSize"
            :placement="placement"
            :min="minSize"
            :max="0"
            :reverse="true"
            :beforeResize="beforeResize"
            @on-change="onChangeResize"/>
        <div class="overlay-content"><slot/></div>
    </div>
</template>

<script>
import ResizeLine from "../ResizeLine";

export default {
    name: 'DrawerOverlayView',
    components: {ResizeLine},
    props: {
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
        minSize: {
            type: Number,
            default: 300
        },
        resize: {
            type: Boolean,
            default: true
        },
    },

    data() {
        return {
            dynamicSize: 0,
        }
    },

    computed: {
        bodyStyle() {
            let size = this.dynamicSize;
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

    watch: {
        size: {
            handler(val) {
                this.dynamicSize = parseInt(val);
            },
            immediate: true
        }
    },

    methods: {
        onClose() {
            this.$emit("on-close")
        },

        beforeResize() {
            return new Promise(resolve => {
                if (this.dynamicSize <= 100) {
                    this.updateSize();
                }
                resolve()
            })
        },

        onChangeResize({event}) {
            if (event === 'up') {
                this.updateSize();
            }
        },

        updateSize() {
            if (this.placement === 'bottom') {
                this.dynamicSize = this.$refs.body.clientHeight;
            } else {
                this.dynamicSize = this.$refs.body.clientWidth;
            }
        }
    }
}
</script>
