<template>
    <div class="message-view" :data-id="msgData.id">

        <!--文本-->
        <div v-if="msgData.type === 'text'" class="message-content" v-html="textMsg(msgInfo.text)"></div>
        <!--等待-->
        <div v-else-if="msgData.type === 'loading'" class="message-content loading"><Loading/></div>
        <!--文件-->
        <div v-else-if="msgData.type === 'file'" :class="['message-content', msgInfo.type]">
            <a :href="msgInfo.url" target="_blank">
                <img v-if="msgInfo.type === 'img'" class="file-img" :style="imageStyle(msgInfo)" :src="msgInfo.thumb"/>
                <div v-else class="file-box">
                    <img class="file-thumb" :src="msgInfo.thumb"/>
                    <div class="file-info">
                        <div class="file-name">{{msgInfo.name}}</div>
                        <div class="file-size">{{$A.bytesToSize(msgInfo.size)}}</div>
                    </div>
                </div>
            </a>
        </div>
        <!--未知-->
        <div v-else class="message-content unknown">{{$L("未知的消息类型")}}</div>

        <!--时间/阅读-->
        <div v-if="msgData.created_at" class="message-foot">
            <div class="time">{{formatTime(msgData.created_at)}}</div>
            <Poptip
                v-if="msgData.send > 1 || dialogType == 'group'"
                class="percent"
                placement="left-end"
                transfer
                :width="360"
                :offset="8"
                @on-popper-show="popperShow">
                <div slot="content" class="message-readbox">
                    <ul class="read">
                        <li class="read-title"><em>{{readList.length}}</em>{{$L('已读')}}</li>
                        <li v-for="item in readList"><UserAvatar :userid="item.userid" :size="26" show-name/></li>
                    </ul>
                    <ul class="unread">
                        <li class="read-title"><em>{{unreadList.length}}</em>{{$L('未读')}}</li>
                        <li v-for="item in unreadList"><UserAvatar :userid="item.userid" :size="26" show-name/></li>
                    </ul>
                </div>
                <WCircle :percent="msgData.percentage" :size="14"/>
            </Poptip>
            <Icon v-else-if="msgData.percentage === 100" class="done" type="md-done-all"/>
            <Icon v-else class="done" type="md-checkmark"/>
        </div>
        <div v-else class="message-foot"><Loading/></div>

    </div>
</template>

<script>
import {mapState} from "vuex";
import WCircle from "../../../components/WCircle";

export default {
    name: "MessageView",
    components: {WCircle},
    props: {
        msgData: {
            type: Object,
            default: () => {
                return {};
            }
        },
        dialogType: {
            type: String,
            default: ''
        },
    },

    data() {
        return {
            msgInfo: {},
            read_list: []
        }
    },

    mounted() {
        this.parsingData()
    },

    computed: {
        ...mapState(['userId']),

        readList() {
            return this.read_list.filter(({read_at}) => read_at)
        },

        unreadList() {
            return this.read_list.filter(({read_at}) => !read_at)
        }
    },

    watch: {
        msgData() {
            this.parsingData()
        }
    },

    methods: {
        popperShow() {
            $A.apiAjax({
                url: 'dialog/msg/readlist',
                data: {
                    msg_id: this.msgData.id,
                },
                success: ({ret, data, msg}) => {
                    if (ret === 1) {
                        this.read_list = data;
                    }
                }
            });
        },

        parsingData() {
            this.msgInfo = this.msgData.msg;
            //
            const {userid, r, id} = this.msgData;
            if (userid == this.userId) return;
            if ($A.isJson(r) && r.read_at) return;
            this.$store.commit('wsSend', {
                type: 'readMsg',
                data: {id}
            });
        },

        formatTime(date) {
            let time = Math.round(new Date(date).getTime() / 1000),
                string = '';
            if ($A.formatDate('Ymd') === $A.formatDate('Ymd', time)) {
                string = $A.formatDate('H:i', time)
            } else if ($A.formatDate('Y') === $A.formatDate('Y', time)) {
                string = $A.formatDate('m-d', time)
            } else {
                string = $A.formatDate('Y-m-d', time)
            }
            return string || '';
        },

        textMsg(text) {
            if (!text) {
                return ""
            }
            text = text.trim().replace(/(\n\x20*){3,}/g, "<br/><br/>");
            text = text.trim().replace(/\n/g, "<br/>");
            return text;
        },

        imageStyle(info) {
            const {width, height} = info;
            if (width && height) {
                let maxWidth = 220,
                    maxHeight = 220,
                    tempWidth = width,
                    tempHeight = height;
                if (width > maxWidth || height > maxHeight) {
                    if (width > height) {
                        tempWidth = maxWidth;
                        tempHeight = height * (maxWidth / width);
                    } else {
                        tempWidth = width * (maxHeight / height);
                        tempHeight = maxHeight;
                    }
                }
                return {
                    width: tempWidth + 'px',
                    height: tempHeight + 'px',
                };
            }
            return {};
        }
    }
}
</script>
