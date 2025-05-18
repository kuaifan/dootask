<template>
    <div class="page-apply">

        <PageTitle :title="$L('应用')"/>

        <div class="apply-wrapper">
            <div class="apply-head">
                <div class="apply-nav">
                    <h1>{{ $L('应用') }}</h1>
                </div>
            </div>
            <div class="apply-content">
                <template v-for="t in applyTypes">
                    <div v-if="isExistAdminList" class="apply-row-title">
                        {{ t == 'base' ? $L('常用') : $L('管理员') }}
                    </div>
                    <Row :gutter="16">
                        <Col
                            v-if="t == 'base'"
                            :xs="{ span: 6 }"
                            :sm="{ span: 6 }"
                            :lg="{ span: 6 }"
                            :xl="{ span: 6 }"
                            :xxl="{ span: 3 }">
                            <div class="apply-col">
                                <div @click="applyClick({value: 'appstore'})">
                                    <div class="logo">
                                        <div class="apply-icon no-dark-content" :class="getLogoClass('appstore')"></div>
                                    </div>
                                    <p>{{ $L('应用商店') }}</p>
                                </div>
                            </div>
                        </Col>
                        <Col
                            v-for="(item, key) in (t == 'base' ? filterMicroAppsMenus : filterMicroAppsMenusAdmin)"
                            :key="`micro_` + key"
                            :xs="{ span: 6 }"
                            :sm="{ span: 6 }"
                            :lg="{ span: 6 }"
                            :xl="{ span: 6 }"
                            :xxl="{ span: 3 }">
                            <div class="apply-col">
                                <div @click="applyClick({value: 'microApp'}, item)">
                                    <div class="logo">
                                        <div class="apply-icon no-dark-content" :style="{backgroundImage: `url(${item.icon})`}"></div>
                                    </div>
                                    <p>{{ item.label }}</p>
                                </div>
                            </div>
                        </Col>
                        <Col
                            v-for="(item, key) in applyList"
                            :key="key"
                            v-if="((t=='base' && !item.type) || item.type == t) && item.show !== false"
                            :xs="{ span: 6 }"
                            :sm="{ span: 6 }"
                            :lg="{ span: 6 }"
                            :xl="{ span: 6 }"
                            :xxl="{ span: 3 }">
                            <div class="apply-col">
                                <div @click="applyClick(item)">
                                    <div class="logo">
                                        <div class="apply-icon no-dark-content" :class="getLogoClass(item.value)"></div>
                                        <div @click.stop="applyClick(item, 'badge')" class="apply-box-top-report">
                                            <Badge v-if="showBadge(item,'approve')" :overflow-count="999" :count="approveUnreadNumber"/>
                                            <Badge v-if="showBadge(item,'report')" :overflow-count="999" :count="reportUnreadNumber"/>
                                        </div>
                                    </div>
                                    <p>{{ $L(item.label) }}</p>
                                </div>
                            </div>
                        </Col>
                    </Row>
                </template>
            </div>
        </div>

        <!--MY BOT-->
        <DrawerOverlay v-model="mybotShow" placement="right" :size="720">
            <div v-if="mybotShow" class="ivu-modal-wrap-apply">
                <div class="ivu-modal-wrap-apply-title">
                    {{ $L('我的机器人') }}
                    <p @click="applyClick({value: 'mybot-add'}, {id: 0})">{{ $L('添加机器人') }}</p>
                </div>
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
            </Form>
            <div slot="footer" class="adaption">
                <Button type="default" @click="mybotModifyShow=false">{{ $L('取消') }}</Button>
                <Button type="primary" :loading="mybotModifyLoad > 0" @click="onMybotModify">{{ $L('保存') }}</Button>
            </div>
        </Modal>

        <!--AI BOT-->
        <DrawerOverlay v-model="aibotShow" placement="right" :size="720">
            <div v-if="aibotShow" class="ivu-modal-wrap-apply">
                <div class="ivu-modal-wrap-apply-title">
                    {{ $L('AI 列表') }}
                    <p @click="applyClick({value: 'robot-setting'}, 'openai')" v-if="userIsAdmin">{{ $L('机器人设置') }}</p>
                </div>
                <div class="ivu-modal-wrap-apply-body full-body">
                    <ul class="ivu-modal-wrap-ul">
                        <li v-for="(item, key) in aibotList" :key="key">
                            <div class="modal-item-img">
                                <img :src="item.src">
                            </div>
                            <div class="modal-item-info">
                                <div class="modal-item-name">
                                    <h4>{{ item.label }}</h4>
                                    <div v-if="item.tag" class="modal-item-tag" @click="applyClick({value: 'robot-setting'}, item.value)">
                                        {{ item.tag }}
                                        <em v-if="item.tags.length > 1">+{{ item.tags.length - 1 }}</em>
                                    </div>
                                </div>
                                <p class="modal-item-desc" @click="openDetail(item.desc)">{{ item.desc }}</p>
                                <div class="modal-item-btns">
                                    <Button icon="md-chatbubbles" :loading="aibotDialogSearchLoad == item.value" @click="onGoToChat(item.value)">{{ $L('开始聊天') }}</Button>
                                    <Button v-if="userIsAdmin" icon="md-settings" @click="applyClick({value: 'robot-setting'}, item.value)">{{ $L('设置') }}</Button>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </DrawerOverlay>

        <!--AI BOT 设置-->
        <DrawerOverlay v-model="aibotSettingShow" placement="right" :size="950">
            <div v-if="aibotSettingShow" class="ivu-modal-wrap-apply">
                <div class="ivu-modal-wrap-apply-title">
                    {{ $L('AI 设置') }}
                    <p @click="aibotSettingShow=false">{{ $L('返回') }}</p>
                </div>
                <div class="ivu-modal-wrap-apply-body">
                    <Tabs v-model="aibotTabAction" :animated="false" class="ai-tabs">
                        <TabPane v-for="(item, key) in aibotList" :key="key" :label="item.label" :name="item.value">
                            <div class="aibot-setting">
                                <SystemAibot
                                    v-if="aibotTabAction == item.value"
                                    :type="item.value"
                                    @on-update-setting="handleAITags"/>
                            </div>
                        </TabPane>
                    </Tabs>
                </div>
            </div>
        </DrawerOverlay>

        <!--签到-->
        <DrawerOverlay v-model="signInShow" placement="right" :size="500">
            <div v-if="signInShow" class="ivu-modal-wrap-apply">
                <div class="ivu-modal-wrap-apply-title">
                    {{ $L('签到管理') }}
                    <p @click="signInSettingShow=true" v-if="userIsAdmin">{{ $L('签到设置') }}</p>
                </div>
                <div class="ivu-modal-wrap-apply-body">
                    <Checkin/>
                </div>
            </div>
        </DrawerOverlay>

        <!--签到设置-->
        <DrawerOverlay v-model="signInSettingShow" placement="right" :size="720">
            <div v-if="signInSettingShow" class="ivu-modal-wrap-apply">
                <div class="ivu-modal-wrap-apply-title">
                    {{ $L('签到设置') }}
                    <p @click="signInSettingShow=false">{{ $L('返回') }}</p>
                </div>
                <div class="ivu-modal-wrap-apply-body">
                    <SystemCheckin/>
                </div>
            </div>
        </DrawerOverlay>

        <!--会议-->
        <DrawerOverlay v-model="meetingShow" placement="right" :size="720">
            <div v-if="meetingShow" class="ivu-modal-wrap-apply">
                <div class="ivu-modal-wrap-apply-title">
                    {{ $L('会议') }}
                    <p @click="meetingSettingShow = true" v-if="userIsAdmin">{{ $L('会议设置') }}</p>
                </div>
                <div class="ivu-modal-wrap-apply-body full-body">
                    <SystemMeetingNav @openDetail="openDetail" @onMeeting="onMeeting"/>
                </div>
            </div>
        </DrawerOverlay>

        <!--会议设置-->
        <DrawerOverlay v-model="meetingSettingShow" placement="right" :size="600">
            <div v-if="meetingSettingShow" class="ivu-modal-wrap-apply">
                <div class="ivu-modal-wrap-apply-title">
                    {{ $L('会议设置') }}
                    <p @click="meetingSettingShow = false">{{ $L('返回') }}</p>
                </div>
                <div class="ivu-modal-wrap-apply-body full-body">
                    <SystemMeeting/>
                </div>
            </div>
        </DrawerOverlay>

        <!--LDAP-->
        <DrawerOverlay v-model="ldapShow" placement="right" :size="700">
            <div v-if="ldapShow" class="ivu-modal-wrap-apply">
                <div class="ivu-modal-wrap-apply-title">
                    {{ $L('LDAP 设置') }}
                </div>
                <div class="ivu-modal-wrap-apply-body">
                    <SystemThirdAccess/>
                </div>
            </div>
        </DrawerOverlay>

        <!--邮件-->
        <DrawerOverlay v-model="mailShow" placement="right" :size="700">
            <div v-if="mailShow" class="ivu-modal-wrap-apply">
                <div class="ivu-modal-wrap-apply-title">
                    {{ $L('邮件通知') }}
                </div>
                <div class="ivu-modal-wrap-apply-body">
                    <SystemEmailSetting/>
                </div>
            </div>
        </DrawerOverlay>

        <!--App 推送-->
        <DrawerOverlay v-model="appPushShow" placement="right" :size="700">
            <div v-if="appPushShow" class="ivu-modal-wrap-apply">
                <div class="ivu-modal-wrap-apply-title">
                    {{ $L('APP 推送') }}
                </div>
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
import SystemAibot from "./setting/components/SystemAibot";
import SystemCheckin from "./setting/components/SystemCheckin";
import Checkin from "./setting/checkin";
import SystemMeeting from "./setting/components/SystemMeeting";
import SystemMeetingNav from "./setting/components/SystemMeetingNav.vue";
import SystemThirdAccess from "./setting/components/SystemThirdAccess";
import SystemEmailSetting from "./setting/components/SystemEmailSetting";
import SystemAppPush from "./setting/components/SystemAppPush";
import emitter from "../../store/events";
import {AIBotList, AIModelNames} from "../../utils/ai";
import ImgUpload from "../../components/ImgUpload.vue";

