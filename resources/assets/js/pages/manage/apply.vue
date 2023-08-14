<template>
    <div class="page-apply">
        
        <PageTitle :title="$L('应用')" />

        <div class="apply-wrapper">
            <div class="apply-head">
                <div class="apply-nav">
                    <h1>{{ $L('应用') }}</h1>
                </div>
            </div>
            <div class="apply-row">
                <Row :gutter="16">
                    <Col v-for="item in applyList" :xs="{ span: 8 }" :lg="{ span: 6 }" :xl="{ span: 4 }" :xxl="{ span: 3 }">
                    <div class="apply-col">
                        <div @click="applyClick(item)">
                            <img :src="item.src">
                            <p>{{ item.label }}</p>
                            <!-- 工作报告 -->
                            <Badge v-if="item.value == 'report' && reportUnreadNumber > 0" class="manage-box-top-report" :overflow-count="999" :count="reportUnreadNumber"/>
                        </div>
                    </div>
                    </Col>
                </Row>
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
                    <div v-if="aibotType==1">
                        <ul class="aibot-ul">
                            <li>
                                <img src="/images/avatar/default_openai.png">
                                <h4>ChatGPT</h4>
                                <p>我是一个人工智能助手，为用户提供问题解答和指导。我没有具体的身份，只是一个程序。您有什么问题可以问我哦？</p>
                                <p class="btn">去聊天</p>
                            </li>
                            <li>
                                <img src="/images/avatar/default_claude.png">
                                <h4>Claude</h4>
                                <p>我是Claude,一个由Anthropic公司创造出来的AI助手机器人。我的工作是帮助人类,与人对话并给出解答。</p>
                                <p class="btn">去聊天</p>
                            </li>
                            <li>
                                <img src="/avatar/Wenxin.png">
                                <h4>文心一言 (Wenxin)</h4>
                                <p>我是文心一言，英文名是ERNIE Bot。我能够与人对话互动，回答问题，协助创作，高效便捷地帮助人们获取信息、知识和灵感。</p>
                                <p class="btn">去聊天</p>
                            </li>
                            <li>
                                <img src="/avatar/%E9%80%9A%E4%B9%89%E5%8D%83%E9%97%AE.png">
                                <h4>通义千问 (Qianwen)</h4>
                                <p>我是达摩院自主研发的超大规模语言模型，能够回答问题、创作文字，还能表达观点、撰写代码。</p>
                                <p class="btn">去聊天</p>
                            </li>
                        </ul>
                    </div>
                    <div v-if="aibotType==2">
                        <Tabs v-model="aibotTabAction" style="height: 100%;display: flex;flex-direction: column;">
                            <TabPane label="ChatGPT" name="opanai" style="height: 100%;">
                                <div style="position: relative;height: 100%;"> <SystemAibot type="ChatGPT"/></div>
                            </TabPane>
                            <TabPane label="Claude" name="claude" style="height: 100%;">
                                <div style="position: relative;height: 100%;"> <SystemAibot type="Claude"/></div>
                            </TabPane>
                            <TabPane label="文心一言" name="wenxin" style="height: 100%;">
                                <div style="position: relative;height: 100%;"> <SystemAibot type="Wenxin"/></div>
                            </TabPane>
                            <TabPane label="通义千问" name="qianwen" style="height: 100%;">
                                <div style="position: relative;height: 100%;"> <SystemAibot type="Qianwen"/></div>
                            </TabPane>
                        </Tabs>
                    </div>
                </div>
            </div>
        </DrawerOverlay>

        <!--签到-->
        <DrawerOverlay v-model="signInShow" placement="right" :size="700">
            <div class="ivu-modal-wrap-apply">
                <div class="ivu-modal-wrap-apply-title">
                    {{ $L('签到管理') }}
                    <p @click="signType = signType == 1 ? 2 : 1" v-if="userIsAdmin">
                        {{ signType == 1 ? $L('系统设置') : $L('返回') }}
                    </p>
                </div>
                <div class="ivu-modal-wrap-apply-body">
                    <Checkin v-if="signType==1"/>
                    <SystemCheckin v-if="signType==2"/>
                </div>
            </div>
        </DrawerOverlay>

        <!-- 会议  -->
        <DrawerOverlay v-model="meetingShow" placement="right" :size="600">
            <div class="ivu-modal-wrap-apply">
                <div class="ivu-modal-wrap-apply-title">
                    {{ $L('会议功能') }}
                    <p @click="meetingType = meetingType == 1 ? 2 : 1">
                        {{ meetingType == 1 ? $L('会议设置') : $L('返回') }}
                    </p>
                </div>
                <div class="ivu-modal-wrap-apply-body">
                    <div v-if="meetingType==1">
                        <Form ref="addForm" :model="meetingAddData" label-width="auto" @submit.native.prevent>
                            <template v-if="meetingAddData.type === 'join'">
                                <!-- 加入会议 -->
                                <FormItem v-if="meetingAddData.name" prop="userids" :label="$L('会议主题')">
                                    <Input v-model="meetingAddData.name" disabled/>
                                </FormItem>
                                <FormItem prop="meetingid" :label="$L('会议频道ID')">
                                    <Input v-model="meetingAddData.meetingid" :disabled="meetingAddData.meetingdisabled === true" :placeholder="$L('请输入会议频道ID')"/>
                                </FormItem>
                            </template>
                            <template v-else>
                                <!-- 新会议 -->
                                <FormItem prop="name" :label="$L('会议主题')">
                                    <Input v-model="meetingAddData.name" :maxlength="50" :placeholder="$L('选填')"/>
                                </FormItem>
                                <FormItem prop="meetingid" :label="$L('会议频道ID')">
                                    <Input v-model="meetingAddData.meetingid" :disabled="meetingAddData.meetingdisabled === true" :placeholder="$L('请输入会议频道ID')"/>
                                </FormItem>
                                <FormItem prop="userids" :label="$L('邀请成员')">
                                    <UserSelect v-model="meetingAddData.userids" :uncancelable="[userId]" :multiple-max="20" :title="$L('选择邀请成员')"/>
                                </FormItem>
                            </template>
                            <FormItem prop="tracks">
                                <CheckboxGroup v-model="meetingAddData.tracks">
                                    <Checkbox label="audio">
                                        <span>{{$L('麦克风')}}</span>
                                    </Checkbox>
                                    <Checkbox label="video">
                                        <span>{{$L('摄像头')}}</span>
                                    </Checkbox>
                                </CheckboxGroup>
                            </FormItem>
                        </Form>
                        <div slot="footer" class="adaption">
                            <!-- <Button type="default" @click="meetingShow=false">{{$L('取消')}}</Button> -->
                            <Button type="primary" :loading="meetingLoadIng > 0" @click="onMeetingSubmit">{{$L(meetingAddData.type === 'join' ? '加入会议' : '开始会议')}}</Button>
                        </div>
                        <!-- 
                        <p @click="onAddMenu('createMeeting')" style="cursor: pointer;color: #2b85e4;    padding: 10px;
                            border: 1px solid;
                            border-radius: 5px;">新会议</p>

                        <p @click="onAddMenu('joinMeeting')" style="cursor: pointer;color: #2b85e4;    padding: 10px;
                            border: 1px solid;
                            border-radius: 5px;margin-top: 30px;">加入会议</p> -->
                    </div>
                    <SystemMeeting v-if="meetingType==2"/>
                </div>
            </div>
        </DrawerOverlay>

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
import {Store} from "le5le-store";

