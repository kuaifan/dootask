<template>
    <div class="page-apply">
        <PageTitle :title="$L('应用')"/>

        <div class="apply-wrapper">
            <div class="apply-head">
                <div class="apply-nav">
                    <h1>{{ $L('应用') }}</h1>
                </div>
                <div class="apply-nav-actions">
                    <Dropdown trigger="click" placement="bottom-end" transfer @on-click="handleActionMenu">
                        <div class="apply-action-btn">
                            <Icon type="ios-more"/>
                        </div>
                        <DropdownMenu slot="list">
                            <DropdownItem v-if="!sortingMode" name="sort">{{ $L('调整排序') }}</DropdownItem>
                            <DropdownItem v-else name="cancelSort">{{ $L('退出排序') }}</DropdownItem>
                        </DropdownMenu>
                    </Dropdown>
                </div>
            </div>

            <div v-if="sortingMode" class="apply-sort-bar">
                <div class="apply-sort-tip">
                    <Icon type="md-move"/>
                    <span>{{ $L('拖动卡片调整顺序，保存后仅自己可见') }}</span>
                </div>
                <div class="apply-sort-actions">
                    <Button @click="exitSortMode">{{ $L('取消') }}</Button>
                    <Button @click="restoreDefaultSort">{{ $L('恢复默认') }}</Button>
                    <Button
                        type="primary"
                        :disabled="!sortHasChanges"
                        :loading="appSortSaving"
                        @click="submitSort">
                        {{ $L('保存') }}
                    </Button>
                </div>
            </div>

            <div class="apply-content">
                <template v-for="t in applyTypes">
                    <template v-if="t === 'base' || adminAppItems.length > 0">
                        <div
                            v-if="(t === 'base' && isExistAdminList) || t === 'admin'"
                            class="apply-row-title">
                            {{ t === 'base' ? $L('常用') : $L('管理员') }}
                        </div>
                        <Draggable
                            v-for="cards in [currentCards(t)]"
                            :key="`apps_${t}`"
                            tag="Row"
                            class="apply-sort-list"
                            :list="cards"
                            :disabled="!sortingMode"
                            :component-data="{ props: { gutter: 16 } }"
                            :options="getDraggableOptions(t)">
                            <Col
                                v-for="card in cards"
                                :key="card.sortKey"
                                class="apply-col-wrapper"
                                :xs="{ span: 6 }"
                                :sm="{ span: 6 }"
                                :lg="{ span: 6 }"
                                :xl="{ span: 6 }"
                                :xxl="{ span: 3 }">
                                <div class="apply-col">
                                    <template v-if="card.category === 'micro'">
                                        <div class="apply-item" :class="{'is-sorting': sortingMode}" @click="handleCardClick(card)">
                                            <div class="logo">
                                                <div class="apply-icon no-dark-content" :style="{backgroundImage: `url(${card.micro.icon})`}"></div>
                                            </div>
                                            <p>{{ card.micro.label }}</p>
                                        </div>
                                    </template>
                                    <template v-else>
                                        <template v-if="card.system.value === 'exportManage' && !sortingMode">
                                            <EPopover
                                                v-model="exportPopoverShow"
                                                trigger="click"
                                                placement="bottom"
                                                popperClass="apply-export-popover"
                                                :transfer="true">
                                                <div slot="reference" class="apply-item" :class="{'is-sorting': sortingMode}">
                                                    <div class="logo">
                                                        <div class="apply-icon no-dark-content" :class="getLogoClass(card.system.value)"></div>
                                                    </div>
                                                    <p>{{ $L(card.system.label) }}</p>
                                                </div>
                                                <ul class="apply-export-menu">
                                                    <li @click="handleExport('task')">{{ $L('导出任务统计') }}</li>
                                                    <li @click="handleExport('overdue')">{{ $L('导出超期任务') }}</li>
                                                    <li @click="handleExport('approve')">{{ $L('导出审批数据') }}</li>
                                                    <li @click="handleExport('checkin')">{{ $L('导出签到数据') }}</li>
                                                </ul>
                                            </EPopover>
                                        </template>
                                        <div
                                            v-else
                                            class="apply-item"
                                            :class="{'is-sorting': sortingMode}"
                                            @click="handleCardClick(card)">
                                            <div class="logo">
                                                <div class="apply-icon no-dark-content" :class="getLogoClass(card.system.value)"></div>
                                                <div
                                                    v-if="!sortingMode"
                                                    @click.stop="handleCardClick(card, 'badge')"
                                                    class="apply-box-top-report">
                                                    <Badge v-if="showBadge(card.system,'approve')" :overflow-count="999" :count="approveUnreadNumber"/>
                                                    <Badge v-if="showBadge(card.system,'report')" :overflow-count="999" :count="reportUnreadNumber"/>
                                                </div>
                                            </div>
                                            <p>{{ $L(card.system.label) }}</p>
                                        </div>
                                    </template>
                                </div>
                            </Col>
                        </Draggable>
                    </template>
                </template>
            </div>
        </div>

        <!--MY BOT-->
        <DrawerOverlay v-model="mybotShow" placement="right" :size="720">
            <template v-if="mybotShow" #title>
                {{ $L('我的机器人') }}
            </template>
            <template v-if="mybotShow" #more>
                <a href="javascript:void(0)" @click="applyClick({value: 'mybot-add'}, {id: 0})">{{ $L('添加机器人') }}</a>
            </template>
            <div v-if="mybotShow" class="ivu-modal-wrap-apply">
                <div class="ivu-modal-wrap-apply-body full-body">
                    <div v-if="mybotList.length === 0" class="empty-data">
                        <Loading v-if="mybotLoad"/>
                        <span v-else>{{ $L('您没有创建机器人') }}</span>
                    </div>
                    <ul v-else class="ivu-modal-wrap-ul">
                        <li v-for="(item, key) in mybotList" :key="key">
                            <div class="modal-item-img">
                                <img :src="item.avatar">
                            </div>
                            <div class="modal-item-info">
                                <div class="modal-item-name">
                                    <h4 class="user-select-auto">{{ item.name }}</h4>
                                </div>
                                <div class="modal-item-mybot user-select-auto">
                                    <p><span>ID:</span>{{ item.id }}</p>
                                    <p><span>{{ $L('清理时间') }}:</span>{{ item.clear_day }}</p>
                                    <p><span>Webhook:</span>{{ item.webhook_url || '-' }}</p>
                                    <p><span>{{ $L('Webhook事件') }}:</span>{{ formatWebhookEvents(item.webhook_events) }}</p>
                                </div>
                                <div class="modal-item-btns">
                                    <Button icon="md-chatbubbles" @click="applyClick({value: 'mybot-chat'}, item)">{{ $L('开始聊天') }}</Button>
                                    <Button icon="md-create" @click="applyClick({value: 'mybot-add'}, item)">{{ $L('修改') }}</Button>
                                    <Button icon="md-trash" @click="applyClick({value: 'mybot-del'}, item)">{{ $L('删除') }}</Button>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </DrawerOverlay>

        <!--MY BOT 设置-->
        <Modal
            v-model="mybotModifyShow"
            :title="$L(mybotModifyData.id > 0 ? '修改机器人' : '添加机器人')"
            :mask-closable="false">
            <Form :model="mybotModifyData" v-bind="formOptions" @submit.native.prevent>
                <Alert v-if="mybotModifyData.system_name" type="error" style="margin-bottom:18px">{{ $L(`正在修改系统机器人：${mybotModifyData.system_name}`) }}</Alert>
                <FormItem prop="avatar" :label="$L('头像')">
                    <ImgUpload v-model="mybotModifyData.avatar" :num="1" :width="512" :height="512" whcut="cover"/>
                </FormItem>
                <FormItem prop="name" :label="$L('名称')">
                    <Input v-model="mybotModifyData.name" :maxlength="20" :placeholder="$L('机器人名称')"/>
                </FormItem>
                <FormItem prop="clear_day" :label="$L('消息保留')">
                    <Input v-model="mybotModifyData.clear_day" :maxlength="3" type="number" :placeholder="$L('默认：90天')">
                        <div slot="append">{{ $L('天') }}</div>
                    </Input>
                </FormItem>
                <FormItem prop="webhook_url" label="Webhook">
                    <Input v-model="mybotModifyData.webhook_url" :maxlength="255" :show-word-limit="0.9" type="textarea" placeholder="Webhook"/>
                </FormItem>
                <FormItem prop="webhook_events" :label="$L('Webhook事件')">
                    <CheckboxGroup v-model="mybotModifyData.webhook_events">
                        <Checkbox v-for="option in webhookEventOptions" :key="option.value" :label="option.value">
                            {{ $L(option.label) }}
                        </Checkbox>
                    </CheckboxGroup>
                </FormItem>
            </Form>
            <div slot="footer" class="adaption">
                <Button type="default" @click="mybotModifyShow=false">{{ $L('取消') }}</Button>
                <Button type="primary" :loading="mybotModifyLoad > 0" @click="onMybotModify">{{ $L('保存') }}</Button>
            </div>
        </Modal>

        <!--签到-->
        <DrawerOverlay v-model="signInShow" placement="right" :size="500">
            <template v-if="signInShow" #title>
                {{ $L('签到管理') }}
            </template>
            <template v-if="signInShow" #more>
                <a href="javascript:void(0)" @click="signInSettingShow=true" v-if="userIsAdmin">{{ $L('签到设置') }}</a>
            </template>
            <div v-if="signInShow" class="ivu-modal-wrap-apply">
                <div class="ivu-modal-wrap-apply-body">
                    <Checkin/>
                </div>
            </div>
        </DrawerOverlay>

        <!--签到设置-->
        <DrawerOverlay v-model="signInSettingShow" placement="right" :size="720">
            <template v-if="signInSettingShow" #title>
                {{ $L('签到设置') }}
            </template>
            <div v-if="signInSettingShow" class="ivu-modal-wrap-apply">
                <div class="ivu-modal-wrap-apply-body">
                    <SystemCheckin/>
                </div>
            </div>
        </DrawerOverlay>

        <!--会议-->
        <DrawerOverlay v-model="meetingShow" placement="right" :size="720">
            <template v-if="meetingShow" #title>
                {{ $L('会议') }}
            </template>
            <template v-if="meetingShow" #more>
                <a href="javascript:void(0)" @click="meetingSettingShow = true" v-if="userIsAdmin">{{ $L('会议设置') }}</a>
            </template>
            <div v-if="meetingShow" class="ivu-modal-wrap-apply">
                <div class="ivu-modal-wrap-apply-body full-body">
                    <SystemMeetingNav @openDetail="openDetail" @onMeeting="onMeeting"/>
                </div>
            </div>
        </DrawerOverlay>

        <!--会议设置-->
        <DrawerOverlay v-model="meetingSettingShow" placement="right" :size="600">
            <template v-if="meetingSettingShow" #title>
                {{ $L('会议设置') }}
            </template>
            <div v-if="meetingSettingShow" class="ivu-modal-wrap-apply">
                <div class="ivu-modal-wrap-apply-body full-body">
                    <SystemMeeting/>
                </div>
            </div>
        </DrawerOverlay>

        <!--LDAP-->
        <DrawerOverlay v-model="ldapShow" placement="right" :size="700">
            <template v-if="ldapShow" #title>
                {{ $L('LDAP 设置') }}
            </template>
            <div v-if="ldapShow" class="ivu-modal-wrap-apply">
                <div class="ivu-modal-wrap-apply-body">
                    <SystemThirdAccess/>
                </div>
            </div>
        </DrawerOverlay>

        <!--邮件-->
        <DrawerOverlay v-model="mailShow" placement="right" :size="700">
            <template v-if="mailShow" #title>
                {{ $L('邮件通知') }}
            </template>
            <div v-if="mailShow" class="ivu-modal-wrap-apply">
                <div class="ivu-modal-wrap-apply-body">
                    <SystemEmailSetting/>
                </div>
            </div>
        </DrawerOverlay>

        <!--App 推送-->
        <DrawerOverlay v-model="appPushShow" placement="right" :size="700">
            <template v-if="appPushShow" #title>
                {{ $L('APP 推送') }}
            </template>
            <div v-if="appPushShow" class="ivu-modal-wrap-apply">
                <div class="ivu-modal-wrap-apply-body">
                    <SystemAppPush/>
                </div>
            </div>
        </DrawerOverlay>

        <!--扫码登录-->
        <Modal
            v-model="scanLoginShow"
            :title="$L('扫码登录')"
            :mask-closable="false">
            <div class="mobile-scan-login-box">
                <div class="mobile-scan-login-title">{{ $L(`你好，扫码确认登录`) }}</div>
                <div class="mobile-scan-login-subtitle">「{{ $L('为确保帐号安全，请确认是本人操作') }}」</div>
            </div>
            <div slot="footer" class="adaption">
                <Button type="default" @click="scanLoginShow=false">{{ $L('取消登录') }}</Button>
                <Button type="primary" :loading="scanLoginLoad" @click="scanLoginSubmit">{{ $L('确认登录') }}</Button>
            </div>
        </Modal>

        <!--发起群投票、接龙-->
        <UserSelect
            ref="wordChainAndVoteRef"
            v-model="sendData"
            :multiple-max="1"
            :title="sendType == 'vote' ? $L('选择群组发起投票') : $L('选择群组发起接龙')"
            :before-submit="goWordChainAndVote"
            :show-select-all="false"
            :only-group="true"
            show-dialog
            module/>

    </div>
