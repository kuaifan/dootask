<template>
    <div class="page-apply">

        <PageTitle :title="$L('应用')" />

        <div class="apply-wrapper">
            <div class="apply-head">
                <div class="apply-nav">
                    <h1>{{ $L('应用') }}</h1>
                </div>
            </div>
            <div class="apply-content">
                <template v-for="t in applyListTypes">
                    <div v-if="isExistAdminList" class="apply-row-title">
                        {{ t == 'base' ? $L('常用') : $L('管理员') }}
                    </div>
                    <Row :gutter="16">
                        <Col v-for="(item, key) in applyList" :key="key"
                            v-if="((t=='base' && !item.type) || item.type == t) && item.show !== false"
                            :xs="{ span: 6 }"
                            :sm="{ span: 6 }"
                            :lg="{ span: 6 }"
                            :xl="{ span: 6 }"
                            :xxl="{ span: 3 }"
                        >
                            <div class="apply-col">
                                <div @click="applyClick(item)">
                                    <div class="logo">
                                        <img :src="getLogoPath(item.value)" />
                                        <div @click.stop="applyClick(item, 'badge')" class="apply-box-top-report">
                                            <Badge v-if="showBadge(item,'approve')" :overflow-count="999" :count="approveUnreadNumber" />
                                            <Badge v-if="showBadge(item,'report')" :overflow-count="999" :count="reportUnreadNumber" />
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

        <!--工作报告-->
        <DrawerOverlay v-model="workReportShow" placement="right" :size="1200">
            <Report v-if="workReportShow" v-model="workReportTabs" @on-read="$store.dispatch('getReportUnread', 1000)" />
        </DrawerOverlay>

        <!--AI机器人-->
        <DrawerOverlay v-model="aibotShow" placement="right" :size="600">
            <div class="ivu-modal-wrap-apply">
                <div class="ivu-modal-wrap-apply-title">
                    {{ $L('AI机器人') }}
                    <p @click="aibotType = aibotType == 1 ? 2 : 1" v-if="userIsAdmin">
                        {{ aibotType == 1 ? $L('机器人设置') : $L('返回') }}
                    </p>
                </div>
                <div class="ivu-modal-wrap-apply-body">
                    <ul class="ivu-modal-wrap-ul" v-if="aibotType == 1">
                        <li v-for="(item, key) in aibotList"  :key="key">
                            <img :src="item.src">
                            <h4>{{ item.label }}</h4>
                            <p class="desc" @click="openDetail(item.desc)">{{ item.desc }}</p>
                            <p class="btn" @click="onGoToChat(item.value)">{{ $L('去聊天') }}</p>
                            <div class="load" v-if="aibotDialogSearchLoad == item.value">
                                <Loading />
                            </div>
                        </li>
                    </ul>
                    <Tabs v-else v-model="aibotTabAction">
                        <TabPane label="ChatGPT" name="opanai">
                            <div class="aibot-warp">
                                <SystemAibot type="ChatGPT" v-if="aibotTabAction == 'opanai'" />
                            </div>
                        </TabPane>
                        <TabPane label="Claude" name="claude">
                            <div class="aibot-warp">
                                <SystemAibot type="Claude" v-if="aibotTabAction == 'claude'" />
                            </div>
                        </TabPane>
                        <TabPane :label="$L('文心一言')" name="wenxin">
                            <div class="aibot-warp">
                                <SystemAibot type="Wenxin" v-if="aibotTabAction == 'wenxin'" />
                            </div>
                        </TabPane>
                        <TabPane :label="$L('通义千问')" name="qianwen">
                            <div class="aibot-warp">
                                <SystemAibot type="Qianwen" v-if="aibotTabAction == 'qianwen'" />
                            </div>
                        </TabPane>
                    </Tabs>
                </div>
            </div>
        </DrawerOverlay>

        <!--签到-->
        <DrawerOverlay v-model="signInShow" placement="right" :size="700">
            <div class="ivu-modal-wrap-apply">
                <div class="ivu-modal-wrap-apply-title">
                    {{ $L('签到管理') }}
                    <p @click="signInType = signInType == 1 ? 2 : 1" v-if="userIsAdmin">
                        {{ signInType == 1 ? $L('系统设置') : $L('返回') }}
                    </p>
                </div>
                <div class="ivu-modal-wrap-apply-body">
                    <Checkin v-if="signInType == 1" />
                    <SystemCheckin v-else />
                </div>
            </div>
        </DrawerOverlay>

        <!-- 会议  -->
        <DrawerOverlay v-model="meetingShow" placement="right" :size="600">
            <div class="ivu-modal-wrap-apply">
                <div class="ivu-modal-wrap-apply-title">
                    {{ $L('会议') }}
                    <p @click="meetingType = meetingType == 1 ? 2 : 1">
                        {{ meetingType == 1 ? $L('会议设置') : $L('返回') }}
                    </p>
                </div>
                <div class="ivu-modal-wrap-apply-body">
                    <ul class="ivu-modal-wrap-ul" v-if="meetingType == 1">
                        <li>
                            <img :src="getLogoPath('meeting')">
                            <h4>{{ $L('新会议') }}</h4>
                            <p class="desc" @click="openDetail(meetingDescs.add)"> {{ meetingDescs.add }} </p>
                            <p class="btn" @click="onMeeting('createMeeting')">{{ $L('新建会议') }}</p>
                        </li>
                        <li>
                            <img :src="getLogoPath('meeting-join')">
                            <h4>{{ $L('加入会议') }}</h4>
                            <p class="desc" @click="openDetail(meetingDescs.join)">{{ meetingDescs.join }}</p>
                            <p class="btn" @click="onMeeting('joinMeeting')">{{ $L('加入会议') }}</p>
                        </li>
                    </ul>
                    <SystemMeeting v-else />
                </div>
            </div>
        </DrawerOverlay>

        <!--LDAP-->
        <DrawerOverlay v-model="ldapShow" placement="right" :size="700">
            <div class="ivu-modal-wrap-apply">
                <div class="ivu-modal-wrap-apply-title">
                    {{ $L('LDAP设置') }}
                </div>
                <div class="ivu-modal-wrap-apply-body">
                    <SystemThirdAccess />
                </div>
            </div>
        </DrawerOverlay>

        <!--邮件-->
        <DrawerOverlay v-model="mailShow" placement="right" :size="700">
            <div class="ivu-modal-wrap-apply">
                <div class="ivu-modal-wrap-apply-title">
                    {{ $L('邮件管理') }}
                </div>
                <div class="ivu-modal-wrap-apply-body">
                    <SystemEmailSetting />
                </div>
            </div>
        </DrawerOverlay>

        <!--app推送-->
        <DrawerOverlay v-model="appPushShow" placement="right" :size="700">
            <div class="ivu-modal-wrap-apply">
                <div class="ivu-modal-wrap-apply-title">
                    {{ $L('APP推送') }}
                </div>
                <div class="ivu-modal-wrap-apply-body">
                    <SystemAppPush />
                </div>
            </div>
        </DrawerOverlay>

        <!-- 扫码登录 -->
        <Modal
            v-model="scanLoginShow"
            :title="$L('扫码登录')"
            :mask-closable="false">
            <div class="mobile-scan-login-box">
                <div class="mobile-scan-login-title">{{$L(`你好，扫码确认登录`)}}</div>
                <div class="mobile-scan-login-subtitle">「{{$L('为确保帐号安全，请确认是本人操作')}}」</div>
            </div>
            <div slot="footer" class="adaption">
                <Button type="default" @click="scanLoginShow=false">{{$L('取消登录')}}</Button>
                <Button type="primary" :loading="scanLoginLoad" @click="scanLoginSubmit">{{$L('确认登录')}}</Button>
            </div>
        </Modal>

        <!-- 发起接龙 -->
        <UserSelect
            ref="wordChainAndVoteRef"
            v-model="sendData"
            :multiple-max="50"
            :title="sendType == 'vote' ? $L('选择群组发起投票') : $L('选择群组发起接龙')"
            :before-submit="goWordChainAndVote"
            :show-select-all="false"
            :forced-radio="true"
            :group="true"
            show-dialog
            module/>

    </div>
