<template>
    <div class="review-details">
        <div class="review-details-box">
            <h2 class="review-details-title">
                <span>{{datas.proc_def_name}}</span>
                <Tag v-if="datas.state == 0" color="cyan">{{$L('待审批')}}</Tag>
                <Tag v-if="datas.state == 1" color="cyan">{{$L('审批中')}}</Tag>
                <Tag v-if="datas.state == 2" color="green">{{$L('已通过')}}</Tag>
                <Tag v-if="datas.state == 3" color="red">{{$L('已拒绝')}}</Tag>
                <Tag v-if="datas.state == 4" color="red">{{$L('已撤回')}}</Tag>
            </h2>
            <h3 class="review-details-subtitle"><Avatar :src="datas.userimg" size="24"/><span>{{datas.start_user_name}}</span></h3>
            <h3 class="review-details-subtitle"><span>{{$L('提交于')}} {{datas.start_time}}</span></h3>
            <Divider/>
            <div class="review-details-text" v-if="(datas.proc_def_name || '').indexOf('班') == -1">
                <h4>{{$L('假期类型')}}</h4>
                <p>{{datas.var?.type}}</p>
            </div>
            <div class="review-details-text">
                <h4>{{$L('开始时间')}}</h4>
                <p>{{datas.var?.start_time}}</p>
            </div>
            <div class="review-details-text">
                <h4>{{$L('结束时间')}}</h4>
                <p>{{datas.var?.end_time}}</p>
            </div>
            <div class="review-details-text">
                <h4>{{ $L('时长') }}（{{getTimeDifference(datas.var?.start_time,datas.var?.end_time)['unit']}}）</h4>
                <p>{{ getTimeDifference(datas.var?.start_time,datas.var?.end_time)['time'] }}</p>
            </div>
            <div class="review-details-text">
                <h4>{{$L('请假事由')}}</h4>
                <p>{{datas.var?.description}}</p>
            </div>
            <Divider/>
            <h3 class="review-details-subtitle">{{$L('审批记录')}}</h3>
            <Timeline style="margin-top: 20px;">
                <!-- 提交 -->
                <TimelineItem v-for="(item,key) in datas.node_infos" :key="key" v-if="item.type == 'starter'" color="green">
                    <p class="timeline-title">{{$L('提交')}}</p>
                    <div style="display: flex;">
                        <Avatar :src="data.userimg || datas.userimg" size="38"/>
                        <div style="margin-left: 10px;flex: 1;">
                            <p class="review-process-name">{{item.approver}}</p>
                            <p class="review-process-state">{{$L('已提交')}}</p>
                        </div>
                        <div class="review-process-right">
                            <p>{{ getTimeAgo(item.claim_time) }}</p>
                            <p>{{item.claim_time?.substr(0,16)}}</p>
                        </div>
                    </div>
                </TimelineItem>
                <!-- 审批 -->
                <TimelineItem v-for="(item,key) in datas.node_infos" :key="key" v-if="item.type == 'approver' && item._show"
                    :color="item.identitylink ? (item.identitylink?.state > 1 ? '#f03f3f' :'green') : '#ccc'"
                >
                    <p class="timeline-title">{{$L('审批')}}</p>
                    <div style="display: flex;">
                        <Avatar :src="item.node_user_list && item.node_user_list[0]?.userimg" size="38"/>
                        <div style="margin-left: 10px;flex: 1;">
                            <p class="review-process-name">{{item.approver}}</p>
                            <p class="review-process-state" style="color: #6d6d6d;" v-if="!item.identitylink">待审批</p>
                            <p class="review-process-state" v-if="item.identitylink">
                                <span v-if="item.identitylink.state==0" style="color:#496dff;">{{$L('审批中')}}</span>
                                <span v-if="item.identitylink.state==1" >{{$L('已通过')}}</span>
                                <span v-if="item.identitylink.state==2" style="color:#f03f3f;">{{$L('已拒绝')}}</span>
                                <span v-if="item.identitylink.state==3" style="color:#f03f3f;">{{$L('已撤回')}}</span>
                            </p>
                        </div>
                        <div class="review-process-right">
                            <p>
                                {{ item.identitylink?.state==0 ? 
                                    ($L('已等待') + " " + getTimeAgo( datas.node_infos[key-1].claim_time,2)) : 
                                    (item.claim_time ? getTimeAgo(item.claim_time) : '') 
                                }}
                            </p>
                            <p>{{item.claim_time?.substr(0,16)}}</p>
                        </div>
                    </div>
                </TimelineItem>
                <!-- 抄送 -->
                <TimelineItem v-for="(item,key) in datas.node_infos" :key="key" :color="item.is_finished ? 'green' : '#ccc'" v-if="item.type == 'notifier' && item._show">
                    <p class="timeline-title">{{$L('抄送')}}</p>
                    <div style="display: flex;">
                        <Avatar :src="'/images/avatar/default_bot.png'" size="38"/>
                        <div style="margin-left: 10px;flex: 1;">
                            <p class="review-process-name">{{$L('系统')}}</p>
                            <p style="font-size: 12px;">自动抄送
                                <span style="color: #486fed;">
                                    {{ item.node_user_list?.map(h=>h.name).join(',') }} 
                                    等{{item.node_user_list?.length}}人
                                </span>
                            </p>
                        </div>
                    </div>
                </TimelineItem>
                <!-- 结束 -->
                <TimelineItem v-for="(item,key) in datas.node_infos" :key="key" :color="item.is_finished ? 'green' : '#ccc'" v-if="item.aprover_type == 'end'">
                    <p class="timeline-title">{{$L('结束')}}</p>
                    <div style="display: flex;">
                        <Avatar :src="'/images/avatar/default_bot.png'" size="38"/>
                        <div style="margin-left: 10px;flex: 1;">
                            <p class="review-process-name">{{$L('系统')}}</p>
                            <p style="font-size: 12px;"> {{  datas.is_finished ? $L('已结束') : $L('未结束')  }}</p>
                        </div>
                    </div>
                </TimelineItem>
            </Timeline>
        </div>
        <div class="review-operation" v-if="datas.state<=1">
            <div style="flex: 1;"></div>
            <Button type="success" v-if="(datas.candidate || '').split(',').indexOf(userId + '') != -1" @click="approve(1)">{{$L('同意')}}</Button>
            <Button type="error" v-if="(datas.candidate || '').split(',').indexOf(userId + '') != -1" @click="approve(2)">{{$L('拒绝')}}</Button>
            <Button type="warning" v-if="userId == datas.start_user_id" @click="revocation">{{$L('撤销')}}</Button>
        </div>
    </div>
