<template>
    <Modal class-name="chain-reaction-wrapper"
        v-model="show"
        :mask-closable="false"
        :title="$L('发起接龙')"
        :closable="!isFullscreen"
        :fullscreen="isFullscreen"
        :footer-hide="isFullscreen">
        <!-- 顶部 -->
        <template #header>
            <div v-if="isFullscreen" class="user-modal-header">
                <div class="user-modal-close" @click="show = false">
                    {{ $L('关闭') }}
                </div>
                <div class="user-modal-title">1</div>
                <div  class="user-modal-submit" @click="showMultiple = true">
                    {{$L('多选')}}
                </div>
            </div>
        </template>
        <template #close>
            <i class="ivu-icon ivu-icon-ios-close"></i>
        </template>

        <!--  -->
        2212

        <div slot="footer">
            <Button type="default" @click="show=false">{{$L('取消')}}</Button>
            <Button type="primary" @click="onSend">{{$L('发送')}}</Button>
        </div>

        <!-- <TaskDetail ref="taskDetail" :task-id="taskId" :open-task="taskData" modalMode/> -->
    </Modal>
    <!-- <Modal
        v-model="fullInput"
        :mask-closable="false"
        :beforeClose="onFullBeforeClose"
        class-name="chat-input-full-input"
        footer-hide
        fullscreen>
        <div class="chat-input-box">
            <div class="chat-input-wrapper">
                <div ref="editorFull" class="no-dark-content"></div>
            </div>
        </div>
        <i slot="close" class="taskfont">&#xe6ab;</i>
    </Modal> -->
</template>

<script>
import {mapState} from "vuex";
export default {
    name: 'ChainReaction',
    props: {
        disabled: {
            type: Boolean,
            default: false
        },
    },

    data() {
        return {
            show: false,  // 鼠标滑过overflow文本时，再检查是否需要显示
        }
    },

    computed: {
        ...mapState(['chainReaction']),

        isFullscreen({ windowWidth }) {
            return windowWidth < 576;
        },
    },

    watch: {
        chainReaction(data) {
            if(data.type == 'create' && data.dialog_id){
                this.show = true;
            }
        }
    },

    methods: {
        onSend(e) {
            this.$emit("on-click", e)
        }
    }
}
</script>
