<template>
    <div class="page-invite">
        <PageTitle :title="$L('加入项目')"/>
        <div v-if="loadIng > 0" class="invite-load">
            <Loading/>
        </div>
        <div v-else class="invite-warp">
            <Card v-if="project.id > 0">
                <p slot="title">{{project.name}}</p>
                <div v-if="project.desc" class="invite-desc">{{project.desc}}</div>
                <div v-else>{{$L('暂无介绍')}}</div>
                <div class="invite-footer">
                    <Button v-if="already" type="success" icon="ios-checkmark-circle-outline" @click="goProject">{{$L('已加入')}}</Button>
                    <Button v-else type="primary" :loading="joinLoad > 0" @click="joinProject">{{$L('加入项目')}}</Button>
                </div>
            </Card>
            <Card v-else>
                <p>{{$L('邀请地址不存在或已被删除！')}}</p>
            </Card>
        </div>
    </div>
</template>

<style lang="scss" scoped>
.page-invite {
    display: flex;
    align-items: center;
    justify-content: center;
    .invite-warp {
        .invite-desc {
            max-width: 460px;
            max-height: 300px;
            overflow: auto;
        }
        .invite-footer {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 24px;
            > button {
                height: 36px;
                min-width: 120px;
            }
        }
    }
}
</style>
<script>
import {mapState} from "vuex";
export default {
    data() {
        return {
            loadIng: 0,
            joinLoad: 0,

            already: false,
            project: {},
        }
    },
    computed: {
        ...mapState(['dialogId','windowPortrait']),
    },
    watch: {
        '$route': {
            handler(route) {
                if(route.name == 'manage-project-invite'){
                    // 唤醒app
                    if (!$A.Electron && !$A.isEEUiApp && navigator.userAgent.indexOf("MicroMessenger") === -1){
                        if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
                            try {
                                if(/Android/i.test(navigator.userAgent)){
                                    window.open("dootask://" + route.fullPath)
                                }else{
                                    window.location.href = "dootask://" + route.fullPath
                                }
                            } catch (error) {}
                        }
                    }
                    // 关闭聊天
                    if (this.windowPortrait && this.dialogId > 0){
                        this.$store.dispatch("openDialog", 0)
                    }
                    //
                    this.code = route.query ? route.query.code : '';
                    this.getData();
                }
            },
            immediate: true
        },
    },
    methods: {
        getData() {
            this.loadIng++;
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

        goProject() {
            this.$nextTick(() => {
                $A.goForward({name: 'manage-project', params: {projectId: this.project.id}});
            })
        }
    }
}
</script>
