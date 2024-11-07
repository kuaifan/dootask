<template>
    <div class="approve-details" :style="{'z-index':modalTransferIndex}">
        <!-- 导航 -->
        <div class="approve-details-nav">
            <div class="common-nav-back" @click="onBack">
                <i class="taskfont">&#xe676;</i>
            </div>
            <h2>{{$L('审批详情')}}</h2>
        </div>
        <!-- 审批详情 -->
        <div class="approve-details-box" ref="approveDetailsBox">
            <h2 class="approve-details-title">
                <span>{{$L(datas.proc_def_name || '- -')}}</span>
                <Tag v-if="datas.state == 0" color="cyan">{{$L('待审批')}}</Tag>
                <Tag v-if="datas.state == 1" color="cyan">{{$L('审批中')}}</Tag>
                <Tag v-if="datas.state == 2" color="green">{{$L('已通过')}}</Tag>
                <Tag v-if="datas.state == 3" color="red">{{$L('已拒绝')}}</Tag>
                <Tag v-if="datas.state == 4" color="red">{{$L('已撤回')}}</Tag>
            </h2>
            <h3 class="approve-details-subtitle">
                <Avatar :src="datas.userimg" size="24"/>
                <span>{{datas.start_user_name}}</span>
            </h3>
            <h3 class="approve-details-subtitle"><span>{{$L('提交于')}} {{datas.start_time}}</span></h3>

            <Divider/>

            <div class="approve-details-text" v-if="(datas.proc_def_name || '').indexOf('请假') !== -1 && datas.var?.type">
                <h4>{{$L('假期类型')}}</h4>
                <p>{{$L(datas.var?.type || '- -')}}</p>
            </div>
            <div class="approve-details-text">
                <h4>{{$L('开始时间')}}</h4>
                <div class="time-text">
                    <span>{{datas.var?.start_time || '- -'}}</span>
                    <span v-if="datas.var?.start_time">({{getWeekday(datas.var?.start_time)}})</span>
                </div>
            </div>
            <div class="approve-details-text">
                <h4>{{$L('结束时间')}}</h4>
                <div class="time-text">
                    <span>{{datas.var?.end_time || '- -'}}</span>
                    <span v-if="datas.var?.end_time">({{getWeekday(datas.var?.end_time)}})</span>
                </div>
            </div>
            <div class="approve-details-text">
                <h4>{{ $L('时长') }}（{{getTimeDifference(datas.var?.start_time,datas.var?.end_time)['unit']}}）</h4>
                <p>{{ datas.var?.start_time ? getTimeDifference(datas.var?.start_time,datas.var?.end_time)['time'] : '- -' }}</p>
            </div>
            <div class="approve-details-text">
                <h4>{{$L('事由')}}</h4>
                <p>{{datas.var?.description || '- -'}}</p>
            </div>
            <div class="approve-details-text"  v-if="datas.var?.other">
                <h4>{{$L('图片')}}</h4>
                <div class="img-body">
                    <div v-for="(src,key) in (datas.var.other).split(',')" @click="onViewPicture(src, 1)">
                        <ImgView :src="src" :key="key" class="img-view"/>
                    </div>
                </div>
            </div>

            <Divider/>

            <h3 class="approve-details-subtitle">{{$L('审批记录')}}</h3>
            <Timeline class="approve-record-timeline">
                <template v-for="(item,key) in datas.node_infos">

                    <!-- 提交 -->
                    <TimelineItem :key="key" v-if="item.type == 'starter'" color="green">
                        <p class="timeline-title">{{$L('提交')}}</p>
                        <div class="timeline-body">
                            <div class="approve-process-avatar" @click="onAvatar(data.start_user_id || datas.start_user_id)">
                                <Avatar :src="data.userimg || datas.userimg" size="38"/>
                            </div>
                            <div class="approve-process-left">
                                <p class="approve-process-name">{{data.start_user_name || datas.start_user_name}}</p>
                                <p class="approve-process-state">{{$L('已提交')}}</p>
                            </div>
                            <div class="approve-process-right">
                                <p v-if="parseInt(getTimeAgo(item.claim_time)) < showTimeNum">{{ getTimeAgo(item.claim_time) }}</p>
                                <p>{{item.claim_time?.substr(0,16)}}</p>
                            </div>
                        </div>
                    </TimelineItem>

                    <!-- 审批 -->
                    <TimelineItem
                        v-if="item.type == 'approver' && item._show"
                        :key="key"
                        :color="item.identitylink ? (item.identitylink?.state > 1 ? '#f03f3f' :'green') : '#ccc'">
                        <p class="timeline-title">{{$L('审批')}}</p>
                        <div class="timeline-body">
                            <div class="approve-process-avatar" @click="onAvatar(item.node_user_list && item.node_user_list[0]?.target_id || item.aprover_id)">
                                <Avatar :src="(item.node_user_list && item.node_user_list[0]?.userimg) || item.userimg" size="38"/>
                            </div>
                            <div class="approve-process-left">
                                <p class="approve-process-name">{{item.approver}}</p>
                                <p v-if="!item.identitylink" class="approve-process-state">
                                    <span style="color:#6d6d6d;">{{$L('待审批')}}</span>
                                </p>
                                <p v-else class="approve-process-state">
                                    <span v-if="item.identitylink.state==0" style="color:#496dff;">{{$L('审批中')}}</span>
                                    <span v-if="item.identitylink.state==1" >{{$L('已通过')}}</span>
                                    <span v-if="item.identitylink.state==2" style="color:#f03f3f;">{{$L('已拒绝')}}</span>
                                    <span v-if="item.identitylink.state==3" style="color:#f03f3f;">{{$L('已撤回')}}</span>
                                </p>
                            </div>
                            <div class="approve-process-right">
                                <p v-if="parseInt(getTimeAgo(item.claim_time)) < showTimeNum">
                                    {{ item.identitylink?.state==0 ?
                                        ($L('已等待') + " " + getTimeAgo( datas.node_infos[key-1].claim_time,2)) :
                                        (item.claim_time ? getTimeAgo(item.claim_time) : '')
                                    }}
                                </p>
                                <p>{{item.claim_time?.substr(0,16)}}</p>
                            </div>
                        </div>
                        <p class="comment" v-if="item.identitylink?.comment"><span>“{{ item.identitylink?.is_system ? $L(item.identitylink?.comment) : item.identitylink?.comment  }}”</span></p>
                    </TimelineItem>

                    <!-- 抄送 -->
                    <TimelineItem :key="key" :color="item.is_finished ? 'green' : '#ccc'" v-if="item.type == 'notifier' && item._show">
                        <p class="timeline-title">{{$L('抄送')}}</p>
                        <div class="timeline-body">
                            <Avatar :src="$A.mainUrl('images/avatar/default_approval.png')" size="38"/>
                            <div class="approve-process-left">
                                <p class="approve-process-name">{{$L('系统')}}</p>
                                <p class="approve-process-desc">{{$L('自动抄送')}}
                                    <span style="color: #486fed;">{{ item.node_user_list?.map(h=>h.name).join(',') }}</span>
                                    {{$L('共'+item.node_user_list?.length+'人')}}
                                </p>
                            </div>
                        </div>
                    </TimelineItem>

                    <!-- 结束 -->
                    <TimelineItem class="finish" :key="key" :color="item.is_finished ? 'green' : '#ccc'" v-if="item.aprover_type == 'end'">
                        <p class="timeline-title">{{$L('结束')}}</p>
                        <div class="timeline-body">
                            <Avatar :src="$A.mainUrl('images/avatar/default_approval.png')" size="38"/>
                            <div class="approve-process-left">
                                <p class="approve-process-name">{{$L('系统')}}</p>
                                <p class="approve-process-desc"> {{  datas.is_finished ? $L('已结束') : $L('未结束')  }}</p>
                            </div>
                        </div>
                    </TimelineItem>

                </template>
            </Timeline>

            <template v-if="$A.arrayLength(datas.global_comments) > 0">
                <Divider/>
                <h3 class="approve-details-subtitle">{{$L('全文评论')}}</h3>
                <div class="approve-record-comment">
                    <List :split="false" :border="false">
                        <ListItem v-for="(item, key) in datas.global_comments" :key="key">
                            <div>
                                <div class="top">
                                    <span @click="onAvatar(item.user_id)">
                                        <Avatar :src="item.userimg" size="38"/>
                                    </span>
                                    <div>
                                        <p>{{ item.nickname }}</p>
                                        <p class="time">{{ item.created_at }}</p>
                                    </div>
                                    <span>{{ getTimeAgo(item.created_at) }}</span>
                                </div>
                                <div class="content">
                                    {{ getContent(item.content) }}
                                </div>
                                <div class="content" style="display: flex; gap: 10px;">
                                    <div v-for="(src, k) in getPictures(item.content)" :key="k" @click="onViewPicture(src, 2)">
                                        <ImgView :src="getPictureThumb(src)" :error-src="src" class="img-view"/>
                                    </div>
                                </div>
                            </div>
                        </ListItem>
                    </List>
                </div>
            </template>
        </div>

        <!--审批操作-->
        <div class="approve-operation">
            <Button type="primary" v-if="isShowAgreeBtn && !loadIng" @click="approve(1)">{{$L('同意')}}</Button>
            <Button type="error" v-if="isShowAgreeBtn && !loadIng"  @click="approve(2)">{{$L('拒绝')}}</Button>
            <Button type="warning" v-if="isShowWarningBtn && !loadIng" @click="revocation">{{$L('撤销')}}</Button>
            <Button type="primary" @click="comment" :loading="loadIng > 0" ghost>+{{$L('添加评论')}}</Button>
        </div>

        <!--加载中-->
        <div v-if="loadIng > 0" class="approve-load">
            <Loading/>
        </div>

        <!--评论-->
        <Modal v-model="commentShow" :title="$L('评论')" :mask-closable="false" class="page-approve-initiate">
            <Form ref="initiateRef" :model="commentData" :rules="commentRule" v-bind="formOptions" @submit.native.prevent>
                <FormItem prop="content" :label="$L('内容')">
                    <Input type="textarea" v-model="commentData.content"></Input>
                </FormItem>
                <FormItem prop="pictures" :label="$L('图片')">
                    <ImgUpload v-model="commentData.pictures" :num="3" :width="2000" :height="2000" :whcut="0"></ImgUpload>
                </FormItem>
            </Form>
            <div slot="footer" class="adaption">
                <Button type="default" @click="commentShow=false">{{$L('取消')}}</Button>
                <Button type="primary" :loading="commentLoad > 0" @click="confirmComment">{{$L('确认')}}</Button>
            </div>
        </Modal>
    </div>