</template>

<script>
import {mapGetters, mapState} from "vuex";
import DrawerOverlay from "../../components/DrawerOverlay";
import UserSelect from "../../components/UserSelect";
import SystemCheckin from "./setting/components/SystemCheckin";
import Checkin from "./setting/checkin";
import SystemMeeting from "./setting/components/SystemMeeting";
import SystemMeetingNav from "./setting/components/SystemMeetingNav.vue";
import SystemThirdAccess from "./setting/components/SystemThirdAccess";
import SystemEmailSetting from "./setting/components/SystemEmailSetting";
import SystemAppPush from "./setting/components/SystemAppPush";
import emitter from "../../store/events";
import ImgUpload from "../../components/ImgUpload.vue";
import {webhookEventOptions} from "../../utils/webhook";
import Draggable from "vuedraggable";

export default {
    components: {
        Draggable,
        ImgUpload,
        UserSelect,
        DrawerOverlay,
        SystemCheckin,
        Checkin,
        SystemMeeting,
        SystemMeetingNav,
        SystemThirdAccess,
        SystemEmailSetting,
        SystemAppPush
    },
    data() {
        return {
            applyTypes: ['base', 'admin'],
            sortingMode: false,
            sortLists: {
                base: [],
                admin: [],
            },
            sortInitialLists: {
                base: [],
                admin: [],
            },
            appSorts: {
                base: [],
                admin: [],
            },
            appSortLoaded: false,
            appSortLoading: false,
            appSortSaving: false,
            //
            mybotShow: false,
            mybotList: [],
            mybotLoad: 0,
            mybotModifyShow: false,
            mybotModifyData: {},
            mybotModifyLoad: 0,
            webhookEventOptions,
            //
            signInShow: false,
            signInSettingShow: false,
            //
            meetingShow: false,
            meetingSettingShow: false,
            //
            ldapShow: false,
            //
            mailShow: false,
            //
            appPushShow: false,
            //
            exportPopoverShow: false,
            //
            scanLoginShow: false,
            scanLoginLoad: false,
            scanLoginCode: '',
            //
            sendData: [],
            sendType: '',
        }
    },
    created() {
        this.fetchAppSorts();
    },
    activated() {
        this.$store.dispatch("updateMicroAppsStatus")
    },
    computed: {
        ...mapState([
            'systemConfig',
            'userInfo',
            'userIsAdmin',
            'reportUnreadNumber',
            'approveUnreadNumber',
            'cacheDialogs',
            'windowOrientation',
            'windowPortrait',
            'formOptions',
            'routeLoading',
            'microAppsIds'
        ]),
        ...mapGetters([
            'filterMicroAppsMenus',
            'filterMicroAppsMenusAdmin',
        ]),
        applyList() {
            const list = [
                // 常用应用
                {value: "approve", label: "审批中心", sort: 30, show: this.microAppsIds.includes('approve')},
                {value: "favorite", label: "我的收藏", sort: 45},
                {value: "recent", label: "最近打开", sort: 47},
                {value: "report", label: "工作报告", sort: 50},
                {value: "mybot", label: "我的机器人", sort: 55},
                {value: "signin", label: "签到打卡", sort: 70},
                {value: "meeting", label: "在线会议", sort: 80},
                {value: "createGroup", label: "创建群组", sort: 85},
                {value: "word-chain", label: "群接龙", sort: 90},
                {value: "vote", label: "群投票", sort: 100},
                {value: "addProject", label: "创建项目", sort: 110},
                {value: "addTask", label: "添加任务", sort: 120},
                {value: "scan", label: "扫一扫", sort: 130, show: $A.isEEUIApp},

                // 管理员应用
                {type: 'admin', value: "ldap", label: "LDAP", sort: 160, show: this.userIsAdmin},
                {type: 'admin', value: "mail", label: "邮件通知", sort: 170, show: this.userIsAdmin},
                {type: 'admin', value: "appPush", label: "APP 推送", sort: 180, show: this.userIsAdmin},
                {type: 'admin', value: "complaint", label: "举报管理", sort: 190, show: this.userIsAdmin},
                {type: 'admin', value: "exportManage", label: "数据导出", sort: 195, show: this.userIsAdmin},
                {type: 'admin', value: "allUser", label: "团队管理", sort: 200, show: this.userIsAdmin},
            ]
            // 竖屏模式
            if (this.windowPortrait) {
                list.push(...[
                    {value: "calendar", label: "日历", sort: 10},
                    {value: "file", label: "文件", sort: 20},
                    {value: "setting", label: "设置", sort: 140},
                ])
            }
            //
            return list.sort((a, b) => a.sort - b.sort);
        },
        isExistAdminList() {
            return this.adminAppItems.length > 0;
        },
        baseAppItems() {
            return this.applySavedSort(this.collectAppItems('base'), 'base');
        },
        adminAppItems() {
            return this.applySavedSort(this.collectAppItems('admin'), 'admin');
        },
        sortHasChanges() {
            if (!this.sortingMode) {
                return false;
            }
            const groups = ['base', 'admin'];
            return groups.some(group => {
                const current = (this.sortLists[group] || []).map(item => item.sortKey);
                const initial = this.sortInitialLists[group] || [];
                if (current.length !== initial.length) {
                    return true;
                }
                return current.some((key, index) => key !== initial[index]);
            });
        }
    },
    watch: {
        sortingMode(val) {
            if (val) {
                this.bootstrapSortLists();
            } else {
                this.resetSortState();
            }
        },
        baseAppItems() {
            if (this.sortingMode) {
                this.mergeSortListWithSource('base');
            }
        },
        adminAppItems() {
            if (this.sortingMode) {
                this.mergeSortListWithSource('admin');
            }
        }
    },
    methods: {
        handleActionMenu(action) {
            if (action === 'sort') {
                this.enterSortMode();
            } else if (action === 'cancelSort') {
                this.exitSortMode();
            }
        },
        currentCards(type) {
            return this.sortingMode ? (this.sortLists[type] || []) : this.getDisplayItems(type);
        },
        getDisplayItems(type) {
            return type === 'admin' ? this.adminAppItems : this.baseAppItems;
        },
        collectAppItems(group) {
            const items = [];
            const microSource = group === 'admin' ? this.filterMicroAppsMenusAdmin : this.filterMicroAppsMenus;
            microSource.forEach(menu => {
                if (!menu || menu.show === false) {
                    return;
                }
                items.push(this.createMicroCard(menu, group));
            });
            this.applyList.forEach(item => {
                if (item.show === false) {
                    return;
                }
                const isAdminItem = item.type === 'admin';
                if (group === 'admin') {
                    if (!isAdminItem) {
                        return;
                    }
                } else if (isAdminItem) {
                    return;
                }
                items.push(this.createSystemCard(item, group));
            });
            return items;
        },
        createMicroCard(menu, group) {
            const fallback = menu?.id || menu?.value || menu?.url || menu?.label || 'unknown';
            const key = menu?.name || fallback;
            return {
                sortKey: `micro:${key}`,
                category: 'micro',
                group,
                micro: menu,
            };
        },
        createSystemCard(item, group) {
            return {
                sortKey: `system:${item.value}`,
                category: 'system',
                group,
                system: item,
            };
        },
        applySavedSort(items, group) {
            const saved = this.appSorts[group] || [];
            if (!saved.length) {
                return items;
            }
            const map = {};
            items.forEach(card => {
                map[card.sortKey] = card;
            });
            const ordered = [];
            saved.forEach(key => {
                if (map[key]) {
                    ordered.push(map[key]);
                    delete map[key];
                }
            });
            items.forEach(card => {
                if (map[card.sortKey]) {
                    ordered.push(card);
                    delete map[card.sortKey];
                }
            });
            return ordered;
        },
        async enterSortMode() {
            if (this.sortingMode) {
                return;
            }
            if (!this.appSortLoaded && !this.appSortLoading) {
                await this.fetchAppSorts();
            }
            this.sortingMode = true;
        },
        exitSortMode() {
            this.sortingMode = false;
        },
        bootstrapSortLists() {
            const base = this.cloneAppItems(this.baseAppItems);
            const admin = this.cloneAppItems(this.adminAppItems);
            this.$set(this.sortLists, 'base', base);
            this.$set(this.sortLists, 'admin', admin);
            this.$set(this.sortInitialLists, 'base', base.map(item => item.sortKey));
            this.$set(this.sortInitialLists, 'admin', admin.map(item => item.sortKey));
        },
        resetSortState() {
            this.$set(this.sortLists, 'base', []);
            this.$set(this.sortLists, 'admin', []);
            this.$set(this.sortInitialLists, 'base', []);
            this.$set(this.sortInitialLists, 'admin', []);
        },
        mergeSortListWithSource(group) {
            const source = this.cloneAppItems(this.getDisplayItems(group));
            if (!source.length) {
                this.$set(this.sortLists, group, []);
                this.$set(this.sortInitialLists, group, []);
                return;
            }
            const sourceMap = new Map(source.map(item => [item.sortKey, item]));
            const next = [];
            (this.sortLists[group] || []).forEach(item => {
                if (sourceMap.has(item.sortKey)) {
                    next.push(sourceMap.get(item.sortKey));
                    sourceMap.delete(item.sortKey);
                }
            });
            sourceMap.forEach(item => next.push(item));
            this.$set(this.sortLists, group, this.cloneAppItems(next));
            const snapshot = this.sortInitialLists[group] ? [...this.sortInitialLists[group]] : [];
            next.forEach(item => {
                if (!snapshot.includes(item.sortKey)) {
                    snapshot.push(item.sortKey);
                }
            });
            this.$set(this.sortInitialLists, group, snapshot);
        },
        cloneAppItems(items = []) {
            return items.map(item => Object.assign({}, item));
        },
        getDraggableOptions(type) {
            return {
                animation: 200,
                draggable: '.apply-col-wrapper',
                group: {
                    name: `${type}-apps`,
                    pull: false,
                    put: false,
                }
            };
        },
        async fetchAppSorts() {
            if (this.appSortLoading) {
                return;
            }
            this.appSortLoading = true;
            try {
                const {data} = await this.$store.dispatch("call", {
                    url: 'users/appsort',
                    method: 'get',
                });
                this.appSorts = this.normalizeSortPayload(data?.sorts);
            } catch (error) {
                const msg = error?.msg || error?.message;
                msg && console.warn(msg);
            } finally {
                this.appSortLoading = false;
                this.appSortLoaded = true;
            }
        },
        normalizeSortPayload(raw) {
            const result = {base: [], admin: []};
            if (!raw || typeof raw !== 'object') {
                return result;
            }
            ['base', 'admin'].forEach(group => {
                const list = Array.isArray(raw[group]) ? raw[group] : [];
                result[group] = list
                    .filter(item => typeof item === 'string')
                    .map(item => item.trim())
                    .filter(item => item.length > 0);
            });
            return result;
        },
        submitSort() {
            if (!this.sortHasChanges) {
                this.exitSortMode();
                return;
            }
            const payload = this.buildSortPayload();
            this.appSortSaving = true;
            this.$store.dispatch("call", {
                url: 'users/appsort/save',
                method: 'post',
                data: {
                    sorts: payload,
                }
            }).then(({data, msg}) => {
                this.appSorts = this.normalizeSortPayload(data?.sorts || payload);
                this.exitSortMode();
                $A.messageSuccess(msg || this.$L('保存成功'));
            }).catch(({msg}) => {
                $A.modalError(msg || this.$L('保存失败'));
            }).finally(() => {
                this.appSortSaving = false;
            });
        },
        restoreDefaultSort() {
            if (!this.sortingMode) {
                return;
            }
            ['base', 'admin'].forEach(group => {
                this.$set(this.sortLists, group, this.cloneAppItems(this.collectAppItems(group)));
            });
        },
        buildSortPayload() {
            const payload = {base: [], admin: []};
            ['base', 'admin'].forEach(group => {
                const keys = (this.sortLists[group] || []).map(item => item.sortKey);
                const defaults = this.getDefaultSortKeys(group);
                payload[group] = this.arraysEqual(keys, defaults) ? [] : keys;
            });
            return payload;
        },
        getDefaultSortKeys(group) {
            return this.collectAppItems(group).map(item => item.sortKey);
        },
        arraysEqual(a = [], b = []) {
            if (a.length !== b.length) {
                return false;
            }
            return a.every((item, index) => item === b[index]);
        },
        handleCardClick(card, params = '') {
            if (this.sortingMode) {
                return;
            }
            if (!card) {
                return;
            }
            if (card.category === 'micro') {
                this.applyClick({value: 'microApp'}, card.micro);
                return;
            }
            this.applyClick(card.system, params);
        },
        normalizeWebhookEvents(events = [], useFallback = false) {
            if (!Array.isArray(events)) {
                events = events ? [events] : [];
            }
            const allowed = this.webhookEventOptions.map(item => item.value);
            const result = events.filter(item => allowed.includes(item));
            if (result.length) {
                return Array.from(new Set(result));
            }
            return [];
        },
        enhanceMybotItem(item = {}) {
            const data = $A.cloneJSON(item || {});
            let events = data.webhook_events;
            if (typeof events === 'undefined' || events === null) {
                events = [];
            }
            events = this.normalizeWebhookEvents(events, false);
            if (!events.length) {
                events = [];
            }
            data.webhook_events = events;
            return data;
        },
        formatWebhookEvents(events) {
            const values = this.normalizeWebhookEvents(events, false);
            const labels = this.webhookEventOptions
                .filter(option => values.includes(option.value))
                .map(option => this.$L(option.label));
            return labels.length ? labels.join('、') : '-';
        },
        getLogoClass(name) {
            name = name.replace(/([a-z])([A-Z])/g, '$1-$2').toLowerCase();
            return name
        },
        showBadge(item, type) {
            let num = 0;
            switch (type) {
                case 'approve':
                    num = this.approveUnreadNumber;
                    break;
                case 'report':
                    num = this.reportUnreadNumber;
                    break;
            }
            return item.value == type && num > 0
        },
        // 点击应用
        applyClick(item, params = '') {
            switch (item.value) {
                case 'calendar':
                case 'file':
                case 'setting':
                    this.goForward({name: 'manage-' + item.value});
                    break;
                case 'report':
                    emitter.emit('openReport', params == 'badge' ? 'receive' : 'my');
                    break;
                case 'favorite':
                    emitter.emit('openFavorite');
                    break;
                case 'recent':
                    emitter.emit('openRecent');
                    break;
                case 'mybot':
                    this.getMybot();
                    this.mybotShow = true;
                    break;
                case 'mybot-chat':
                    this.chatMybot(params.id);
                    break;
                case 'mybot-add':
                    this.addMybot(params);
                    break;
                case 'mybot-del':
                    this.delMybot(params);
                    break;
                case 'signin':
                    this.signInShow = true;
                    break;
                case 'meeting':
                    this.meetingShow = true;
                    break;
                case 'ldap':
                    this.ldapShow = true;
                    break;
                case 'mail':
                    this.mailShow = true;
                    break;
                case 'appPush':
                    this.appPushShow = true;
                    break;
                case 'scan':
                    $A.eeuiAppScan(this.scanResult);
                    break;
                case 'word-chain':
                case 'vote':
                    this.sendData = [];
                    this.sendType = item.value;
                    this.$refs.wordChainAndVoteRef.onSelection()
                    break;

            }
            this.$emit("on-click", item.value, params);
        },
        handleExport(type) {
            this.exportPopoverShow = false;
            emitter.emit('openManageExport', type);
        },
        // 获取我的机器人
        getMybot() {
            this.mybotLoad++
            this.$store.dispatch("call", {
                url: 'users/bot/list',
            }).then(({data}) => {
                this.mybotList = (data.list || []).map(item => this.enhanceMybotItem(item));
            }).finally(_ => {
                this.mybotLoad--
            });
        },
        // 与我的机器人聊天
        chatMybot(userid) {
            this.$store.dispatch("openDialogUserid", userid).catch(({msg}) => {
                $A.modalError(msg || this.$L('打开会话失败'))
            });
        },
        // 添加修改我的机器人
        addMybot(info) {
            this.mybotModifyData = this.enhanceMybotItem(info)
            this.mybotModifyShow = true;
        },
        // 删除我的机器人
        delMybot(info) {
            $A.modalInput({
                title: `删除机器人：${info.name}`,
                placeholder: `请输入备注原因`,
                okText: "删除",
                okType: "error",
                onOk: remark => {
                    if (!remark) {
                        return `请输入备注原因`
                    }
                    return new Promise((resolve, reject) => {
                        this.$store.dispatch("call", {
                            url: 'users/bot/delete',
                            data: {
                                id: info.id,
                                remark
                            }
                        }).then(({msg}) => {
                            const index = this.mybotList.findIndex(item => item.id === info.id);
                            if (index > -1) {
                                this.mybotList.splice(index, 1);
                            }
                            $A.messageSuccess(msg);
                            resolve();
                        }).catch(({msg}) => {
                            reject(msg);
                        });
                    })
                }
            });
        },
        // 添加/修改我的机器人
        onMybotModify() {
            this.mybotModifyLoad++
            this.$store.dispatch("editUserBot", this.mybotModifyData).then(({data, msg}) => {
                const botData = this.enhanceMybotItem(data);
                const index = this.mybotList.findIndex(item => item.id === botData.id);
                if (index > -1) {
                    this.mybotList.splice(index, 1, botData);
                } else {
                    this.mybotList.unshift(botData);
                }
                this.mybotModifyShow = false;
                this.mybotModifyData = {};
                $A.messageSuccess(msg);
            }).catch(({msg}) => {
                $A.modalError(msg);
            }).finally(_ => {
                this.mybotModifyLoad--;
            });
        },
        // 会议
        onMeeting(name) {
            switch (name) {
                case 'createMeeting':
                    emitter.emit('addMeeting', {
                        type: 'create',
                        userids: [this.userId],
                    });
                    break;
                case 'joinMeeting':
                    emitter.emit('addMeeting', {
                        type: 'join',
                    });
                    break;
            }
        },
        // 扫一扫
        scanResult(text) {
            const arr = (text + "").match(/^https?:\/\/(.*?)\/login\?qrcode=(.*?)$/)
            if (arr) {
                // 扫码登录
                if ($A.getDomain(text) != $A.getDomain($A.mainUrl())) {
                    let content = this.$L('请确认扫码的服务器与当前服务器一致')
                    content += `<br/>${this.$L('二维码服务器')}: ${$A.getDomain(text)}`
                    content += `<br/>${this.$L('当前服务器')}: ${$A.getDomain($A.mainUrl())}`
                    $A.modalWarning({
                        language: false,
                        title: this.$L('扫码登录'),
                        content
                    })
                    return
                }
                this.scanLoginCode = arr[2];
                this.scanLoginShow = true;
                return
            }
            if (/^https?:\/\//i.test(text)) {
                // 打开链接
                this.$store.dispatch('openAppChildPage', {
                    pageType: 'app',
                    pageTitle: ' ',
                    url: 'web.js',
                    params: {
                        url: text
                    },
                });
            }
        },
        // 扫描登录提交
        scanLoginSubmit() {
            if (this.scanLoginLoad === true) {
                return
            }
            this.scanLoginLoad = true
            //
            this.$store.dispatch("call", {
                url: "users/login/qrcode",
                data: {
                    type: "login",
                    code: this.scanLoginCode,
                }
            }).then(({msg}) => {
                this.scanLoginShow = false
                $A.messageSuccess(msg)
            }).catch(({msg}) => {
                $A.messageError(msg)
            }).finally(_ => {
                this.scanLoginLoad = false
            });
        },
        // 打开明细
        openDetail(desc) {
            $A.modalInfo({
                content: desc,
            });
        },
        // 前往接龙与投票
        goWordChainAndVote() {
            return new Promise((resolve, reject) => {
                if (this.sendData.length === 0) {
                    $A.messageError("请选择对话或成员");
                    reject()
                    return
                }
                const dialog_id = Number(this.sendData[0].replace('d:', ''))
                this.$store.dispatch("openDialog", dialog_id).then(async () => {
                    await new Promise(resolve => setTimeout(resolve, 300));
                    requestAnimationFrame(_ => {
                        const type = this.sendType == 'word-chain' ? 'dialogDroupWordChain' : 'dialogGroupVote'
                        this.$store.state[type] = {type: 'create', dialog_id: dialog_id}
                    })
                })
                resolve()
            })
        }
    }
}
</script>
