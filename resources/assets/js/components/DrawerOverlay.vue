<template>
    <div
        v-transfer-dom
        :data-transfer="transfer"
        :class="['drawer-overlay', placement, value ? 'overlay-visible' : 'overlay-hide']"
        :style="overlayStyle">
        <div class="overlay-mask" @click="mask"></div>
        <div class="overlay-body" :style="bodyStyle">
            <div class="overlay-close">
                <a href="javascript:void(0)" @click.stop="close">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 26 26" fill="none" role="img" class="icon fill-current">
                        <path d="M8.28596 6.51819C7.7978 6.03003 7.00634 6.03003 6.51819 6.51819C6.03003 7.00634 6.03003 7.7978 6.51819 8.28596L11.2322 13L6.51819 17.714C6.03003 18.2022 6.03003 18.9937 6.51819 19.4818C7.00634 19.97 7.7978 19.97 8.28596 19.4818L13 14.7678L17.714 19.4818C18.2022 19.97 18.9937 19.97 19.4818 19.4818C19.97 18.9937 19.97 18.2022 19.4818 17.714L14.7678 13L19.4818 8.28596C19.97 7.7978 19.97 7.00634 19.4818 6.51819C18.9937 6.03003 18.2022 6.03003 17.714 6.51819L13 11.2322L8.28596 6.51819Z" fill="currentColor"></path>
                    </svg>
                </a>
            </div>
            <ResizeLine v-if="resize" class="overlay-resize" :placement="placement" v-model="dynamicSize" :min="minSize" :max="0" reverse/>
            <div class="overlay-content"><slot/></div>
        </div>
    </div>
</template>

<script>
    import ResizeLine from "./ResizeLine";
    import TransferDom from '../directives/transfer-dom';
    import {mapState} from "vuex";

    export default {
        name: 'DrawerOverlay',
        components: {ResizeLine},
        directives: { TransferDom },
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
            minSize: {
                type: Number,
                default: 300
            },
            resize: {
                type: Boolean,
                default: true
            },
            transfer: {
                type: Boolean,
                default: false
            },
            beforeClose: Function
        },

        data() {
            return {
                dynamicSize: 0,
                zIndex: 0,
            }
        },

        mounted () {
            document.addEventListener('keydown', this.escClose);
        },

        beforeDestroy () {
            document.removeEventListener('keydown', this.escClose);
        },

        computed: {
            ...mapState(['cacheDrawerIndex']),

            overlayStyle() {
                return {
                    zIndex: 1000 + this.zIndex
                }
            },

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
            value(val) {
                if (this._uid) {
                    const index =  this.$store.state.cacheDrawerOverlay.findIndex(({_uid}) => _uid === this._uid);
                    if (val && index === -1) {
                        this.$store.state.cacheDrawerOverlay.push({
                            _uid: this._uid,
                            close: this.close
                        });
                    }
                    if (!val && index > -1) {
                        this.$store.state.cacheDrawerOverlay.splice(index, 1);
                    }
                }
                //
                if (val) {
                    this.zIndex = this.$store.state.cacheDrawerIndex++;
                } else if (this.$store.state.cacheDrawerOverlay.length === 0) {
                    this.$store.state.cacheDrawerIndex = 0;
                }
            },
            size: {
                handler(val) {
                    this.dynamicSize = parseInt(val);
                },
                immediate: true
            }
        },

        methods: {
            mask() {
                if (this.maskClosable) {
                    this.close()
                }
            },
            close() {
                if (!this.beforeClose) {
                    return this.handleClose();
                }

                const before = this.beforeClose();

                if (before && before.then) {
                    before.then(this.handleClose);
                } else {
                    this.handleClose();
                }
            },
            handleClose () {
                this.$emit("input", false)
            },
            escClose(e) {
                if (this.value && this.escClosable) {
                    if (e.keyCode === 27) {
                        if (this.$Modal.visibles().length > 0) {
                            return;
                        }
                        const list = this.$store.state.cacheDrawerOverlay;
                        if (list.length > 0) {
                            const $Drawer = list[list.length - 1]
                            $Drawer.close();
                        }
                    }
                }
            }
        }
    }
</script>
