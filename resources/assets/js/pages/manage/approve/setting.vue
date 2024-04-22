<template>
    <div class="page-approve-setting">
        <Row class="approve-row" :gutter="16">
            <Col :xxl="{ span: 6 }" :xl="{ span: 8 }" :lg="{ span: 12 }" :sm="{ span: 12 }" :xs="{ span: 24 }" >
                <div class="approve-col-box approve-col-add" @click="add">
                    <Icon type="md-add" />
                </div>
            </Col>
            <Col v-for="(item, key) in list" :xxl="{ span: 6 }" :xl="{ span: 8 }" :lg="{ span: 12 }" :sm="{ span: 12 }" :xs="{ span: 24 }" :key="key">
                <div class="approve-col-box approve-col-for" @click="edit(item)">
                    <p>{{$L('流程名称')}}：<span class="approve-name">{{$L(item.name)}}</span></p>
                    <Divider class="divider"/>
                    <div class="approve-button-box" @click.stop="edit(item)">
                        <p>{{$L('已发布')}}</p>
                        <p class="icon-warp" @click.stop="change(item)" >
                            <Icon type="md-trash" size="16" class="delcon"/>
                        </p>
                    </div>
                </div>
            </Col>
        </Row>

        <!--查看所有项目-->
        <DrawerOverlay v-model="approvalSettingShow"  placement="right" :size="1200">
            <iframe :src="iframeSrc"></iframe>
        </DrawerOverlay>

    </div>
</template>

<script>
import DrawerOverlay from "../../../components/DrawerOverlay";
import store from '../../../store/state'
import {languageName} from "../../../language";
export default {
    name: "ApproveSetting",
    components: {DrawerOverlay},
    data(){
        return{
            value:false,
            loadIng:0,
            approvalSettingShow:false,
            iframeSrc:"",
            name:"",
            list:[]
        }
    },
    watch: {
        approvalSettingShow(val) {
            if (val) {
                this.iframeSrc = $A.mainUrl(`approve/#/?name=${this.name}&token=${store.userToken}&lang=${languageName}`)
            }
        }
    },
    mounted() {
        window.addEventListener('message', this.saveSuccess)
        this.getList();
    },
    beforeDestroy() {
        window.removeEventListener("message", this.saveSuccess);
    },
    methods: {
        // 获取列表数据
        getList(){
            this.$store.dispatch("call", {
                url: 'approve/procdef/all',
                method: 'post',
            }).then(({data}) => {
                this.list = data.rows;
                data.rows.forEach((h,index) => {
                    this.list.forEach((o,index) => {
                        if(o.name == h.name){
                            o.issue = true;
                            o.id = h.id;
                            o.version = h.version;
                        }
                    })
                })
            }).catch(({msg}) => {
                $A.modalError(msg);
            }).finally(_ => {
                this.loadIng--;
            });
        },
        // 保存成功回调
        saveSuccess(e){
            if (typeof e.data === 'string') {
                let propsBody = JSON.parse(e.data);
                if( propsBody.method == "saveSuccess" ) {
                    this.getList();
                    this.list.forEach((h,index) => {
                        if(h.name == this.name){
                            h.issue = true;
                            this.$set(this.list,index,h)
                        }
                    });
                    this.approvalSettingShow = false;
                    $A.messageSuccess('发布成功');
                }
            }
        },
        // 添加
        add(){
            $A.modalInput({
                title: `添加流程`,
                placeholder: `请输入流程名称`,
                okText: "确定",
                onOk: (desc) => {
                    if (!desc) {
                        return `请输入流程名称`
                    }
                    this.name = desc
                    this.approvalSettingShow = true;
                    return false
                }
            });
        },
        // 编辑
        edit(item){
            this.name = item.name
            this.approvalSettingShow = true;
        },
        // 变更
        change(item){
            this.$nextTick(()=>{
                item.issue = true;
                $A.modalConfirm({
                    title: '删除',
                    content: '将会清空流程数据，此操作不可恢复',
                    onOk: () => {
                        this.del(item)
                    }
                });
            });
        },
        // 删除数据
        del(item){
            if(!item.id){
                item.issue = false;
                return true;
            }
            this.$store.dispatch("call", {
                url: 'approve/procdef/del',
                data: {id: item.id},
                method: 'post',
            }).then(({data}) => {
                item.issue = false;
                this.getList();
                $A.messageSuccess('成功');
            }).catch(({msg}) => {
                $A.modalError(msg);
            }).finally(_ => {
                this.loadIng--;
            });
        },
    }
}
</script>

<style scoped>
    iframe{
        width: 100%;
        height: 100%;
        padding: 0;
        margin: 0;
        border: 0;
        float: left;
        border-top-left-radius: 18px;
        border-bottom-left-radius: 18px;
    }
    .delcon{
        position: absolute;
        right: 0;
        padding: 5px !important;
    }
    .delcon:hover{
        color: #ed4014 !important;
    }
</style>