</template>

<script>
import { mapState } from "vuex";
import DrawerOverlay from "../../components/DrawerOverlay";
import UserSelect from "../../components/UserSelect";
import Report from "../manage/components/Report";
import SystemAibot from "./setting/components/SystemAibot";
import SystemCheckin from "./setting/components/SystemCheckin";
import Checkin from "./setting/checkin";
import SystemMeeting from "./setting/components/SystemMeeting";
import SystemThirdAccess from "./setting/components/SystemThirdAccess";
import SystemEmailSetting from "./setting/components/SystemEmailSetting";
import SystemAppPush from "./setting/components/SystemAppPush";
import { Store } from "le5le-store";

export default {
    components: {
        UserSelect,
        DrawerOverlay,
        Report,
        SystemAibot,
        SystemCheckin,
        Checkin,
        SystemMeeting,
        SystemThirdAccess,
        SystemEmailSetting,
        SystemAppPush
    },
    data() {
        return {
            applyList: [],
            applyListTypes: ['base', 'admin'],
            //
            workReportShow: false,
            workReportTabs: "my",
            //
            aibotList: [
                {
                    value: "openai",
                    label: "ChatGPT",
                    src: $A.apiUrl('../images/avatar/default_openai.png'),
                    desc: this.$L('我是一个人工智能助手，为用户提供问题解答和指导。我没有具体的身份，只是一个程序。您有什么问题可以问我哦？')
                },
                {
                    value: "claude",
                    label: "Claude",
                    src: $A.apiUrl('../images/avatar/default_claude.png'),
                    desc: this.$L('我是Claude,一个由Anthropic公司创造出来的AI助手机器人。我的工作是帮助人类,与人对话并给出解答。')
                },
                {
                    value: "wenxin",
                    label: "Wenxin",
                    src: $A.apiUrl('../avatar/Wenxin.png'),
                    desc: this.$L('我是文心一言，英文名是ERNIE Bot。我能够与人对话互动，回答问题，协助创作，高效便捷地帮助人们获取信息、知识和灵感。')
                },
                {
                    value: "qianwen",
                    label: "Qianwen",
                    src: $A.apiUrl('../avatar/%E9%80%9A%E4%B9%89%E5%8D%83%E9%97%AE.png'),
                    desc: this.$L('我是达摩院自主研发的超大规模语言模型，能够回答问题、创作文字，还能表达观点、撰写代码。')
                },
            ],
            aibotTabAction: "opanai",
            aibotShow: false,
            aibotType: 1,
            aibotDialogSearchLoad: "",
            //
            signInShow: false,
            signInType: 1,
            //
            meetingShow: false,
            meetingType: 1,
            meetingDescs: {
                add: this.$L('创建一个全新的会议视频会议，与会者可以在实时中进行面对面的视听交流。通过视频会议平台，参与者可以分享屏幕、共享文档，并与其他与会人员进行讨论和协。'),
                join: this.$L('加入视频会议，参与已经创建的会议，在会议过程中与其他参会人员进行远程实时视听交流和协作。'),
            },
            //
            ldapShow: false,
            //
            mailType: 1,
            mailShow: false,
            //
            appPushType: 1,
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
        this.initList()
    },
    computed: {
        ...mapState([
            'userIsAdmin',
            'reportUnreadNumber',
            'approveUnreadNumber',
            'cacheDialogs',
            'windowOrientation',
        ]),
        isExistAdminList() {
            return this.applyList.map(h => h.type).indexOf('admin') !== -1;
        }
    },
    watch: {
        windowOrientation() {
            this.initList()
        }
    },
    methods: {
        initList() {
            let applyList = [
                { value: "approve", label: "审批中心", sort: 1 },
                { value: "report", label: "工作报告", sort: 2 },
                { value: "okr", label: "OKR管理", sort: 3 },
                { value: "robot", label: "AI机器人", sort: 4 },
                { value: "signin", label: "签到", sort: 5 },
                { value: "meeting", label: "会议", sort: 6 },
                { value: "calendar", label: "日历", sort: 7 },
                { value: "word-chain", label: "接龙", sort: 9 },
                { value: "vote", label: "投票", sort: 10 },
            ];
            // wap模式
            let appApplyList = this.windowOrientation != 'portrait' ? (
                    $A.isEEUiApp ? [
                        { value: "scan", label: "扫一扫", sort: 13 }
                    ] : []
                ) : [
                { value: "file", label: "文件", sort: 8 },
                { value: "addProject", label: "创建项目", sort: 11 },
                { value: "addTask", label: "添加任务", sort: 12 },
                { value: "scan", label: "扫一扫", sort: 13 , show: $A.isEEUiApp },
                { value: "setting", label: "设置", sort: 14 }
            ];
            // 管理员
            let adminApplyList = !this.userIsAdmin ? [] : [
                { value: "okrAnalyze", label: "OKR结果", sort: 15 },
                { value: "ldap", label: "LDAP", sort: 16 },
                { value: "mail", label: "邮件", sort: 17 },
                { value: "appPush", label: "APP推送", sort: 18 },
                { value: "allUser", label: "团队管理", sort: 19 }
            ].map((h) => {
                h.type = 'admin';
                return h;
            });
            //
            this.applyList = [...applyList, ...appApplyList, ...adminApplyList].sort((a, b) => {
                if (a.sort < b.sort) {
                    return -1;
                } else if (a.sort > b.sort) {
                    return 1;
                } else {
                    return 0;
                }
            });
        },
        getLogoPath(name) {
            name = name.replace(/([a-z])([A-Z])/g, '$1-$2').toLowerCase();
            return $A.apiUrl(`../images/application/${name}.svg`)
        },
        showBadge(item,type) {
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
        applyClick(item, area = '') {
            switch (item.value) {
                case 'approve':
                case 'calendar':
                case 'file':
                case 'setting':
                    this.goForward({ name: 'manage-' + item.value });
                    break;
                case 'okr':
                case 'okrAnalyze':
                    this.goForward({
                        path: '/manage/apps/okr/' + (item.value == 'okr' ? 'list' : 'analysis'),
                    });
                    break;
                case 'report':
                    this.workReportTabs = area == 'badge' ? 'receive' : 'my';
                    this.workReportShow = true;
                    break;
                case 'robot':
                    this.aibotType = 1;
                    this.aibotTabAction = "opanai";
                    this.aibotShow = true;
                    break;
                case 'signin':
                    this.signInType = 1;
                    this.signInShow = true;
                    break;
                case 'meeting':
                    this.meetingType = 1;
                    this.meetingShow = true;
                    break;
                case 'ldap':
                    this.ldapShow = true;
                    break;
                case 'mail':
                    this.mailType = 1;
                    this.mailShow = true;
                    break;
                case 'appPush':
                    this.appPushType = 1;
                    this.appPushShow = true;
                    break;
                case 'scan':
                    $A.eeuiAppScan(this.scanResult);
                    return;
                case 'word-chain':
                case 'vote':
                    this.sendData = [];
                    this.sendType = item.value;
                    this.$refs.wordChainAndVoteRef.onSelection()
                    return;
            }
            this.$emit("on-click", item.value)
        },
        // 去聊天
        onGoToChat(type) {
            let dialogId = 0;
            let email = `ai-${type}@bot.system`;
            this.cacheDialogs.map(h => {
                if (h.email == email) {
                    dialogId = h.id;
                }
            })
            if (dialogId) {
                if (this.windowOrientation == 'portrait') {
                    this.$store.dispatch("openDialog", dialogId)
                } else {
                    this.goForward({ name: 'manage-messenger', params: { dialog_id: dialogId } });
                }
                this.aibotShow = false;
            } else {
                this.aibotDialogSearchLoad = type;
                this.$store.dispatch("call", {
                    url: 'dialog/search',
                    data: { key: email },
                }).then(({ data }) => {
                    if (data?.length < 1) {
                        $A.messageError('机器人暂未开启');
                        this.aibotDialogSearchLoad = '';
                        return;
                    }
                    this.$store.dispatch("openDialogUserid", data[0]?.dialog_user.userid).then(_ => {
                        if (this.windowOrientation != 'portrait') {
                            this.goForward({ name: 'manage-messenger' })
                        }
                        this.aibotShow = false;
                    }).catch(({ msg }) => {
                        $A.modalError(msg)
                    }).finally(_ => {
                        this.aibotDialogSearchLoad = '';
                    });
                }).catch(_ => {
                    this.aibotDialogSearchLoad = '';
                });
            }
        },
        // 会议
        onMeeting(name) {
            switch (name) {
                case 'createMeeting':
                    Store.set('addMeeting', {
                        type: 'create',
                        userids: [this.userId],
                    });
                    break;
                case 'joinMeeting':
                    Store.set('addMeeting', {
                        type: 'join',
                    });
                    break;
            }
            this.meetingShow = false;
        },
        // 扫一扫
        scanResult(text) {
            const arr = (text + "").match(/^https*:\/\/(.*?)\/login\?qrcode=(.*?)$/)
            if (arr) {
                // 扫码登录
                this.scanLoginCode = arr[2];
                this.scanLoginShow = true;
                return
            }
            if (/^https*:\/\//i.test(text)) {
                // 打开链接
                $A.eeuiAppOpenPage({
                    pageType: 'app',
                    pageTitle: ' ',
                    url: 'web.js',
                    params: {
                        url: text,
                        browser: true,
                        showProgress: true,
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
        openDetail(desc){
            $A.modalInfo({
                content: desc,
                onOk: () => {
                    return new Promise((resolve, reject) => {
                        this.$store.dispatch("call", {
                            url: 'dialog/group/disband',
                            data: {
                                dialog_id: this.dialogId,
                            }
                        }).then(({msg}) => {
                            resolve(msg);
                            this.$store.dispatch("forgetDialog", this.dialogId);
                            this.goForward({name: 'manage-messenger'});
                        }).catch(({msg}) => {
                            reject(msg);
                        });
                    })
                },
            });
        },
        // 前往接龙与投票
        goWordChainAndVote(){
            const dialog_id = Number(this.sendData[0].replace('d:', ''))
            if(this.windowPortrait){
                this.$store.dispatch("openDialog", dialog_id ).then(() => {
                    this.$store.state[ this.sendType == 'word-chain' ?'dialogDroupWordChain' : 'dialogGroupVote'] = {
                        type: 'create',
                        dialog_id: dialog_id
                    }
                })
            }else{
                this.goForward({ name: 'manage-messenger', params: { dialog_id: dialog_id}});
                setTimeout(()=>{
                    this.$store.state[ this.sendType == 'word-chain' ?'dialogDroupWordChain' : 'dialogGroupVote'] = {
                        type: 'create',
                        dialog_id: dialog_id
                    }
                },100)
            }
        }
    }
}
</script>
