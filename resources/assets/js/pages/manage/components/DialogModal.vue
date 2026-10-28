<template>
    <Modal
        ref="modal"
        :value="show"
        :styles="modalStyles"
        :mask-closable="false"
        :footer-hide="true"
        :mask="!windowPortrait"
        :fullscreen="windowPortrait"
        :transition-names="transitionNames"
        :class-name="className"
        :beforeClose="onBeforeClose">
        <DialogWrapper
            v-if="show && contentReady"
            ref="dialogWrapper"
            :dialogId="dialogId"
            :style="dialogStyles"
            :beforeBack="onBeforeClose"
            location="modal"/>
        <!--内容挂载前的占位（导航结构与 DialogWrapper 一致 + 消息骨架），避免划入时整页空白-->
        <div v-else-if="show" class="dialog-wrapper" :style="dialogStyles">
            <div class="dialog-nav">
                <div class="nav-wrapper" :class="{completed: $A.dialogCompleted(dialogData)}">
                    <div class="dialog-back" @click="onBeforeClose">
                        <i class="taskfont">&#xe676;</i>
                    </div>
                    <div class="dialog-block">
                        <div class="dialog-avatar">
                            <template v-if="dialogData.type=='group'">
                                <EAvatar v-if="dialogData.avatar" class="img-avatar" :src="dialogData.avatar" :size="42"></EAvatar>
                                <i v-else-if="dialogData.group_type=='department'" class="taskfont icon-avatar department">&#xe75c;</i>
                                <i v-else-if="dialogData.group_type=='project'" class="taskfont icon-avatar project">&#xe6f9;</i>
                                <i v-else-if="dialogData.group_type=='task'" class="taskfont icon-avatar task">&#xe6f4;</i>
                                <i v-else-if="dialogData.group_type=='okr'" class="taskfont icon-avatar task">&#xe6f4;</i>
                                <Icon v-else class="icon-avatar" type="ios-people" />
                            </template>
                            <div v-else-if="dialogData.dialog_user" class="user-avatar">
                                <UserAvatarTip :online="dialogData.online_state" :userid="dialogData.dialog_user.userid" :size="42"/>
                            </div>
                            <Icon v-else class="icon-avatar" type="md-person" />
                        </div>
                        <div class="dialog-title">
                            <div class="main-title">
                                <Tag v-for="(tag, index) in dialogTags" :key="index" :color="tag.color" :fade="false">{{$L(tag.text)}}</Tag>
                                <h2 v-html="transformEmojiToHtml(dialogData.name)"></h2>
                                <em v-if="peopleNum > 0">({{peopleNum}})</em>
                                <Tag v-if="dialogData.bot" class="after" :fade="false">{{$L('机器人')}}</Tag>
                                <Tag v-if="dialogData.group_type=='all'" class="after" :fade="false">{{$L('全员')}}</Tag>
                                <Tag v-else-if="dialogData.group_type=='department'" class="after" :fade="false">{{$L('部门')}}</Tag>
                            </div>
                            <ul class="title-desc">
                                <li v-if="dialogData.type === 'user'" :class="[dialogData.online_state === true ? 'online' : 'offline']">
                                    {{$L(dialogData.online_state === true ? '在线' : dialogData.online_state)}}
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="dialog-menu">
                        <i class="taskfont dialog-menu-icon">&#xe6e9;</i>
                    </div>
                </div>
            </div>
            <div class="dialog-skeleton">
                <div v-for="(item, index) in skeletonList" :key="index" class="skeleton-item" :class="{self: item.self}">
                    <div class="skeleton-avatar"></div>
                    <div class="skeleton-bubble" :style="{width: item.width, height: item.height}"></div>
                </div>
            </div>
            <div class="dialog-skeleton-footer">
                <div class="skeleton-input"></div>
            </div>
        </div>
    </Modal>
</template>

<script>
import {mapState} from "vuex";
import DialogWrapper from "./DialogWrapper";
import UserAvatarTip from "../../../components/UserAvatar/tip.vue";
import transformEmojiToHtml from "../../../utils/emoji";
import emitter from "../../../store/events";

