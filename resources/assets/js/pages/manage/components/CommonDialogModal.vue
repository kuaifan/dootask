<template>
    <Modal v-model="visibleProxy" :title="$L('共同群组') + ' (' + $L('(*)个', totalCount) + ')'" :footer-hide="true" width="500">
        <div class="common-dialog-content">
            <div v-if="loading > 0 && list.length === 0" class="loading-wrapper">
                <Loading/>
            </div>
            <div v-else-if="list.length === 0" class="empty-wrapper">
                <div class="empty-content">
                    <Icon type="ios-people-outline" size="48"/>
                    <p>{{$L('暂无共同群组')}}</p>
                </div>
            </div>
            <div v-else class="dialog-list">
                <div
                    v-for="dialog in list"
                    :key="dialog.id"
                    class="dialog-item"
                    @click="onEnterDialog(dialog)">
                    <div class="dialog-avatar">
                        <EAvatar v-if="dialog.avatar" :src="dialog.avatar" :size="42"></EAvatar>
                        <i v-else-if="dialog.group_type=='department'" class="taskfont icon-avatar department">&#xe75c;</i>
                        <i v-else-if="dialog.group_type=='project'" class="taskfont icon-avatar project">&#xe6f9;</i>
                        <i v-else-if="dialog.group_type=='task'" class="taskfont icon-avatar task">&#xe6f4;</i>
                        <i v-else-if="dialog.group_type=='okr'" class="taskfont icon-avatar task">&#xe6f4;</i>
                        <Icon v-else class="icon-avatar" type="ios-people" />
                    </div>
                    <div class="dialog-info">
                        <div class="dialog-name" v-html="transformEmojiToHtml(dialog.name)"></div>
                        <div class="dialog-meta">
                            <span class="member-count">{{$L('(*)人', dialog.people || 0)}}</span>
                            <span v-if="dialog.last_at" class="last-time">{{$A.timeFormat(dialog.last_at)}}</span>
                        </div>
                    </div>
                    <Icon class="enter-icon" type="ios-arrow-forward" />
                </div>
                <div v-if="hasMore" class="load-more-wrapper">
                    <Button type="primary" @click="loadList(true)" :loading="loading > 0">{{$L('加载更多')}}</Button>
                </div>
            </div>
        </div>
    </Modal>
</template>

<script>
import transformEmojiToHtml from "../../../utils/emoji";

export default {
    name: 'CommonDialogModal',

    props: {
        value: { // v-model
            type: Boolean,
            default: false,
        },
        targetUserId: {
            type: [Number, String],
            required: true,
        },
        totalCount: {
            type: [Number, String],
            default: 0,
        },
    },

    data() {
        return {
            list: [],
            page: 1,
            hasMore: false,
            loading: 0,
        }
    },

    computed: {
        visibleProxy: {
            get() { return this.value; },
            set(v) { this.$emit('input', v); }
        }
    },

    watch: {
        visibleProxy(val) {
            if (val && this.list.length === 0) {
                this.loadList(false);
            }
        },
        targetUserId() {
            // reset when user changes
            this.list = [];
            this.page = 1;
            this.hasMore = false;
        }
    },

    methods: {
        transformEmojiToHtml,

        loadList(loadMore = false) {
            if (!this.targetUserId) return;
            this.loading++;
            const page = loadMore ? this.page + 1 : 1;
            this.$store.dispatch('call', {
                url: 'dialog/common/list',
                data: {
                    target_userid: this.targetUserId,
                    page
                }
            }).then(({data}) => {
                const newList = loadMore ? [...this.list, ...data.data] : data.data;
                this.list = Array.isArray(newList) ? newList : [];
                this.page = data.current_page || page;
                this.hasMore = !!data.next_page_url;
            }).catch(({msg}) => {
                $A.modalError(msg || this.$L('加载失败'));
            }).finally(() => {
                this.loading--;
            });
        },

        onEnterDialog(dialog) {
            this.$emit('open-chat', dialog);
        }
    }
}
</script>

<style scoped>
/* 组件自身不引入额外样式，复用全局样式类名 */
</style>


