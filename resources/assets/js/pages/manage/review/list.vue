<template>
    <div class="review-list review-list-active">
        <h2><span class="list-name">请假类型</span> <Tag color="success">已通过</Tag></h2>
        <p>{{$L('假期类型：')}}<span>{{$L('事假')}}</span></p>
        <p>{{$L('开始时间：')}}<span>{{$L('2023年1月1日')}}</span></p>
        <p>{{$L('结束时间：')}}<span>{{$L('2023年1月2日')}}</span></p>
        <div class="list-member">
            <span>
                  <Avatar src="https://i.loli.net/2017/08/21/599a521472424.jpg" size="18"/>
                请假名字
            </span>
            <span>
                发起时间：2023年1月2日
            </span>
        </div>
    </div>
</template>

<script>
export default {
    name: "list"
}
</script>

