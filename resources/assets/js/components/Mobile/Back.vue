<template>
    <div class="mobile-back">
        <div v-if="isVisible && x > 20" class="back-semicircle" :style="style"></div>
    </div>
</template>

<script>
import {mapState} from "vuex";
import microApp from '@micro-zoe/micro-app'

export default {
    name: "MobileBack",
    props: {
        showTabbar: {
            type: Boolean,
            default: false
        },
    },

    data() {
        return {
            x: 0,
            y: 0,

            isVisible: false,
            isTouched: false,
            isScrolling: undefined,
            touchesStart: {},
        };
    },

    created() {
        this.appAndroidEvents()
    },

    mounted() {
        document.addEventListener('touchstart', this.touchstart)
        document.addEventListener('touchmove', this.touchmove, { passive: false })
        document.addEventListener('touchend', this.touchend)
    },

    beforeDestroy() {
        document.removeEventListener('touchstart', this.touchstart)
        document.removeEventListener('touchmove', this.touchmove)
        document.removeEventListener('touchend', this.touchend)
    },

    computed: {
        ...mapState(['fileLists', 'messengerSearchKey']),

        style() {
            const offset = 135;
            const top = Math.max(offset, this.y) + this.windowScrollY,
                maxTop = this.windowHeight - offset;
            return {
                top: Math.min(top, maxTop) + 'px',
            }
        },

        routeName() {
            return this.$route.name
        },

        fileFolderId() {
            const {folderId} = this.$route.params;
            return parseInt(/^\d+$/.test(folderId) ? folderId : 0);
        },
    },

    watch: {
        isVisible(state) {
            this.$store.state.touchBackInProgress = state;
        }
    },

    methods: {
        getXY(event) {
            const touch = event.touches[0]
            this.x = touch.clientX
            this.y = touch.clientY
        },

        touchstart(event) {
            this.getXY(event)
            this.isTouched = this.canBack() && this.x < 20;
            this.isScrolling = undefined
            this.touchesStart.x = event.type === 'touchstart' ? event.targetTouches[0].pageX : event.pageX;
            this.touchesStart.y = event.type === 'touchstart' ? event.targetTouches[0].pageY : event.pageY;
        },

        touchmove(event) {
            if (!this.isTouched) {
                return;
            }
            const pageX = event.type === 'touchmove' ? event.targetTouches[0].pageX : event.pageX;
            const pageY = event.type === 'touchmove' ? event.targetTouches[0].pageY : event.pageY;
            if (typeof this.isScrolling === 'undefined') {
                this.isScrolling = !!(this.isScrolling || Math.abs(pageY - this.touchesStart.y) > Math.abs(pageX - this.touchesStart.x));
            }
            if (this.isScrolling) {
                this.isTouched = false;
                return;
            }
            this.isVisible = true
            this.getXY(event)
            event.preventDefault()
        },

        touchend() {
            // 判断停止时的位置偏移
            if (this.x > 90 && this.isVisible) {
                this.onBack();
            }
            this.x = 0
            this.isVisible = false
        },

        canBack() {
            if (!this.showTabbar) {
                return true;
            }
            if (this.$Modal.visibles().length > 0) {
                return true;
            }
            if (this.fileFolderId > 0) {
                return true;
            }
            if (this.routeName === 'manage-messenger') {
                if (this.$route.params.dialogAction === 'contacts') {
                    if (this.messengerSearchKey.contacts) {
                        return true;
                    }
                } else {
                    if (this.messengerSearchKey.dialog) {
                        return true;
                    }
                }
            }
            // 微应用
            let microAppIsVisible = false;
            microApp.setGlobalData({
                type: 'modalVisible',
                callback: (appName, isVisible) => {
                    if (isVisible) {
                        microAppIsVisible = true;
                    }
                }
            })
            if (microAppIsVisible) {
                return true;
            }
            //
            return false;
        },

        onBack() {
            // 微应用通知
            let microAppIsAccept = false;
            microApp.setGlobalData({
                type: 'route',
                action: 'back',
                route: this.$route,
                callback: (appName, isAccept) => {
                    if (isAccept) {
                        microAppIsAccept = true;
                    }
                }
            })
            if (microAppIsAccept) {
                return;
            }
            //
            if (this.$Modal.removeLast()) {
                return;
            }
            if (this.routeName === 'manage-file') {
                if (this.fileFolderId > 0) {
                    const file = this.fileLists.find(({id, permission}) => id == this.fileFolderId && permission > -1)
                    if (file) {
                        const prevFile = this.fileLists.find(({id, permission}) => id == file.pid && permission > -1)
                        if (prevFile) {
                            this.goForward({name: 'manage-file', params: {folderId: prevFile.id, fileId: null}});
                            return;
                        }
                    }
                    this.goForward({name: 'manage-file'});
                    return;
                }
                this.goForward({name: 'manage-application'}, true);
                return;
            }
            if (this.routeName === 'manage-messenger') {
                if (this.$route.params.dialogAction === 'contacts') {
                    if (this.messengerSearchKey.contacts) {
                        this.$store.state.messengerSearchKey.contacts = ""
                        return;
                    }
                } else {
                    if (this.messengerSearchKey.dialog) {
                        this.$store.state.messengerSearchKey.dialog = ""
                        return;
                    }
                }
            }
            this.goBack();
        },

        appAndroidEvents() {
            if (this.$isEEUiApp && $A.isAndroid()) {
                $A.eeuiAppSetPageBackPressed({
                    pageName: 'firstPage',
                }, _ => {
                    if (this.canBack()) {
                        this.onBack();
                    } else {
                        $A.eeuiAppGoDesktop()
                    }
                });
            }
        }
    },
};
</script>