</template>

<script>
export default {
    name: "details",
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
            datas:{

            }
        }
    },
    watch: {
        data: {
            handler(newValue,oldValue) {
                if(newValue.id){
                    this.getInfo()
                }
            },
            deep: true
        },
    },
    mounted() {
        if(this.$route.query.id){
            this.data.id = this.$route.query.id;
            this.getInfo()
        }
    },
    methods:{
        // 把时间转成几小时前
        getTimeAgo(time,type) {
            const currentTime = new Date();
            const timeDiff = (currentTime - new Date(time)) / 1000; // convert to seconds
            if (timeDiff < 60) {
                return  type == 2 ? "0"+this.$L('分钟') : this.$L('刚刚');
            } else if (timeDiff < 3600) {
                const minutes = Math.floor(timeDiff / 60);
                return type == 2 ? `${minutes}${this.$L('分钟')}` : `${minutes} ${this.$L('分钟前')}`;
            } else if(timeDiff < 3600 * 24)  {
                const hours = Math.floor(timeDiff / 3600);
                return type == 2 ? `${hours}${this.$L('小时')}` : `${hours} ${this.$L('小时前')}`;
            } else {
                const days = Math.floor(timeDiff / 3600 / 24);
                return type == 2 ? `${days}${this.$L('天')}` : `${days} ${this.$L('天')}`;
            }
        },
        // 获取时间差
        getTimeDifference(startTime,endTime) {
            const currentTime = new Date(endTime);
            const timeDiff = (currentTime - new Date(startTime)) / 1000; // convert to seconds
            if (timeDiff < 60) {
                return {time:timeDiff,unit:this.$L('秒')};
            } else if (timeDiff < 3600) {
                const minutes = Math.floor(timeDiff / 60);
                return {time:minutes,unit:this.$L('分钟')};
            } else if(timeDiff < 3600 * 24) {
                const hours = Math.floor(timeDiff / 3600);
                return {time:hours,unit:this.$L('小时')};
            } else {
                const days = Math.floor(timeDiff / 3600 / 24);
                return {time:days,unit:this.$L('天')};
            }
        },
        // 获取详情
        getInfo(){
            this.datas = this.data
            this.$store.dispatch("call", {
                method: 'get',
                url: 'workflow/process/detail',
                data: {
                    id:this.data.id,
                }
            }).then(({data}) => {
                var show = true;
                data.node_infos =  data.node_infos.map(item=>{
                    item._show = show;
                    if( item.identitylink?.state==2 || item.identitylink?.state==3 ){
                        show = false;
                    }
                    return item;
                })
                this.datas = data
            }).catch(({msg}) => {
                $A.modalError(msg);
            }).finally(_ => {
                this.loadIng--;
            });
        },
        // 通过
        approve(type){
            $A.modalInput({
                title: `审批`,
                placeholder: `请输入审批意见`,
                type:"textarea",
                okText: type == 1 ? "同意" : "拒绝",
                onOk: (desc) => {
                    if (!desc) {
                        return `请输入审批意见`
                    }
                    this.$store.dispatch("call", {
                        url: 'workflow/task/complete',
                        data: {
                            task_id: this.datas.task_id,
                            pass: type == 1,
                            comment: desc,
                        }
                    }).then(({msg}) => {
                        this.getInfo()
                        this.$emit('approve')
                    }).catch(({msg}) => {
                        $A.modalError(msg);
                    });
                    return false
                }
            });
        },
        // 撤销
        revocation(){
            $A.modalConfirm({
                content: "你确定要撤销吗？",
                loading: true,
                onOk: () => {
                    return new Promise((resolve, reject) => {
                        this.$store.dispatch("call", {
                            url: 'workflow/task/withdraw',
                            data: {
                                task_id: this.datas.task_id,
                                proc_inst_id: this.datas.id,
                            }
                        }).then(({msg}) => {
                            resolve();
                            this.getInfo()
                            this.$emit('revocation')
                        }).catch(({msg}) => {
                            $A.modalError(msg);
                            reject(msg);
                        });
                        return false
                    })
                },
            });
        }
    }
}
</script>

<style scoped>

</style>
