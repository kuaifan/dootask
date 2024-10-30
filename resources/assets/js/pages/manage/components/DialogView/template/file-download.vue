<template>
    <div>
        <p><b>{{$L(msg.title)}}</b></p>
        <p>&nbsp;</p>

        <p>{{$L('文件名')}}: {{msg.name}}</p>
        <p>{{$L('文件大小')}}: {{$A.bytesToSize(msg.size)}}</p>
        <p style="margin-top:10px">
            <Button :to="msg.url" target="_blank" type="warning" class="no-dark-content">{{$L('立即下载')}}</Button>
        </p>
    </div>
</template>

<script>
export default {
    props: {
        msg: Object,
    },
    data() {
        return {};
    },
    computed: {},
    methods: {},
}
</script>
