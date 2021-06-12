<template>
    <div :class="['task-detail', projectOpenTask._dialog || projectOpenTask._msgText ? 'open-dialog' : '']">
        <div class="task-info">
            <div class="head">
                <Icon class="icon" type="md-radio-button-off"/>
                <div class="nav">
                    <p>项目名称</p>
                    <p>列表名称</p>
                    <p>2222</p>
                </div>
                <Icon class="menu" type="ios-more"/>
            </div>
            <div class="scroller overlay-y" :style="scrollerStyle">
                <div class="title">
                    <Input
                        v-model="projectOpenTask.name"
                        type="textarea"
                        :rows="1"
                        :autosize="{ minRows: 1, maxRows: 8 }"
                        :maxlength="255"/>
                </div>
                <div class="desc">
                    <TEditor
                        v-model="content"
                        :plugins="taskPlugins"
                        :options="taskOptions"
                        :option-full="taskOptionFull"
                        :placeholder="$L('详细描述...')"
                        inline></TEditor>
                </div>
                <Form class="items" label-position="left" label-width="auto" @submit.native.prevent>
                    <FormItem>
                        <div class="item-label" slot="label">
                            <i class="iconfont">&#xe6ec;</i>{{$L('优先级')}}
                        </div>
                        <ul class="item-content">
                            <li>紧急且重要</li>
                        </ul>
                    </FormItem>
                    <FormItem>
                        <div class="item-label" slot="label">
                            <i class="iconfont">&#xe6e4;</i>{{$L('负责人')}}
                        </div>
                        <ul class="item-content user">
                            <li><UserAvatar :userid="1"/></li>
                        </ul>
                    </FormItem>
                    <FormItem>
                        <div class="item-label" slot="label">
                            <i class="iconfont">&#xe6e8;</i>{{$L('截止时间')}}
                        </div>
                        <ul class="item-content">
                            <li>2020/10/11 10:00</li>
                        </ul>
                    </FormItem>
                    <FormItem>
                        <div class="item-label" slot="label">
                            <i class="iconfont">&#xe6e6;</i>{{$L('附件')}}
                        </div>
                        <ul class="item-content file">
                            <li>
                                <img class="file-ext" :src="'/images/ext/psd.png'"/>
                                <div class="file-name">附件名称.psd</div>
                                <div class="file-size">20.5kb</div>
                            </li>
                            <li>
                                <img class="file-ext" :src="'/images/ext/xls.png'"/>
                                <div class="file-name">附件名称.xls</div>
                                <div class="file-size">20.5kb</div>
                            </li>
                            <li>
                                <img class="file-ext" :src="'/images/ext/doc.png'"/>
                                <div class="file-name">附件名称.doc</div>
                                <div class="file-size">20.5kb</div>
                            </li>
                            <li>
                                <div class="add-button">
                                    <i class="iconfont">&#xe6f2;</i>{{$L('添加附件')}}
                                </div>
                            </li>
                        </ul>
                    </FormItem>
                    <FormItem>
                        <div class="item-label" slot="label">
                            <i class="iconfont">&#xe6f0;</i>{{$L('子任务')}}
                        </div>
                        <ul class="item-content subtask">
                            <li>
                                <Icon class="subtask-icon" type="md-radio-button-off" />
                                <div class="subtask-name">
                                    <Input
                                        v-model="projectOpenTask.name"
                                        type="textarea"
                                        :rows="1"
                                        :autosize="{ minRows: 1, maxRows: 8 }"
                                        :maxlength="255"/>
                                </div>
                                <div class="subtask-time today">{{expiresFormat('2021-06-12')}}</div>
                                <UserAvatar class="subtask-avatar" :userid="1" :size="20"/>
                            </li>
                            <li>
                                <Icon class="subtask-icon" type="md-radio-button-off" />
                                <div class="subtask-name">
                                    <Input
                                        v-model="projectOpenTask.name"
                                        type="textarea"
                                        :rows="1"
                                        :autosize="{ minRows: 1, maxRows: 8 }"
                                        :maxlength="255"/>
                                </div>
                                <div class="subtask-time overdue">{{expiresFormat('2021-06-11')}}</div>
                                <UserAvatar class="subtask-avatar" :userid="1" :size="20"/>
                            </li>
                            <li>
                                <Icon class="subtask-icon" type="md-radio-button-off" />
                                <div class="subtask-name">
                                    <Input
                                        v-model="projectOpenTask.name"
                                        type="textarea"
                                        :rows="1"
                                        :autosize="{ minRows: 1, maxRows: 8 }"
                                        :maxlength="255"/>
                                </div>
                                <div class="subtask-time">{{expiresFormat('2021-06-12')}}</div>
                                <UserAvatar class="subtask-avatar" :userid="1" :size="20"/>
                            </li>
                            <li>
                                <div class="add-button">
                                    <i class="iconfont">&#xe6f2;</i>{{$L('添加子任务')}}
                                </div>
                            </li>
                        </ul>
                    </FormItem>
                </Form>
                <div class="add">
                    <EDropdown
                        trigger="click"
                        placement="bottom-start"
                        @command="">
                        <div class="add-button">
                            <i class="iconfont">&#xe6f2;</i>{{$L('添加模块')}}
                        </div>
                        <EDropdownMenu slot="dropdown">
                            <EDropdownItem v-for="(item, key) in menuList" :key="key" :command="item.command">
                                <div class="item">
                                    <i class="iconfont" v-html="item.icon"></i>{{$L(item.name)}}
                                </div>
                            </EDropdownItem>
                        </EDropdownMenu>
                    </EDropdown>
                </div>
            </div>
        </div>
        <div class="task-dialog">
            <div class="head">
                <Icon class="icon" type="ios-chatbubbles-outline" />
                <div class="nav">
                    <p class="active">群聊</p>
                    <p>动态</p>
                </div>
            </div>
            <div class="no-dialog">
                <div class="no-tip">{{$L('暂无消息')}}</div>
                <div class="no-input">
                    <Input class="dialog-input" v-model="projectOpenTask._msgText" type="textarea" :rows="1" :autosize="{ minRows: 1, maxRows: 3 }" :maxlength="255" :placeholder="$L('输入消息...')" />
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import {mapState} from "vuex";
import TEditor from "../../../components/TEditor";

