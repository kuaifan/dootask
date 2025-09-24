<template>
    <div class="dialog-session-history">
        <div class="session-history-title">{{$L('与 (*) 会话历史', sessionData.name)}}</div>
        <Scrollbar
            ref="list"
            class="session-history-list"
            @on-scroll="listScroll">
            <ul>
                <li v-for="(item, index) in listData" :key="index" @click="onOpen(item)">
                    <div class="history-title">
                        <div v-if="openIng == item.id" class="history-load"><Loading/></div><em v-if="item.is_open">{{$L('当前')}}</em>{{item.title || $L('新会话')}}
                    </div>
                    <div class="history-meta">
                        <div v-if="renameIng === item.id" class="history-rename-load"><Loading/></div>
                        <Icon
                            v-else
                            class="history-rename"
                            type="ios-create-outline"
                            :title="$L('重命名')"
                            @click.stop="onRename(item)"/>
                        <div class="history-time" :title="item.created_at">
                            {{$A.timeFormat(item.created_at)}}
                        </div>
                    </div>
                </li>
            </ul>
            <div v-if="listLoad > 0" class="session-history-load">
                <Loading/>
            </div>
        </Scrollbar>
    </div>
</template>

<script>
export default {
    name: "DialogSessionHistory",
    props: {
        sessionData: {
            type: Object,
            default: () => {
                return {};
            }
        },
    },

    data() {
        return {
            openIng: 0,

            listData: [],
            listLoad: 0,
            listCurrentPage: 1,
            listHasMorePages: false,
            renameIng: 0,
        }
    },

    mounted() {
        this.getListData(1)
    },

    methods: {
        scrollE() {
            if (!this.$refs.list) {
                return 0
            }
            const scrollInfo = this.$refs.list.scrollInfo()
            return scrollInfo.scrollE
        },

        getListData(page) {
            this.listLoad++;
            this.$store.dispatch("call", {
                url: "dialog/session/list",
                data: {
                    dialog_id: this.sessionData.dialog_id,
                    page: page,
                    pagesize: 50
                }
            }).then(({data}) => {
                if (data.current_page === 1) {
                    this.listData = data.data
                } else {
                    this.listData = this.listData.concat(data.data)
                }
                this.listCurrentPage = data.current_page;
                this.listHasMorePages = data.current_page < data.last_page;
                this.$nextTick(this.getListNextPage);
            }).catch(({msg}) => {
                $A.modalError(msg)
            }).finally(_ => {
                this.listLoad--;
            });
        },

        listScroll() {
            if (this.scrollE() < 10) {
                this.getListNextPage()
            }
        },

        getListNextPage() {
            if (this.scrollE() < 10
                && this.listLoad === 0
                && this.listHasMorePages) {
                this.getListData(this.listCurrentPage + 1);
            }
        },

        onOpen(item) {
            if (item.is_open) {
                this.$emit("on-close")
                return
            }
            //
            if (this.openIng > 0) {
                return
            }
            this.openIng = item.id
            //
            this.$store.dispatch("call", {
                url: "dialog/session/open",
                data: {
                    session_id: item.id,
                }
            }).then(() => {
                this.$emit("on-submit")
            }).catch(({msg}) => {
                $A.modalError(msg)
            }).finally(_ => {
                this.openIng = 0;
            });
        },

        onRename(item) {
            if (this.renameIng > 0) {
                return
            }
            const placeholder = this.$L('请输入会话名称')
            $A.modalInput({
                title: this.$L('重命名会话'),
                placeholder,
                value: item.title,
                onOk: (value) => {
                    const name = (value || '').trim()
                    if (!name) {
                        return placeholder
                    }
                    if (name === (item.title || '')) {
                        return false
                    }
                    return this.renameSession(item, name)
                }
            })
        },

        renameSession(item, name) {
            this.renameIng = item.id
            return new Promise((resolve, reject) => {
                this.$store.dispatch("call", {
                    url: "dialog/session/rename",
                    method: 'post',
                    data: {
                        session_id: item.id,
                        title: name,
                    }
                }).then(({data, msg}) => {
                    this.$set(item, 'title', data.title)
                    if (typeof data.updated_at !== 'undefined') {
                        this.$set(item, 'updated_at', data.updated_at)
                    }
                    resolve(msg)
                }).catch(({msg}) => {
                    reject(msg)
                }).finally(() => {
                    this.renameIng = 0
                })
            })
        }
    }
}
</script>