export default {
    components: { UserSelect, DrawerOverlay, Report, SystemAibot, SystemCheckin, Checkin, SystemMeeting },
    data() {
        return {
            applyList: [
                { value: "approve", label: "审批中心", src: "/images/apply/approve.svg" },
                { value: "report", label: "工作报告", src: "/images/apply/report.svg"},
                { value: "ai", label: "AI机器人", src: "/images/apply/robot.svg" },
                { value: "signIn", label: "签到", src: "/images/apply/signin.svg" },
                { value: "meeting", label: "会议", src: "/images/apply/meeting.svg" },
                { value: "ldap", label: "LDAP", src: "/images/apply/ldap.svg" },
                { value: "mail", label: "邮件", src: "/images/apply/mail.svg" },
                { value: "appPush", label: "APP推送", src: "/images/apply/apppush.svg" },
            ],

            workReportShow: false,
            workReportTabs: "my",

            aibotTabAction: "opanai",
            aibotShow: false,
            aibotType: 1,

            signInShow: false,
            signType: 1,

            meetingShow: false,
            meetingType: 1,
            meetingAddData: {
                userids: [],
                tracks: ['audio']
            },
            meetingLoadIng: 0
        }
    },

    created() {
    },

    mounted() {
    },

    activated() {
    },

    computed: {
        ...mapState([
            'userInfo',
            'userIsAdmin',
           
            'reportUnreadNumber',
            'approveUnreadNumber',
        ]),

    },

    watch: {

    },

    methods: {
        applyClick(item) {
            switch (item.value) {
                case 'approve':
                    this.goForward({name: 'manage-approve'});
                    break;
                case 'report':
                    this.workReportShow = true;
                    break;
                case 'ai':
                    this.aibotType = 1;
                    this.aibotTabAction = "opanai";
                    this.aibotShow = true;
                    break;
                case 'signIn':
                    this.signInShow = true;
                    break;
                case 'meeting':
                    this.meetingShow = true;
                    break;
            }
        },
        onAddMenu(name) {
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
        onMeetingSubmit(){

        }
    }
}
</script>
