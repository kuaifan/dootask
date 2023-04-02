<template>
    <div class="meeting-warp">
        <!-- 加入/新建 -->
        <Modal
            v-model="addShow"
            :title="$L(addData.type === 'join' ? '加入会议' : '新会议')"
            :mask-closable="false">
            <Form ref="addForm" :model="addData" label-width="auto" @submit.native.prevent>
                <template v-if="addData.type === 'join'">
                    <!-- 加入会议 -->
                    <FormItem v-if="addData.name" prop="userids" :label="$L('会议主题')">
                        <Input v-model="addData.name" disabled/>
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
                        <UserInput v-model="addData.userids" :uncancelable="[userId]" :multiple-max="20" :placeholder="$L('选择邀请成员')"/>
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
                <Button type="default" @click="addShow=false">{{$L('取消')}}</Button>
                <Button type="primary" :loading="loadIng > 0" @click="onSubmit">{{$L(addData.type === 'join' ? '加入会议' : '开始会议')}}</Button>
            </div>
        </Modal>

        <!-- 会议中 -->
        <Modal
            v-model="meetingShow"
            :title="addData.name"
            :mask="false"
            :mask-closable="false"
            :closable="false"
            :transition-names="['', '']"
            :beforeClose="onClose"
            :class-name="`meeting-manager${meetingMini ? ' meeting-hidden' : ''}`"
            fullscreen>
            <ul>
                <li v-if="localUser.uid">
                    <MeetingPlayer :player="localUser" isLocal/>
                </li><li v-for="user in remoteUsers">
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
                <template v-if="windowSmall">
                    <Button type="primary" @click="onInvitation('open')">
                        <i class="taskfont">&#xe646;</i>
                    </Button>
                    <Button type="primary" @click="meetingMini = true">
                        <i class="taskfont">&#xe656;</i>
                    </Button>
                    <Button type="warning" :loading="loadIng > 0" @click="onClose">
                        <i class="taskfont">&#xe612;</i>
                    </Button>
                </template>
                <template v-else>
                    <Button type="primary" @click="onInvitation('open')">{{$L('邀请')}}</Button>
                    <Button type="primary" @click="meetingMini = true">{{$L('最小化')}}</Button>
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
                    <UserInput v-model="invitationData.userids" :multiple-max="20" :placeholder="$L('选择邀请成员')"/>
                </FormItem>
            </Form>
            <div slot="footer" class="adaption">
                <Button type="default" @click="invitationShow=false">{{$L('取消')}}</Button>
                <Button type="primary" :loading="invitationLoad" @click="onInvitation('submit')">{{$L('发送邀请')}}</Button>
            </div>
        </Modal>
    </div>
</template>

<script>
import UserInput from "../../../components/UserInput";
import {Store} from "le5le-store";
import MeetingPlayer from "./MeetingPlayer";
import DragBallComponent from "../../../components/DragBallComponent";

export default {
    name: "MeetingManager",
    components: {DragBallComponent, MeetingPlayer, UserInput},
    data() {
        return {
            loadIng: 0,
            subscribe: null,

            addShow: false,
            addData: {
                userids: [],
                tracks: ['audio']
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
        }
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
        meetingShow(val) {
            if (val) {
                $A.eeuiAppKeepScreenOn()
            } else {
                $A.eeuiAppKeepScreenOff()
            }
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
                    this.$set(this.addData, 'userids', data.map(item => item.userid))
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
            this.$refs.addForm.validate((valid) => {
                if (valid) {
                    this.loadIng++;
                    this.$store.dispatch("call", {
                        url: 'users/meeting/open',
                        data: this.addData
                    }).then(({data}) => {
                        this.$set(this.addData, 'name', data.name);
                        this.$set(this.addData, 'meetingid', data.meetingid);
                        this.$store.dispatch("saveDialogMsg", data.msgs);
                        this.$store.dispatch("updateDialogLastMsg", data.msgs);
                        delete data.name;
                        delete data.msgs;
                        //
                        $A.loadScript('js/AgoraRTC_N-4.17.0.js').then(_ => {
                            this.join(data)
                        }).catch(_ => {
                            $A.modalError("会议组件加载失败！");
                        }).finally(_ => {
                            this.loadIng--;
                        })
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
                    this.localUser.audioTrack.setDevice(changedDevice.device.deviceId);
                    // Switch to an existing device when the current device is unplugged.
                } else if (changedDevice.device.label === this.localUser.audioTrack.getTrackLabel()) {
                    const oldMicrophones = await AgoraRTC.getMicrophones();
                    oldMicrophones[0] && this.localUser.audioTrack.setDevice(oldMicrophones[0].deviceId);
                }
            }
            // 视频采集设备状态变化回调
            AgoraRTC.onCameraChanged = async (changedDevice) => {
                // When plugging in a device, switch to a device that is newly plugged in.
                if (changedDevice.state === "ACTIVE") {
                    this.localUser.videoTrack.setDevice(changedDevice.device.deviceId);
                    // Switch to an existing device when the current device is unplugged.
                } else if (changedDevice.device.label === this.localUser.videoTrack.getTrackLabel()) {
                    const oldCameras = await AgoraRTC.getCameras();
                    oldCameras[0] && this.localUser.videoTrack.setDevice(oldCameras[0].deviceId);
                }
            }
            // 音频或视频轨道自动播放失败回调
            AgoraRTC.onAutoplayFailed = () => {
                $A.messageWarning("点击屏幕开始会议");
            }

            // 创建客户端
            this.agoraClient = AgoraRTC.createClient({mode: "rtc", codec: "vp8"});
            // 添加事件侦听器
            this.agoraClient.on("user-joined", this.handleUserJoined);
            this.agoraClient.on("user-left", this.handleUserLeft);
            this.agoraClient.on("user-published", this.handleUserPublished);
            this.agoraClient.on("user-unpublished", this.handleUserUnpublished);
            // 加入频道、开启音视频
            const localTracks = [];
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
            this.loadIng--;
            this.addShow = false;
            this.meetingShow = true;
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
        }
    }
}
</script>