export default {
    name: "TaskDetail",
    components: {TEditor},
    data() {
        return {
            nowTime: Math.round(new Date().getTime() / 1000),
            nowInterval: null,

            innerHeight: window.innerHeight,

            content: '随着互联网的发展,生活智能化越来越普及,各类智能产品逐渐出现到人们面前,在体验的过程中,其实里面有 很多细节需要深挖和思考。很多产品细节的背后都是为了提升用户操作效率、兼容用户使用场景、满足用户情感 表达,以最终达到对用户体验的提升。作为智能产品的设计师只有充分了解市面上的智能产品,才能设计出更好',

            menuList: [
                {
                    command: 'priority',
                    icon: '&#xe6ec;',
                    name: '优先级',
                }, {
                    command: 'owner',
                    icon: '&#xe6e4;',
                    name: '负责人',
                }, {
                    command: 'times',
                    icon: '&#xe6e8;',
                    name: '截止时间',
                }, {
                    command: 'file',
                    icon: '&#xe6e6;',
                    name: '附件',
                }, {
                    command: 'subtask',
                    icon: '&#xe6f0;',
                    name: '子任务',
                }
            ],

            taskPlugins: [
                'advlist autolink lists link image charmap print preview hr anchor pagebreak imagetools',
                'searchreplace visualblocks visualchars code',
                'insertdatetime media nonbreaking save table contextmenu directionality',
                'emoticons paste textcolor colorpicker imagetools codesample',
                'autoresize'
            ],
            taskOptions: {
                statusbar: false,
                menubar: false,
                forced_root_block : false,
                remove_trailing_brs: false,
                autoresize_bottom_margin: 2,
                min_height: 200,
                max_height: 380,
                valid_elements : 'a[href|target=_blank],em,strong/b,div[align],span[style],a,br,img[src|alt|witdh|height],pre[class],code',
                toolbar: 'uploadImages | uploadFiles | bold italic underline forecolor backcolor | codesample | preview screenload'
            },
            taskOptionFull: {
                menubar: 'file edit view',
                forced_root_block : false,
                remove_trailing_brs: false,
                valid_elements : 'a[href|target=_blank],em,strong/b,div[align],span[style],a,br,img[src|alt|witdh|height],pre[class],code',
                toolbar: 'uploadImages | uploadFiles | bold italic underline forecolor backcolor | codesample | preview screenload'
            },
        }
    },

    mounted() {
        this.nowInterval = setInterval(() => {
            this.nowTime = Math.round(new Date().getTime() / 1000);
        }, 1000);
        window.addEventListener('resize', this.innerHeightListener);
    },

    destroyed() {
        clearInterval(this.nowInterval);
        window.removeEventListener('resize', this.innerHeightListener);
    },

    computed: {
        ...mapState(['userId', 'projectOpenTask']),

        scrollerStyle() {
            const {innerHeight, projectOpenTask} = this;
            if (!innerHeight || !projectOpenTask._dialog) {
                return {};
            }
            return {
                maxHeight: (innerHeight - 70 - 66 - 30) + 'px'
            }
        },

        expiresFormat() {
            const {nowTime} = this;
            return function (date) {
                let time = Math.round(new Date(date).getTime() / 1000) - nowTime;
                if (time < 86400 * 4 && time > 0 ) {
                    return this.formatSeconds(time);
                } else if (time <= 0) {
                    return '-' + this.formatSeconds(time * -1);
                }
                return this.formatTime(date)
            }
        },
    },

    watch: {

    },

    methods: {
        innerHeightListener() {
            this.innerHeight = window.innerHeight;
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

        formatBit(val) {
            val = +val
            return val > 9 ? val : '0' + val
        },

        formatSeconds(second) {
            let duration
            let days = Math.floor(second / 86400);
            let hours = Math.floor((second % 86400) / 3600);
            let minutes = Math.floor(((second % 86400) % 3600) / 60);
            let seconds = Math.floor(((second % 86400) % 3600) % 60);
            if (days > 0) {
                if (hours > 0) duration = days + "d," + this.formatBit(hours) + "h";
                else if (minutes > 0) duration = days + "d," + this.formatBit(minutes) + "min";
                else if (seconds > 0) duration = days + "d," + this.formatBit(seconds) + "s";
                else duration = days + "d";
            }
            else if (hours > 0) duration = this.formatBit(hours) + ":" + this.formatBit(minutes) + ":" + this.formatBit(seconds);
            else if (minutes > 0) duration = this.formatBit(minutes) + ":" + this.formatBit(seconds);
            else if (seconds > 0) duration = this.formatBit(seconds) + "s";
            return duration;
        },
    }
}
</script>
