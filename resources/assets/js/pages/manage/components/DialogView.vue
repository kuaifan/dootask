<template>
    <div class="dialog-view" :data-id="msgData.id">

        <!--文本-->
        <div v-if="msgData.type === 'text'" class="dialog-content" v-html="textMsg(msgData.msg.text)"></div>
        <!--等待-->
        <div v-else-if="msgData.type === 'loading'" class="dialog-content loading"><Loading/></div>
        <!--文件-->
        <div v-else-if="msgData.type === 'file'" :class="['dialog-content', msgData.msg.type]">
            <a :href="msgData.msg.url" target="_blank">
                <img v-if="msgData.msg.type === 'img'" class="file-img" :style="imageStyle(msgData.msg)" :src="msgData.msg.thumb"/>
                <div v-else class="file-box">
                    <img class="file-thumb" :src="msgData.msg.thumb"/>
                    <div class="file-info">
                        <div class="file-name">{{msgData.msg.name}}</div>
                        <div class="file-size">{{$A.bytesToSize(msgData.msg.size)}}</div>
                    </div>
                </div>
            </a>
        </div>
        <!--未知-->
        <div v-else class="dialog-content unknown">{{$L("未知的消息类型")}}</div>

        <!--时间/阅读-->
        <div v-if="msgData.created_at" class="dialog-foot">
            <div class="time">{{formatTime(msgData.created_at)}}</div>
            <Poptip
                v-if="msgData.send > 1 || dialogType == 'group'"
                class="percent"
                placement="left-end"
                transfer
                :width="360"
                :offset="8"
                @on-popper-show="popperShow">
                <div slot="content" class="dialog-wrapper-read-poptip-content">
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
        <div v-else class="dialog-foot"><Loading/></div>

    </div>
</template>

<script>
import {mapState} from "vuex";
import WCircle from "../../../components/WCircle";

export default {
    name: "DialogView",
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
            read_list: []
        }
    },

    mounted() {
        this.msgRead()
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

    methods: {
        msgRead() {
            this.$store.commit('wsMsgRead', this.msgData);
        },

        popperShow() {
            this.$store.dispatch("call", {
                url: 'dialog/msg/readlist',
                data: {
                    msg_id: this.msgData.id,
                },
            }).then((data, msg) => {
                this.read_list = data;
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
                let maxW = 220,
                    maxH = 220,
                    tempW = width,
                    tempH = height;
                if (width > maxW || height > maxH) {
                    if (width > height) {
                        tempW = maxW;
                        tempH = height * (maxW / width);
                    } else {
                        tempW = width * (maxH / height);
                        tempH = maxH;
                    }
                }
                return {
                    width: tempW + 'px',
                    height: tempH + 'px',
                };
            }
            return {};
        }
    }
}
</script>