export default {
    name: "DialogModal",
    components: {DialogWrapper, UserAvatarTip},

    data() {
        return {
            show: false,
            timer: null,
            closIng: false,
            contentReady: false,
            skeletonList: [
                {self: true, width: '50%', height: '62px'},
                {self: false, width: '36%', height: '40px'},
                {self: false, width: '58%', height: '84px'},
                {self: true, width: '42%', height: '40px'},
                {self: false, width: '52%', height: '40px'},
                {self: true, width: '44%', height: '40px'},
                {self: false, width: '66%', height: '84px'},
                {self: false, width: '40%', height: '40px'},
                {self: true, width: '60%', height: '62px'},
                {self: false, width: '48%', height: '40px'},
                {self: false, width: '64%', height: '62px'},
                {self: true, width: '38%', height: '40px'},
                {self: false, width: '56%', height: '40px'},
                {self: true, width: '68%', height: '84px'},
                {self: false, width: '42%', height: '40px'},
            ],
        }
    },

    mounted() {
        emitter.on('handleMoveTop', this.handleMoveTop);
    },

    beforeDestroy() {
        emitter.off('handleMoveTop', this.handleMoveTop);
    },

    computed: {
        ...mapState(['dialogId', 'windowOrientation', 'cacheDialogs']),

        dialogData() {
            return this.cacheDialogs.find(({id}) => id == this.dialogId) || {}
        },

        dialogTags() {
            return $A.dialogTags(this.dialogData).filter(tag => tag.color != 'success')
        },

        peopleNum() {
            return this.dialogData.type === 'group' ? $A.runNum(this.dialogData.people_user) : 0
        },

        modalStyles() {
            if (this.windowPortrait) {
                return {}
            }
            return {
                width: '90%',
                maxWidth: '720px'
            }
        },

        dialogStyles() {
            if (this.windowPortrait) {
                return {}
            }
            const height = Math.min(1100, this.windowHeight)
            const factor = height > 900 ? 200 : 70;
            return {
                height: '600px',
                maxHeight: (height - factor - 30) + 'px'
            }
        },

        transitionNames() {
            return this.windowPortrait ? ['mobile-dialog', ''] : ['ease', 'fade']
        },

        className() {
            const cls = ['dialog-modal', `dialog-${this.windowOrientation}`]
            if (this.closIng > 0) {
                cls.push('dialog-closing')
            }
            return cls.join(' ')
        }
    },

    watch: {
        dialogId() {
            this.handleShow()
        },

        windowPortrait() {
            this.handleShow()
        },

        show(v) {
            this.$store.state.dialogModalShow = v;
            $A.eeuiAppSetScrollDisabled(v && this.windowPortrait)
            if (v) {
                // 会话内容渲染较重，等打开动画开始（交由合成线程执行）后再挂载，避免动画首帧被阻塞
                this.contentReady = false
                requestAnimationFrame(_ => requestAnimationFrame(_ => {
                    this.contentReady = this.show
                }))
            }
        }
    },

    methods: {
        transformEmojiToHtml,

        onBeforeClose() {
            if (this.$refs.dialogWrapper) {
                this.$refs.dialogWrapper.operateVisible = false
            }
            return new Promise(async _ => {
                this.closIng++
                // 先关闭窗口让关闭动画开始（交由合成线程执行），再清除会话ID，避免消息列表等重渲染阻塞动画首帧
                this.show = false
                await new Promise(resolve => requestAnimationFrame(_ => requestAnimationFrame(resolve)))
                await this.$store.dispatch("openDialog", 0)
                await new Promise(resolve => setTimeout(resolve, 300))
                this.closIng--
            })
        },
        handleShow() {
            this.show = this.dialogId > 0 && (this.windowPortrait || this.routeName !== 'manage-messenger')
        },
        handleMoveTop(type) {
            type === 'dialogModal' && this.$refs.modal?.handleMoveTop();
        }
    }
}
</script>
