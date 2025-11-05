<template>
    <ModalAlive
        v-model="showModal"
        class-name="common-user-detail-modal"
        :fullscreen="isFullscreen"
        :mask-closable="false"
        :footer-hide="true"
        width="420"
    >
        <div class="user-detail-body">
            <div class="profile-header">
                <div class="cover-photo"></div>
                <div class="profile-avatar">
                    <UserAvatar
                        :userid="userData.userid"
                        :size="80"
                        :show-state-dot="false"
                        @on-click="onOpenAvatar"
                    />
                </div>
            </div>
            <div class="profile-content">
                <div class="user-info-top">
                    <span class="username"
                        >@{{ userData.profession || "管理员" }}</span
                    >
                    <h1 class="fullname">
                        {{ userData.nickname}}
                    </h1>
                    <div class="meta">
                        <!-- <span>{{userData.address || 'Bandung'}}</span> -->
                        <span @click="commonDialogShow = true"
                            class="common-dialog"
                            >{{ $L("共同群组") }}:
                            {{ $L("(*)个", commonDialog.total) }}</span
                        >
                        <span class="separator">|</span>
                        <span
                            >{{ $L("最后在线") }}:
                            {{
                                $A.newDateString(
                                    userData.line_at,
                                    "YYYY-MM-DD HH:mm"
                                ) || "-"
                            }}</span
                        >
                    </div>
                </div>

                <div class="profile-actions">
                    <Button
                        icon="md-chatbubbles"
                        @click="onOpenDialog"
                        >{{ $L("开始聊天") }}</Button
                    >
                    <Button
                        icon="md-people"
                        @click="onCreateGroup"
                        >{{ $L("创建群组") }}</Button
                    >
                </div>

                <div class="profile-bio">
                    <p>{{ userData.introduction }}</p>
                </div>

                <div class="profile-information">
                    <h2>{{ $L("个人信息") }}</h2>
                    <ul>
                        <li>
                            <Icon type="ios-person-outline" />
                            <span class="label">{{ $L("部门") }}</span>
                            <span class="value">{{
                                userData.department_name || "-"
                            }}</span>
                        </li>
                        <li>
                            <Icon type="ios-mail-outline" />
                            <span class="label">{{ $L("邮箱") }}</span>
                            <span class="value">{{
                                userData.email || "-"
                            }}</span>
                        </li>
                        <li>
                            <Icon type="ios-call-outline" />
                            <span class="label">{{ $L("电话") }}</span>
                            <span class="value">{{ userData.tel || "-" }}</span>
                        </li>
                        <li>
                            <Icon type="ios-calendar-outline" />
                            <span class="label">{{ $L("生日") }}</span>
                            <span class="value">{{
                                userData.birthday || "-"
                            }}</span>
                        </li>
                    </ul>

                    <div class="profile-tags" @click.capture="onOpenTagsModal">
                        <div v-if="displayTags.length" class="tags-list">
                            <Button
                                type="dashed"
                                class="manage-tags-btn icon"
                                @click.stop="onOpenTagsModal"
                            >
                                <Icon type="ios-settings-outline" /> 管理
                            </Button>
                            <Button
                                v-for="tag in displayTags"
                                :key="tag.id"
                                :type="tag.recognized ? 'primary' : 'default'"
                            >
                                {{ tag.name }}
                            </Button>
                        </div>
                        <div v-else class="tags-empty">
                            <Button
                                type="dashed"
                                size="small"
                                icon="md-add"
                                class="add-tag-btn"
                                @click.stop="onOpenTagsModal"
                                >{{ $L("添加标签") }}</Button
                            >
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <UserTagsModal
            v-if="userData.userid"
            v-model="tagModalVisible"
            :userid="userData.userid"
            @updated="onTagsUpdated"
        />

        <CommonDialogModal
            v-model="commonDialogShow"
            :target-user-id="userData.userid"
            :total-count="commonDialog.total || 0"
            @open-chat="onOpenCommonDialogChat"
        />
    </ModalAlive>
</template>

<script>
import emitter from "../../../store/events";
import { mapState } from "vuex";
import transformEmojiToHtml from "../../../utils/emoji";
import UserTagsModal from "./UserTagsModal.vue";
import CommonDialogModal from "./CommonDialogModal.vue";

