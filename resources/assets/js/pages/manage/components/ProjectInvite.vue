<template>
    <div class="project-invite-warp">
        <Modal
            v-model="show"
            :title="$L('加入项目')"
            :mask-closable="false">
            <div v-if="loadIng > 0" class="invite-load">
                <Loading class="invite-load-icon"/>
            </div>
            <div v-else-if="project.id > 0" class="invite-content">
                <p slot="title" class="invite-title" v-html="transformEmojiToHtml(project.name)"></p>
                <div v-if="project.desc" class="invite-desc user-select-auto">
                    <VMPreviewNostyle :value="project.desc"/>
                </div>
                <div v-else>{{$L('暂无介绍')}}</div>
            </div>
            <div v-else>
                <p>{{$L('邀请地址不存在或已被删除！')}}</p>
            </div>
            <div slot="footer" class="adaption">
                <template v-if="already">
                    <Button v-if="project.id > 0" type="default" @click="show=false">{{$L('关闭')}}</Button>
                    <Button v-if="already" type="success" icon="md-checkmark-circle-outline" @click="goProject">{{$L('已加入')}}</Button>
                </template>
                <template v-else-if="project.id > 0">
                    <Button v-if="project.id > 0" :disabled="joinLoad > 0" type="default" @click="show=false">{{$L('取消')}}</Button>
                    <Button type="primary" :loading="joinLoad > 0" @click="joinProject">{{$L('加入项目')}}</Button>
                </template>
                <template v-else>
                    <Button type="default" @click="show=false">{{$L('关闭')}}</Button>
                </template>
            </div>
        </Modal>
    </div>
</template>

<style lang="scss" scoped>
.invite-load {
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 12px 0;
    .invite-load-icon {
        width: 24px;
        height: 24px;
    }
}
.invite-content {
    .invite-title {
        font-size: 16px;
        font-weight: bold;
        margin-bottom: 12px;
    }
    .invite-desc {
        max-width: 460px;
        max-height: 300px;
        overflow: auto;
    }
}
</style>
<script>
import emitter from "../../../store/events";
import transformEmojiToHtml from "../../../utils/emoji";
import VMPreviewNostyle from "../../../components/VMEditor/nostyle.vue";

export default {
    name: "ProjectInvite",
    components: {VMPreviewNostyle},
    data() {
        return {
            show: false,
            code: '',

            loadIng: 0,
            joinLoad: 0,

            already: false,
            project: {},
        }
    },

    mounted() {
        emitter.on('openProjectInvite', this.open)
    },

    beforeDestroy() {
        emitter.off('openProjectInvite', this.open)
    },

    methods: {
        transformEmojiToHtml,

        /**
         * 打开邀请
         */
        open(code) {
            this.code = code
            this.show = true
            this.getData()
        },

        /**
         * 获取邀请信息
         */
        getData() {
            this.loadIng++;
            this.already = false
            this.project = {}
            this.$store.dispatch("call", {
                url: 'project/invite/info',
                data: {
                    code: this.code,
                },
            }).then(({data}) => {
                this.already = data.already;
                this.project = data.project;
            }).catch(() => {
                this.project = {}
            }).finally(_ => {
                this.loadIng--;
            });
        },

        /**
         * 加入项目
         */
        joinProject() {
            this.joinLoad++;
            this.$store.dispatch("call", {
                url: 'project/invite/join',
                data: {
                    code: this.code,
                },
            }).then(({data}) => {
                this.already = data.already;
                this.project = data.project;
                this.goProject();
            }).catch(({msg}) => {
                $A.modalError(msg);
            }).finally(_ => {
                this.joinLoad--;
            });
        },

         /**
         * 跳转到项目
         */
         goProject() {
            this.show = false
            this.$nextTick(() => {
                $A.goForward({name: 'manage-project', params: {projectId: this.project.id}});
            })
        },
    }
}
</script>