</template>

<script>
import ImgView from "../../../components/ImgView";
import ImgUpload from "../../../components/ImgUpload";
import {mapState} from "vuex";

export default {
    name: "ApproveDetails",
    components: {ImgView, ImgUpload},
    props: {
        data: {
            type: Object,
            default() {
                return {};
            }
        }
    },

    data() {
        return {
            datas: {},
            loadIng: 0,
            showTimeNum: 24,
            modalTransferIndex: window.modalTransferIndex,

            commentLoad: 0,
            commentShow: false,
            commentData: {
                content: "",
                pictures: []
            },
            commentRule: {
                content: {type: 'string', required: true, message: this.$L('请输入内容！'), trigger: 'change'},
            }
        }
    },
    watch: {
        '$route'(to, from) {
            if (to.name == 'manage-approve-details') {
                this.init()
            }
        },
        data: {
            handler(newValue, oldValue) {
                if (newValue.id) {
                    this.getInfo()
                }
            },
            deep: true
        },
    },
    computed: {
        ...mapState(['formOptions']),

        isShowAgreeBtn() {
            return (this.datas.candidate || '').split(',').indexOf(this.userId + '') != -1 && !this.datas.is_finished
        },
        isShowWarningBtn() {
            let is = (this.userId == this.datas.start_user_id) && this.datas?.is_finished != true;
            (this.datas.node_infos || []).map(h => {
                if (h.type != 'starter' && h.is_finished == true && h.identitylink?.userid != this.userId) {
                    is = false;
                }
            })
            return is;
        },
    },
    mounted() {
        this.init()
    },
    methods: {
        init() {
            this.modalTransferIndex = window.modalTransferIndex = window.modalTransferIndex + 1
            if (this.$route.query.id) {
                this.getInfo()
            }
        },
        // 返回
        onBack() {
            this.$emit('onBack')
        },
        // 把时间转成几小时前
        getTimeAgo(time, type) {
            const timeDiff = $A.dayjs().unix() - $A.dayjs(time).unix(); // convert to seconds
            if (timeDiff < 60) {
                return type == 2 ? "0" + this.$L('分钟') : this.$L('刚刚');
            } else if (timeDiff < 3600) {
                const minutes = Math.floor(timeDiff / 60);
                return type == 2 ? `${minutes}${this.$L('分钟')}` : `${minutes} ${this.$L('分钟前')}`;
            } else if (timeDiff < 3600 * 24) {
                const hours = Math.floor(timeDiff / 3600);
                return type == 2 ? `${hours}${this.$L('小时')}` : `${hours} ${this.$L('小时前')}`;
            } else if (timeDiff < 3600 * 24 * 30) {
                const days = Math.floor(timeDiff / 3600 / 24);
                return type == 2 ? `${days + 1}${this.$L('天')}` : `${days + 1} ${this.$L('天前')}`;
            } else {
                const days = Math.floor(timeDiff / 3600 / 720);
                return type == 2 ? `${days + 1}${this.$L('月')}` : `${days + 1} ${this.$L('月前')}`;
            }
        },
        // 时间转为周几
        getWeekday(dateString) {
            return this.$L(['周日', '周一', '周二', '周三', '周四', '周五', '周六'][$A.dayjs(dateString).day()]);
        },
        // 获取时间差
        getTimeDifference(startTime, endTime) {
            const currentTime = $A.dayjs(endTime);
            const endTimes = $A.dayjs(startTime);
            const timeDiff = currentTime.unix() - endTimes.unix(); // convert to seconds
            if (timeDiff < 60) {
                return {time: timeDiff, unit: this.$L('秒')};
            } else if (timeDiff < 3600) {
                const minutes = Math.floor(timeDiff / 60);
                return {time: minutes, unit: this.$L('分钟')};
            } else if (timeDiff < 3600 * 24) {
                const hours = Math.floor(timeDiff / 60 / 60);
                return {time: hours, unit: this.$L('小时')};
            } else {
                const days = Math.floor(timeDiff / 60 / 60 / 24);
                return {time: days + 1, unit: this.$L('天')};
            }
        },
        // 获取详情
        getInfo(isScrollToBottom = false) {
            this.loadIng++;
            this.$store.dispatch("call", {
                method: 'get',
                url: 'approve/process/detail',
                data: {
                    id: this.$route.query.id || this.data.id,
                }
            }).then(({data}) => {
                var show = true;
                data.node_infos = data.node_infos.map(item => {
                    item._show = show;
                    if (item.identitylink?.state == 2 || item.identitylink?.state == 3) {
                        show = false;
                    }
                    return item;
                })
                this.datas = data
                isScrollToBottom && this.scrollToBottom();
            }).catch(({msg}) => {
                $A.modalError(msg);
            }).finally(_ => {
                this.loadIng--;
            });
        },
        // 通过
        approve(type) {
            $A.modalInput({
                title: `审批`,
                placeholder: `请输入审批意见`,
                type: "textarea",
                okText: type == 1 ? "同意" : "拒绝",
                okType: type == 1 ? "primary" : "error",
                onOk: (desc) => {
                    if (type != 1 && !desc) {
                        return `请输入审批意见`
                    }
                    return new Promise((resolve, reject) => {
                        this.$store.dispatch("call", {
                            url: 'approve/task/complete',
                            data: {
                                task_id: this.datas.task_id,
                                pass: type == 1,
                                comment: desc,
                            }
                        }).then(({msg}) => {
                            $A.messageSuccess(msg);
                            if (this.$route.name == 'manage-approve-details' || this.$route.name == 'manage-messenger') {
                                this.getInfo()
                            } else {
                                this.$emit('approve')
                            }
                            resolve()
                        }).catch(({msg}) => {
                            reject(msg)
                        });
                    })
                }
            });
        },
        // 撤销
        revocation() {
            $A.modalConfirm({
                content: "你确定要撤销吗？",
                loading: true,
                okType: "warning",
                onOk: () => {
                    return new Promise((resolve, reject) => {
                        this.$store.dispatch("call", {
                            url: 'approve/task/withdraw',
                            data: {
                                task_id: this.datas.task_id,
                                proc_inst_id: this.datas.id,
                            }
                        }).then(({msg}) => {
                            $A.messageSuccess(msg);
                            resolve();
                            if (this.$route.name == 'manage-approve-details' || this.$route.name == 'manage-messenger') {
                                this.getInfo()
                            } else {
                                this.$emit('revocation')
                            }
                        }).catch(({msg}) => {
                            reject(msg);
                        });
                    })
                },
            });
        },
        // 评论
        comment() {
            this.commentData.content = ""
            this.commentData.pictures = []
            this.commentShow = true;
        },
        // 提交评论
        confirmComment() {
            this.commentLoad++;
            this.$refs["initiateRef"].validate((valid) => {
                if (valid) {
                    this.$store.dispatch("call", {
                        method: 'post',
                        url: 'approve/process/addGlobalComment',
                        data: {
                            proc_inst_id: this.$route.query.id || this.data.id,
                            content: JSON.stringify({
                                'content': this.commentData.content,
                                'pictures': this.commentData.pictures.map(h => {
                                    return h.path;
                                })
                            })
                        }
                    }).then(({msg}) => {
                        $A.messageSuccess("添加成功");
                        this.getInfo(true)
                        this.commentShow = false;
                    }).catch(({msg}) => {
                        $A.modalError(msg);
                    }).finally(_ => {
                        this.commentLoad--;
                    });
                } else {
                    this.commentLoad--;
                }
            })
        },
        // 滚动到容器底部
        scrollToBottom() {
            this.$nextTick(() => {
                const container = this.$refs.approveDetailsBox
                container.scrollTo({
                    top: container.scrollHeight + 1000,
                    behavior: 'smooth'
                });
            })
        },
        // 获取内容
        getContent(content) {
            try {
                return JSON.parse(content).content || ''
            } catch (error) {
                return ''
            }
        },
        // 获取图片
        getPictures(content) {
            try {
                return JSON.parse(content).pictures || []
            } catch (error) {
                return ''
            }
        },
        // 获取图片缩略图
        getPictureThumb(src) {
            if (!/\.(png|jpg|jpeg)$/.test(src)) {
                return src
            }
            return $A.thumbRestore(src) + '_thumb.' + src.split('.').pop()
        },
        // 打开图片
        onViewPicture(currentUrl, type) {
            const datas = [];
            if (type == 1) {
                datas.push(...this.datas.var.other.split(','))
            }
            if (type == 2) {
                this.datas.global_comments.map(h => {
                    datas.push(...this.getPictures(h.content))
                })
            }
            const list = datas.map(src => {
                return {
                    src: $A.mainUrl(src)
                }
            });
            this.$store.dispatch("previewImage", {index: $A.mainUrl(currentUrl), list})
        },
        // 点击头像
        onAvatar(userid) {
            if (!/^\d+$/.test(userid)) {
                return
            }
            this.$store.dispatch("openDialogUserid", userid).then(_ => {
                if (this.$parent.$options.name === "DrawerOverlayView") {
                    this.$parent.onClose()
                }
                this.goForward({name: 'manage-messenger'})
            }).catch(({msg}) => {
                $A.modalError(msg)
            });
        }
    }
}
</script>
