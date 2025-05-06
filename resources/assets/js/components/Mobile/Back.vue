<template>
    <div class="mobile-back">
        <div v-if="isVisible && x > 20" class="back-semicircle" :style="style"></div>
    </div>
</template>

<script>
import {mapState} from "vuex";

export default {
    name: "MobileBack",

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
        ...mapState(['fileLists', 'messengerSearchKey', 'mobileTabbar']),

        style() {
            const offset = 135;
            const top = Math.max(offset, this.y) + this.windowScrollY,
                maxTop = this.windowHeight - offset;
            return {
                top: Math.min(top, maxTop) + 'px',
            }
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
                const verticalMove = Math.abs(pageY - this.touchesStart.y);
                const horizontalMove = Math.abs(pageX - this.touchesStart.x) * 1.5; // 可调整的阈值
                this.isScrolling = verticalMove > horizontalMove;
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
            if (!this.mobileTabbar) {
                return true;
            }
            if (this.$Modal.visibleList().length > 0) {
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
            return false;
        },

        onBack() {
            // 通用菜单
            this.$store.commit('menu/operation', {})

            // 移除模态框
            if (this.$Modal.removeLast()) {
                return;
            }

            // 文件浏览器
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

            // 消息页搜索
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
            if (this.$isEEUIApp && $A.isAndroid()) {
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
