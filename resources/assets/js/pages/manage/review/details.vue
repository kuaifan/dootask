<template>
    <div class="review-details">
        <div class="review-details-box">
            <h2 class="review-details-title">请假<Tag color="success">已通过</Tag></h2>
            <h3 class="review-details-subtitle"><Avatar src="https://i.loli.net/2017/08/21/599a521472424.jpg" size="24"/><span>请假名字</span></h3>
            <Divider/>
            <div class="review-details-text">
                <h4>{{$L('假期类型')}}</h4>
                <p>{{$L('事假')}}</p>
            </div>
            <div class="review-details-text">
                <h4>{{$L('开始时间')}}</h4>
                <p>{{$L('2023年1月1日')}}</p>
            </div>
            <div class="review-details-text">
                <h4>{{$L('结束时间')}}</h4>
                <p>{{$L('2023年1月2日')}}</p>
            </div>
            <div class="review-details-text">
                <h4>{{$L('时长')}}（小时）</h4>
                <p>{{$L('1天')}}</p>
            </div>
            <div class="review-details-text">
                <h4>{{$L('请假事由')}}</h4>
                <p>{{$L('什么什么什么的')}}</p>
            </div>
            <Divider/>
            <h3 class="review-details-subtitle">{{$L('审批进程')}}</h3>
            <div class="review-details-process">
                <div class="review-details-line"></div>

                <div class="review-process">
                    <div class="review-process-left">
                        <Avatar src="https://i.loli.net/2017/08/21/599a521472424.jpg" size="48"/>
                        <div class="review-process-text">
                            <p class="review-process-name">审核人名字</p>
                            <p class="review-process-state">已通过</p>
                        </div>
                    </div>
                    <div class="review-process-right">
                        2023-1-1 12:32:32
                    </div>
                </div>

                <div class="review-process">
                    <div class="review-process-left">
                        <Avatar src="https://i.loli.net/2017/08/21/599a521472424.jpg" size="48"/>
                        <div class="review-process-text">
                            <p class="review-process-name">审核人名字</p>
                            <p class="review-process-state">已通过</p>
                        </div>
                    </div>
                    <div class="review-process-right">
                        2023-1-1 12:32:32
                    </div>
                </div>

                <div class="review-process">
                    <div class="review-process-left">
                        <Avatar src="https://i.loli.net/2017/08/21/599a521472424.jpg" size="48"/>
                        <div class="review-process-text">
                            <p class="review-process-name">抄送人</p>
                            <p class="review-process-state">已抄送</p>
                        </div>
                    </div>
                    <div class="review-process-right">
                        2023-1-1 12:32:32
                    </div>
                </div>
            </div>
            <div class="review-copy">
                <div class="review-copy-member">
                    <Avatar src="https://i.loli.net/2017/08/21/599a521472424.jpg" size="24"/>抄送人
                </div>
            </div>
        </div>

        <div class="review-operation">
            <Button type="text">通过</Button>
            <Button type="text">拒绝</Button>
            <Button type="text">撤销</Button>
            <Button type="text">删除</Button>
        </div>
    </div>
</template>

<script>
export default {
    name: "details"
}
</script>

<style scoped>

</style>
