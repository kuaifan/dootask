<template>
    <div class="meeting-warp">
        <!-- 加入/新建 -->
        <Modal
            v-model="addShow"
            :title="$L(addData.type === 'join' ? '加入会议' : '新会议')"
            :mask-closable="false"
            :closable="!addData.sharekey">
            <Form ref="addForm" :model="addData" :rules="addRule" label-width="auto" @submit.native.prevent>
                <template v-if="addData.type === 'join'">
                    <!-- 加入会议 -->
                    <FormItem v-if="addData.name" prop="userids" :label="$L('会议主题')">
                        <Input v-model="addData.name" disabled/>
                    </FormItem>
                    <FormItem v-if="addData.sharekey" prop="username" :label="$L('你的姓名')">
                        <Input v-model="addData.username" :placeholder="$L('请输入你的姓名')"/>
                    </FormItem>
                    <FormItem prop="meetingid" :label="$L('会议频道ID')">
                        <Input v-model="addData.meetingid" :disabled="addData.meetingdisabled === true" :placeholder="$L('请输入会议频道ID')"/>
                    </FormItem>
                </template>
                <template v-else>
                    <!-- 新会议 -->
                    <FormItem prop="name" :label="$L('会议主题')">
                        <Input v-model="addData.name" :maxlength="50" :placeholder="$L('选填')"/>
                    </FormItem>
                    <FormItem prop="userids" :label="$L('邀请成员')">
                        <UserSelect v-model="addData.userids" :uncancelable="[userId]" :multiple-max="20" :title="$L('选择邀请成员')"/>
                    </FormItem>
                </template>
                <FormItem prop="tracks">
                    <CheckboxGroup v-model="addData.tracks">
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
                <Button type="default" @click="addShow=false" v-if="!addData.sharekey">{{$L('取消')}}</Button>
                <Button type="primary" :loading="loadIng > 0" @click="onSubmit">{{$L(addData.type === 'join' ? '加入会议' : '开始会议')}}</Button>
            </div>
        </Modal>

        <!-- 会议中 -->
        <Modal
            v-model="meetingShow"
            :title="addData.name"
            ref="meetingModal"
            :mask="false"
            :mask-closable="false"
            :closable="false"
            :transition-names="['', '']"
            :beforeClose="onClose"
            :class-name="`meeting-manager${meetingMini ? ' meeting-hidden' : ''}`"
            :ignore-remove-last="meetingMini"
            fullscreen>
            <ul>
                <li v-if="localUser.uid">
                    <MeetingPlayer :player="localUser" isLocal/>
                </li>
                <li v-for="user in remoteUsers">
                    <MeetingPlayer :player="user"/>
                </li>
            </ul>
            <div slot="footer" class="adaption meeting-button-group">
                <Button type="primary" :loading="audioLoad" @click="onAudio">
                    <i class="taskfont" v-html="localUser.audioTrack ? '&#xe7c3;' : '&#xe7c7;'"></i>
                </Button>
                <Button type="primary" :loading="videoLoad" @click="onVideo">
                    <i class="taskfont" v-html="localUser.videoTrack ? '&#xe7c1;' : '&#xe7c8;'"></i>
                </Button>
                <template v-if="windowPortrait">
                    <Button type="primary" :loading="linkCopyLoad" @click="onInvitation('open')">
                        <i class="taskfont">&#xe646;</i>
                    </Button>
                    <Button type="primary" v-if="!addData.sharekey" @click="meetingMini = true">
                        <i class="taskfont">&#xe656;</i>
                    </Button>
                    <Button type="warning" :loading="loadIng > 0" @click="onClose">
                        <i class="taskfont">&#xe612;</i>
                    </Button>
                </template>
                <template v-else>
                    <Button type="primary" @click="onInvitation('open')">{{$L('邀请')}}</Button>
                    <Button type="primary" v-if="!addData.sharekey" @click="meetingMini = true">{{$L('最小化')}}</Button>
                    <Button type="warning" :loading="loadIng > 0" @click="onClose">{{$L('离开会议')}}</Button>
                </template>
            </div>
        </Modal>
        <DragBallComponent
            v-if="meetingMini"
            id="meetingDragBall"
            :z-index="3000"
            @on-click="meetingMini=false">
            <div class="meeting-drag-ball">
                <i class="taskfont" v-html="localUser.audioTrack ? '&#xe7c3;' : '&#xe7c7;'"></i>
                <i class="taskfont" v-html="localUser.videoTrack ? '&#xe7c1;' : '&#xe7c8;'"></i>
                <em>{{$L('会议中')}}</em>
            </div>
        </DragBallComponent>

        <!-- 邀请 -->
        <Modal
            v-model="invitationShow"
            :title="$L('邀请加入')"
            :mask-closable="false">
            <Form ref="invitationForm" :model="invitationData" label-width="auto" @submit.native.prevent>
                <FormItem prop="userids" :label="$L('邀请成员')">
                    <UserSelect v-model="invitationData.userids" :multiple-max="20" :title="$L('选择邀请成员')"/>
                </FormItem>
            </Form>
            <div slot="footer" class="adaption">
                <Button type="default" :loading="linkCopyLoad" @click="linkCopy">{{$L('复制链接')}}</Button>
                <Button type="primary" :loading="invitationLoad" @click="onInvitation('submit')">{{$L('发送邀请')}}</Button>
            </div>
        </Modal>
    </div>
