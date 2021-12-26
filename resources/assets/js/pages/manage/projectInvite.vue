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
export default {
    data() {
        return {
            loadIng: 0,
            joinLoad: 0,

            already: false,
            project: {},
        }
    },
    watch: {
        '$route': {
            handler(route) {
                this.code = route.query ? route.query.code : '';
                this.getData();
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
                this.loadIng--;
                this.already = data.already;
                this.project = data.project;
            }).catch(() => {
                this.loadIng--;
                this.project = {}
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
                this.joinLoad--;
                this.already = data.already;
                this.project = data.project;
                this.goProject();
            }).catch(({msg}) => {
                this.joinLoad--;
                $A.modalError(msg);
            });
        },

        goProject() {
            this.$nextTick(() => {
                this.goForward({path: '/manage/project/' + this.project.id});
            })
        }
    }
}
</script>