export default {
    name: "UserDetail",

    components: { UserTagsModal, CommonDialogModal },

    data() {
        return {
            userData: {
                userid: 0,
            },
            showModal: false,
            tagModalVisible: false,
            commonDialog: {
                userid: null,
                total: null,
                list: [],
                page: 1,
                has_more: false,
            },
            commonDialogShow: false,
            commonDialogLoading: 0,
        };
    },

    mounted() {
        emitter.on("openUser", this.onShow);
    },

    beforeDestroy() {
        emitter.off("openUser", this.onShow);
    },

    watch: {
        ...mapState(["cacheUserBasic"]),

        commonDialogShow() {
            if (!this.commonDialogShow || this.commonDialog.list.length > 0) {
                return;
            }
            this.loadCommonDialogList(false);
        },
    },

    computed: {
        isFullscreen({ windowWidth }) {
            return windowWidth < 576;
        },

        displayTags() {
            return Array.isArray(this.userData.personal_tags)
                ? this.userData.personal_tags
                : [];
        },

        personalTagTotal() {
            if (typeof this.userData.personal_tags_total === "number") {
                return this.userData.personal_tags_total;
            }
            return this.displayTags.length;
        },

        commonDialogList() {
            return this.commonDialog.list || [];
        },
    },

    methods: {
        transformEmojiToHtml,
        onShow(userid) {
            if (!/^\d+$/.test(userid)) {
                return;
            }
            this.$store.dispatch("showSpinner", 600);
            this.$store
                .dispatch("getUserData", userid)
                .then((user) => {
                    this.userData = user;
                    this.ensureTagDefaults();
                    this.showModal = true;
                    this.loadCommonDialogCount();
                })
                .finally((_) => {
                    this.$store.dispatch("hiddenSpinner");
                });
        },

        onHide() {
            this.showModal = false;
            this.tagModalVisible = false;
            this.commonDialogShow = false;
        },

        onOpenAvatar() {
            this.$store.dispatch("previewImage", this.userData.userimg);
        },

        onOpenDialog() {
            this.$store
                .dispatch("openDialogUserid", this.userData.userid)
                .then((_) => {
                    this.onHide();
                })
                .catch(({ msg }) => {
                    $A.modalError(msg);
                });
        },

        onCreateGroup() {
            const userids = [this.$store.state.userId];
            if (this.userData.userid && this.$store.state.userId != this.userData.userid) {
                userids.push(this.userData.userid);
            }
            emitter.emit('createGroup', userids);
            this.onHide();
        },

        ensureTagDefaults() {
            if (!Array.isArray(this.userData.personal_tags)) {
                this.$set(this.userData, "personal_tags", []);
            }
            if (typeof this.userData.personal_tags_total !== "number") {
                this.$set(
                    this.userData,
                    "personal_tags_total",
                    this.userData.personal_tags.length
                );
            }
        },

        onOpenTagsModal() {
            if (!this.userData.userid) {
                return;
            }
            this.tagModalVisible = true;
        },

        onTagsUpdated({ top, total }) {
            this.$set(
                this.userData,
                "personal_tags",
                Array.isArray(top) ? top : []
            );
            this.$set(
                this.userData,
                "personal_tags_total",
                typeof total === "number"
                    ? total
                    : this.userData.personal_tags.length
            );
        },

        loadCommonDialogCount() {
            const target_userid = this.userData.userid;
            const previousUserId = this.commonDialog.userid;
            if (!target_userid) {
                this.commonDialog = {
                    ...this.commonDialog,
                    userid: target_userid || null,
                    total: null,
                    list: [],
                    page: 1,
                    has_more: false,
                };
                return;
            }

            if (previousUserId !== target_userid) {
                this.commonDialog = {
                    ...this.commonDialog,
                    userid: target_userid,
                    total: null,
                    list: [],
                    page: 1,
                    has_more: false,
                };
            }

            const cacheMap = this.$store.state.dialogCommonCountCache || {};
            const cached = cacheMap[String(target_userid)];
            if (cached && typeof cached.total !== "undefined") {
                this.commonDialog = {
                    ...this.commonDialog,
                    total: cached.total,
                };
            }

            this.$store
                .dispatch("call", {
                    url: "dialog/common/list",
                    data: {
                        target_userid,
                        only_count: "yes",
                    },
                })
                .then(({ data }) => {
                    if (target_userid !== this.userData.userid) {
                        return;
                    }
                    const parsedTotal = Number(data.total);
                    const total = Number.isNaN(parsedTotal) ? 0 : parsedTotal;
                    this.commonDialog = {
                        ...this.commonDialog,
                        userid: target_userid,
                        total,
                        list: [],
                        page: 1,
                        has_more: false,
                    };
                    this.$store.commit("common/dialog/count/save", {
                        userid: target_userid,
                        total,
                    });
                });
        },

        loadCommonDialogList(loadMore = false) {
            this.commonDialogLoading++;
            const target_userid = this.userData.userid;
            this.$store
                .dispatch("call", {
                    url: "dialog/common/list",
                    data: {
                        target_userid,
                        page: loadMore ? this.commonDialog.page + 1 : 1,
                    },
                })
                .then(({ data }) => {
                    if (target_userid !== this.userData.userid) {
                        return;
                    }
                    this.commonDialog = {
                        ...this.commonDialog,
                        list: loadMore
                            ? [...this.commonDialog.list, ...data.data]
                            : data.data,
                        total: data.total,
                        page: data.current_page,
                        has_more: !!data.next_page_url,
                    };
                })
                .catch(({ msg }) => {
                    $A.modalError(msg || this.$L("加载失败"));
                })
                .finally(() => {
                    this.commonDialogLoading--;
                });
        },

        onOpenCommonDialogChat(dialog) {
            this.$store
                .dispatch("openDialog", dialog.id)
                .then(() => {
                    this.onHide();
                })
                .catch(({ msg }) => {
                    $A.modalError(msg);
                });
        },
    },
};
</script>

<style lang="scss" scoped>
// The styles will be moved to the SCSS file as requested.
// This scoped style block can be removed if not needed for specific overrides.
</style>