</template>

<script>
import {Store} from "le5le-store";
import {mapState} from 'vuex'
import MeetingPlayer from "./MeetingPlayer.vue";
import DragBallComponent from "../../../components/DragBallComponent";
import UserSelect from "../../../components/UserSelect.vue";

export default {
    name: "MeetingManager",
    components: {UserSelect, DragBallComponent, MeetingPlayer},
    props: {
        id: {
            type: String,
            default: () => {
                return  "meeting-player-" + Math.round(Math.random() * 10000);
            }
        }
    },
    data() {
        return {
            loadIng: 0,
            subscribe: null,

            addShow: false,
            addData: {
                userids: [],
                tracks: ['audio']
            },
            addRule: {
                username: [
                    { required: true, message: this.$L('请输入你的姓名！'), trigger: 'change' },
                ]
            },

            invitationShow: false,
            invitationLoad: false,
            invitationData: {
                userids: [],
            },

            meetingShow: false,
            meetingMini: false,
            audioLoad: false,
            videoLoad: false,

            agoraClient: null,
            remoteUsers: [],
            localUser: {
                uid: null,
                audioTrack: null,
                videoTrack: null,
            },

            linkCopyLoad: false,
        }
    },

    computed: {
        ...mapState(['meetingWindow']),
    },

    mounted() {
        this.subscribe = Store.subscribe('addMeeting', this.onAdd);
    },

    beforeDestroy() {
        if (this.subscribe) {
            this.subscribe.unsubscribe();
            this.subscribe = null;
        }
    },

    watch: {
        meetingMini(val) {
            if (!val) {
                this.$refs.meetingModal.modalIndex = this.$refs.meetingModal.handleGetModalIndex()
            }
        },
        meetingWindow: {
            handler(val) {
                switch (val.type) {
                    case 'add':
                        this.addShow = val.show;
                        this.loadIng = 0;
                        break;
                    case 'join':
                        this.addShow = val.show;
                        this.loadIng = 0;
                        this.addData.type  = 'join';
                        if(val.meetingSharekey){
                            this.addData.sharekey  = val.meetingSharekey;
                            this.addData.meetingid  = val.meetingid || '';
                            this.addData.meetingdisabled  = val.meetingSharekey ? true : false;
                        }
                        break;
                    case 'invitation':
                        this.invitationShow = val.show;
                        this.invitationLoad = false;
                        this.invitationData.meetingid = val.meetingid;
                        break;
                    case 'error':
                        this.addShow = val.show;
                        this.loadIng = 0;
                        this.invitationShow = val.show;
                        this.invitationLoad = false;
                        $A.modalError('加入会议失败');
                        break;
                }
            },
            immediate: true,
        }
    },

    methods: {
        onAdd(data) {
            data = $A.isJson(data) ? data : {};
            // 获取会话成员
            if (/^\d+$/.test(data.dialog_id)) {
                this.loadIng++;
                this.$store.dispatch("call", {
                    url: 'dialog/user',
                    data: {
                        dialog_id: data.dialog_id
                    }
                }).then(({data}) => {
                    this.$set(this.addData, 'userids', data.filter(item => !item.bot).map(item => item.userid))
                }).finally(_ => {
                    this.loadIng--;
                });
                delete data.dialog_id;
            }
            // 加上自己
            if (!$A.isArray(data.userids)) {
                data.userids = [this.userId]
            } else if (!data.userids.includes(this.userId)) {
                data.userids.push(this.userId)
            }
            // 加上音频
            if (!$A.isArray(data.tracks)) {
                data.tracks = ['audio']
            } else if (!data.tracks.includes('audio')) {
                data.tracks.push('audio')
            }
            this.addData = data;
            this.addShow = true;
        },

        onSubmit() {
            if (this.meetingShow) {
                $A.modalWarning("正在会议中，无法进入其他会议室");
                return;
            }
            this.$refs.addForm.validate((valid) => {
                if (valid) {
                    this.loadIng++;
                    this.$store.dispatch("call", {
                        url: 'users/meeting/open',
                        data: this.addData
                    }).then(({data}) => {
                        this.$set(this.addData, 'name', data.name);
                        this.$set(this.addData, 'meetingid', data.meetingid);
                        this.$set(this.localUser, 'nickname', data.nickname);
                        this.$set(this.localUser, 'userimg', data.userimg);
                        this.$store.dispatch("saveDialogMsg", data.msgs);
                        this.$store.dispatch("updateDialogLastMsg", data.msgs);
                        delete data.name;
                        delete data.msgs;
                        //
                        if ($A.isEEUiApp) {
                            $A.eeuiAppSendMessage({
                                action: 'startMeeting',
                                meetingParams: {
                                    name: this.addData.name,
                                    token: data.token,
                                    channel: data.channel,
                                    uuid: data.uid,
                                    appid: data.appid,
                                    avatar: data.userimg,
                                    username: data.nickname,
                                    video: this.addData.tracks.includes("video"),
                                    audio: this.addData.tracks.includes("audio"),
                                    meetingid: data.meetingid,
                                    sharelink: data.sharelink,
                                    alert: {
                                        title: this.$L('温馨提示'),
                                        message: this.$L('确定要离开会议吗？'),
                                        cancel: this.$L('继续'),
                                        confirm: this.$L('退出'),
                                    }
                                }
                            });
                        } else {
                            $A.loadScript('js/AgoraRTC_N-4.17.0.js').then(_ => {
                                this.join(data)
                            }).catch(_ => {
                                $A.modalError("会议组件加载失败！");
                            }).finally(_ => {
                                this.loadIng--;
                            })
                        }
                    }).catch(({msg}) => {
                        this.loadIng--;
                        $A.modalError(msg);
                    });
                }

            });
        },

        onAudio() {
            if (this.localUser.audioTrack) {
                this.closeAudio();
            } else {
                this.openAudio();
            }
        },

        onVideo() {
            if (this.localUser.videoTrack) {
                this.closeVideo();
            } else {
                this.openVideo();
            }
        },

        onInvitation(type) {
            if (type === 'open') {
                if(this.addData.sharekey){
                    this.linkCopy();
                    return;
                }
                this.invitationData = {
                    userids: [],
                    meetingid: this.addData.meetingid
                };
                this.invitationShow = true;
            } else if (type === 'submit') {
                this.invitationLoad = true;
                this.$store.dispatch("call", {
                    url: 'users/meeting/invitation',
                    data: this.invitationData
                }).then(({data, msg}) => {
                    this.invitationShow = false;
                    this.$store.dispatch("saveDialogMsg", data.msgs);
                    this.$store.dispatch("updateDialogLastMsg", data.msgs);
                    $A.messageSuccess(msg);
                }).catch(({msg}) => {
                    $A.modalError(msg);
                }).finally(_ => {
                    this.invitationLoad = false;
                });
            }
        },

        onClose() {
            return new Promise(resolve => {
                $A.modalConfirm({
                    content: '确定要离开会议吗？',
                    cancelText: '继续',
                    okText: '退出',
                    onOk: async _ => {
                        await this.leave()
                        if(this.addData.sharekey){
                            this.addShow = true;
                            this.loadIng = 0;
                        }
                        resolve()
                    }
                });
            })
        },

        async join(options) {
            this.loadIng++;
            // 音频采集设备状态变化回调
            AgoraRTC.onMicrophoneChanged = async (changedDevice) => {
                // When plugging in a device, switch to a device that is newly plugged in.
                if (changedDevice.state === "ACTIVE") {
                    this.localUser.audioTrack?.setDevice(changedDevice.device.deviceId);
                    // Switch to an existing device when the current device is unplugged.
                } else if (changedDevice.device.label === this.localUser.audioTrack?.getTrackLabel()) {
                    const oldMicrophones = await AgoraRTC.getMicrophones();
                    oldMicrophones[0] && this.localUser.audioTrack?.setDevice(oldMicrophones[0].deviceId);
                }
            }
            // 视频采集设备状态变化回调
            AgoraRTC.onCameraChanged = async (changedDevice) => {
                // When plugging in a device, switch to a device that is newly plugged in.
                if (changedDevice.state === "ACTIVE") {
                    this.localUser.videoTrack?.setDevice(changedDevice.device.deviceId);
                    // Switch to an existing device when the current device is unplugged.
                } else if (changedDevice.device.label === this.localUser.videoTrack?.getTrackLabel()) {
                    const oldCameras = await AgoraRTC.getCameras();
                    oldCameras[0] && this.localUser.videoTrack?.setDevice(oldCameras[0].deviceId);
                }
            }
            // 音频或视频轨道自动播放失败回调
            AgoraRTC.onAutoplayFailed = () => {
                $A.messageWarning("点击屏幕开始会议");
            }
            // 设置日志级别
            AgoraRTC.setLogLevel(window.systemInfo.debug === "yes" ? 0 : 3);

            // 创建客户端
            this.agoraClient = AgoraRTC.createClient({mode: "rtc", codec: "vp8"});
            // 添加事件侦听器
            this.agoraClient.on("user-joined", this.handleUserJoined);
            this.agoraClient.on("user-left", this.handleUserLeft);
            this.agoraClient.on("user-published", this.handleUserPublished);
            this.agoraClient.on("user-unpublished", this.handleUserUnpublished);
            // 加入频道、开启音视频
            const localTracks = [];
            try {
                this.localUser.uid = await this.agoraClient.join(options.appid, options.channel, options.token, options.uid)
                if (this.addData.tracks.includes("audio")) {
                    localTracks.push(this.localUser.audioTrack = await AgoraRTC.createMicrophoneAudioTrack())
                }
                if (this.addData.tracks.includes("video")) {
                    localTracks.push(this.localUser.videoTrack = await AgoraRTC.createCameraVideoTrack())
                }
                // 将本地视频曲目播放到本地浏览器、将本地音频和视频发布到频道。
                if (localTracks.length > 0) {
                    await this.agoraClient.publish(localTracks);
                }
                //
                this.meetingShow = true;
            } catch (error) {
                console.error(error)
                $A.modalError("会议组件加载失败！");
            }
            this.loadIng--;
            this.addShow = false;
        },

        async leave() {
            this.loadIng++;
            // 删除本地用户和播放器视图。
            ['audioTrack', 'videoTrack'].some(trackName => {
                this.localUser[trackName]?.stop();
                this.localUser[trackName]?.close();
            })
            this.localUser = {
                uid: null,
                audioTrack: null,
                videoTrack: null,
            }
            // 删除远程用户和播放器视图。
            this.remoteUsers = [];
            // 离开频道
            await this.agoraClient.leave();
            //
            this.loadIng--;
            this.meetingShow = false;
        },

        async openAudio() {
            if (this.audioLoad || this.localUser.audioTrack) return;
            this.audioLoad = true;
            this.localUser.audioTrack = await AgoraRTC.createMicrophoneAudioTrack()
            await this.agoraClient.publish([this.localUser.audioTrack]);
            this.audioLoad = false;
        },

        async closeAudio() {
            if (this.audioLoad || !this.localUser.audioTrack) return;
            this.audioLoad = true;
            await this.agoraClient.unpublish([this.localUser.audioTrack]);
            this.localUser.audioTrack.stop();
            this.localUser.audioTrack.close();
            this.localUser.audioTrack = null;
            this.audioLoad = false;
        },

        async openVideo() {
            if (this.videoLoad || this.localUser.videoTrack) return;
            this.videoLoad = true;
            this.localUser.videoTrack = await AgoraRTC.createCameraVideoTrack()
            await this.agoraClient.publish([this.localUser.videoTrack]);
            this.videoLoad = false;
        },

        async closeVideo() {
            if (this.videoLoad || !this.localUser.videoTrack) return;
            this.videoLoad = true;
            await this.agoraClient.unpublish([this.localUser.videoTrack]);
            this.localUser.videoTrack.stop();
            this.localUser.videoTrack.close();
            this.localUser.videoTrack = null;
            this.videoLoad = false;
        },

        async handleUserJoined(user) {
            const index = this.remoteUsers.findIndex(item => item.uid == user.uid)
            if (index > -1) {
                this.remoteUsers.splice(index, 1, user)
            } else {
                this.remoteUsers.push(user)
            }
        },

        async handleUserLeft(user) {
            const index = this.remoteUsers.findIndex(item => item.uid == user.uid)
            if (index > -1) {
                this.remoteUsers.splice(index, 1)
            }
        },

        async handleUserPublished(user, mediaType) {
            const item = this.remoteUsers.find(item => item.uid == user.uid)
            if (item) {
                await this.agoraClient.subscribe(user, mediaType);
            }
        },

        async handleUserUnpublished(user, mediaType) {
            const item = this.remoteUsers.find(item => item.uid == user.uid)
            if (item) {
                await this.agoraClient.unsubscribe(user, mediaType);
            }
        },

        linkCopy() {
            this.linkCopyLoad = true;
            this.$store.dispatch("call", {
                url: 'users/meeting/link',
                data: {
                    meetingid: this.addData.meetingid || this.invitationData.meetingid,
                    sharekey: this.addData.sharekey
                },
            }).then(({ data }) => {
                this.copyText({
                    text: data,
                    success: '已复制会议邀请链接',
                    error: "复制失败"
                });
                this.invitationShow = false;
            }).catch(({ msg }) => {
                $A.modalError(msg);
            }).finally(_ => {
                this.linkCopyLoad = false;
            });
        },
    }
}
</script>
