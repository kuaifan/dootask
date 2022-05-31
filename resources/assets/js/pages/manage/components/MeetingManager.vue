<template>
    <div v-show="false">
        <Modal
            v-model="addShow"
            :title="$L('新会议')"
            :mask-closable="false">
            <Form ref="addForm" :model="addData" :rules="addRule" label-width="auto" @submit.native.prevent>
                <FormItem prop="userids" :label="$L('会议成员')">
                    <UserInput v-model="addData.userids" :multiple-max="10" :placeholder="$L('选择会议成员')"/>
                </FormItem>
                <FormItem prop="video" :label="$L('开启视频')">
                    <RadioGroup v-model="addData.video">
                        <Radio label="open">{{$L('开启')}}</Radio>
                        <Radio label="close">{{$L('关闭')}}</Radio>
                    </RadioGroup>
                </FormItem>
            </Form>
            <div slot="footer" class="adaption">
                <Button type="default" @click="addShow=false">{{$L('取消')}}</Button>
                <Button type="primary" :loading="loadIng > 0" @click="onSubmit">{{$L('开始会议')}}</Button>
            </div>
        </Modal>
        <Modal
            v-model="meetingShow"
            :title="$L('会议中')"
            :mask="false"
            :mask-closable="false"
            :closable="false"
            :transition-names="['', '']"
            class-name="meeting-manager"
            fullscreen>
            <ul>
                <li v-if="localTracks.uid">
                    <MeetingPlayer :player="localTracks"/>
                </li>
                <li v-for="item in remoteUsers">
                    <MeetingPlayer :player="item"/>
                </li>
            </ul>
            <div slot="footer" class="adaption">
                <Button type="warning" :loading="loadIng > 0" @click="onClose">{{$L('退出会议')}}</Button>
            </div>
        </Modal>
    </div>
</template>

<script>
import UserInput from "../../../components/UserInput";
import {Store} from "le5le-store";
import {mapState} from "vuex";
import MeetingPlayer from "./MeetingPlayer";

export default {
    name: "MeetingManager",
    components: {MeetingPlayer, UserInput},
    data() {
        return {
            loadIng: 0,
            subscribe: null,

            addShow: false,
            addData: {
                userids: [],
                video: 'close'
            },
            addRule: {},

            meetingShow: false,

            agoraClient: null,
            remoteUsers: [],
            localTracks: {
                uid: null,
                mediaType: null,
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

    computed: {
        ...mapState(['userId'])
    },

    methods: {
        onAdd(data) {
            this.addData = Object.assign({}, this.addData, $A.isJson(data) ? data : {
                'userids': [this.userId],
            });
            this.addShow = true;
        },

        onSubmit() {
            this.$refs.addForm.validate((valid) => {
                if (valid) {
                    this.loadIng++;
                    this.$store.dispatch("call", {
                        url: 'users/agoraio/token',
                    }).then(({data}) => {
                        $A.loadScript('//download.agora.io/sdk/release/AgoraRTC_N.js', e => {
                            if (e !== null || typeof AgoraRTC !== 'object') {
                                this.loadIng--;
                                $A.modalError("会议组件加载失败！");
                                return;
                            }
                            this.join(data).then(_ => {
                                this.loadIng--;
                                this.addShow = false;
                                this.meetingShow = true;
                            })
                        });
                    }).catch(({msg}) => {
                        this.loadIng--;
                        $A.modalError(msg);
                    });
                }

            });
        },

        onClose() {
            $A.modalConfirm({
                content: '确定要退出会议吗？',
                cancelText: '继续',
                okText: '退出',
                onOk: () => {
                    this.loadIng++;
                    this.leave().then(_ => {
                        this.loadIng--;
                        this.meetingShow = false;
                    })
                }
            });
        },

        join(options) {
            return new Promise(async resolve => {
                AgoraRTC.onAutoplayFailed = () => {
                    // alert("click to start autoplay!")
                }
                AgoraRTC.onMicrophoneChanged = async (changedDevice) => {
                    // When plugging in a device, switch to a device that is newly plugged in.
                    if (changedDevice.state === "ACTIVE") {
                        this.localTracks.audioTrack.setDevice(changedDevice.device.deviceId);
                        // Switch to an existing device when the current device is unplugged.
                    } else if (changedDevice.device.label === this.localTracks.audioTrack.getTrackLabel()) {
                        const oldMicrophones = await AgoraRTC.getMicrophones();
                        oldMicrophones[0] && this.localTracks.audioTrack.setDevice(oldMicrophones[0].deviceId);
                    }
                }
                AgoraRTC.onCameraChanged = async (changedDevice) => {
                    // When plugging in a device, switch to a device that is newly plugged in.
                    if (changedDevice.state === "ACTIVE") {
                        this.localTracks.videoTrack.setDevice(changedDevice.device.deviceId);
                        // Switch to an existing device when the current device is unplugged.
                    } else if (changedDevice.device.label === this.localTracks.videoTrack.getTrackLabel()) {
                        const oldCameras = await AgoraRTC.getCameras();
                        oldCameras[0] && this.localTracks.videoTrack.setDevice(oldCameras[0].deviceId);
                    }
                }
                //
                this.agoraClient = AgoraRTC.createClient({
                    mode: "rtc",
                    codec: "vp8"
                });
                // Add an event listener to play remote tracks when remote user publishes.
                this.agoraClient.on("user-published", this.handleUserPublished);
                this.agoraClient.on("user-unpublished", this.handleUserUnpublished);
                // Join a channel and create local tracks. Best practice is to use Promise.all and run them concurrently.
                [options.uid, this.localTracks.audioTrack, this.localTracks.videoTrack] = await Promise.all([
                    // Join the channel.
                    this.agoraClient.join(options.appid, options.channel, options.token || null, options.uid || null),
                    // Create tracks to the local microphone and camera.
                    AgoraRTC.createMicrophoneAudioTrack(),
                    AgoraRTC.createCameraVideoTrack()
                ]);
                // Play the local video track to the local browser and update the UI with the user ID.
                this.localTracks.uid = options.uid;
                this.localTracks.mediaType = 'video';
                // Publish the local video and audio tracks to the channel.
                await this.agoraClient.publish([this.localTracks.audioTrack, this.localTracks.videoTrack]);
                resolve()
            })
        },

        leave() {
            return new Promise(async resolve => {
                for (let trackName in this.localTracks) {
                    const track = this.localTracks[trackName];
                    if (track) {
                        if (['audioTrack', 'videoTrack'].includes(trackName)) {
                            track.stop();
                            track.close();
                        }
                        this.localTracks[trackName] = null;
                    }
                }
                // Remove remote users and player views.
                this.remoteUsers = [];
                // leave the channel
                await this.agoraClient.leave();
                resolve();
            })
        },

        async handleUserPublished(user, mediaType) {
            // subscribe to a remote user
            await this.agoraClient.subscribe(user, mediaType);
            // add remote
            user.mediaType = mediaType
            const index = this.remoteUsers.findIndex(item => item.uid == user.uid)
            if (index > -1) {
                this.remoteUsers.splice(index, 1, user)
            } else {
                this.remoteUsers.push(user)
            }
        },

        handleUserUnpublished(user) {
            const index = this.remoteUsers.findIndex(item => item.uid == user.uid)
            if (index > -1) {
                this.remoteUsers.splice(index, 1)
            }
        }
    }
}
</script>