export default {
    components: {
        ImgUpload,
        UserSelect,
        DrawerOverlay,
        SystemAibot,
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
            //
            mybotShow: false,
            mybotList: [],
            mybotLoad: 0,
            mybotModifyShow: false,
            mybotModifyData: {},
            mybotModifyLoad: 0,
            //
            aibotShow: false,
            aibotList: AIBotList,
            aibotSettingShow: false,
            aibotTabAction: "openai",
            aibotDialogSearchLoad: "",
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
            scanLoginShow: false,
            scanLoginLoad: false,
            scanLoginCode: '',
            //
            sendData: [],
            sendType: '',
        }
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
            'formOptions',
            'routeLoading',
            'microAppsInstalled'
        ]),
        ...mapGetters([
            'filterMicroAppsMenus',
            'filterMicroAppsMenusAdmin',
        ]),
        applyList() {
            const list = [
                {value: "approve", label: "审批中心", sort: 30, show: this.microAppsInstalled.includes('approve')},
                {value: "report", label: "工作报告", sort: 50},
                {value: "mybot", label: "我的机器人", sort: 55},
                {value: "robot", label: "AI 机器人", sort: 60, show: this.microAppsInstalled.includes('ai')},
                {value: "signin", label: "签到打卡", sort: 70},
                {value: "meeting", label: "在线会议", sort: 80},
                {value: "createGroup", label: "创建群组", sort: 85},
                {value: "word-chain", label: "群接龙", sort: 90},
                {value: "vote", label: "群投票", sort: 100},
                {value: "addProject", label: "创建项目", sort: 110},
                {value: "addTask", label: "添加任务", sort: 120},
                {value: "scan", label: "扫一扫", sort: 130, show: $A.isEEUIApp},
            ]
            // 竖屏模式
            if (this.windowPortrait) {
                list.push(...[
                    {value: "calendar", label: "日历", sort: 10},
                    {value: "file", label: "文件", sort: 20},
                    {value: "setting", label: "设置", sort: 140},
                ])
            }
            // 管理员
            if (this.userIsAdmin) {
                list.push(...[
                    {type: 'admin', value: "ldap", label: "LDAP", sort: 160},
                    {type: 'admin', value: "mail", label: "邮件通知", sort: 170},
                    {type: 'admin', value: "appPush", label: "APP 推送", sort: 180},
                    {type: 'admin', value: "complaint", label: "举报管理", sort: 190},
                    {type: 'admin', value: "allUser", label: "团队管理", sort: 200},
                ])
            }
            //
            return list.sort((a, b) => a.sort - b.sort);
        },
        isExistAdminList() {
            return this.filterMicroAppsMenusAdmin.length > 0 || this.applyList.map(h => h.type).indexOf('admin') !== -1;
        }
    },
    methods: {
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
                case 'robot':
                    this.getAITags();
                    this.aibotShow = true;
                    break;
                case 'robot-setting':
                    this.aibotTabAction = params;
                    this.aibotSettingShow = true;
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
        // 获取我的机器人
        getMybot() {
            this.mybotLoad++
            this.$store.dispatch("call", {
                url: 'users/bot/list',
            }).then(({data}) => {
                this.mybotList = data.list;
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
            this.mybotModifyData = $A.cloneJSON(info)
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
                const index = this.mybotList.findIndex(item => item.id === data.id);
                if (index > -1) {
                    this.mybotList.splice(index, 1, data);
                } else {
                    this.mybotList.unshift(data);
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
        // 获取AI标签
        getAITags() {
            this.$store.dispatch("call", {
                url: 'system/setting/aibot_models',
            }).then(({data}) => {
                this.handleAITags(data);
            });
        },
        // 处理AI标签
        handleAITags(data) {
            for (let key in data) {
                const match = key.match(/^(.*?)_models$/);
                if (match) {
                    const value = match[1];
                    this.aibotList.map(h => {
                        if (h.value == value) {
                            const items = AIModelNames(data[key])
                            h.tags = items.map(item => item.label);
                            h.tag = data[key.slice(0, -1)];
                            items.some(item => {
                                if (item.value == h.tag) {
                                    h.tag = item.label;
                                    return true;
                                }
                            })
                        }
                    });
                }
            }
        },
        // 开始聊天
        onGoToChat(type) {
            let dialogId = 0;
            this.cacheDialogs.some(h => {
                if (h.email == `ai-${type}@bot.system`) {
                    dialogId = h.id;
                    return true;
                }
            })
            if (dialogId) {
                this.$store.dispatch("openDialog", dialogId)
                return
            }
            //
            this.aibotDialogSearchLoad = type;
            this.$store.dispatch("call", {
                url: 'users/search/ai',
                data: {type},
            }).then(({data}) => {
                this.$store.dispatch("openDialogUserid", data.userid).catch(({msg}) => {
                    $A.modalError(msg)
                }).finally(_ => {
                    this.aibotDialogSearchLoad = '';
                });
            }).catch(({msg}) => {
                this.aibotDialogSearchLoad = '';
                $A.messageError(msg || '机器人暂未开启');
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
