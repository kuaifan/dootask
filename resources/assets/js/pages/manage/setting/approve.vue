<template>
    <div class="setting-item submit">
        <Row class="approve-row" :gutter="8">
            <!-- <Col :xxl="{ span: 6 }" :xl="{ span: 8 }" :lg="{ span: 12 }" :sm="{ span: 24 }" :xs="{ span: 24 }" >
                <div class="approve-col-box approve-col-add" @click="add">
                    <Icon type="md-add" />
                </div>
            </Col> -->
            <Col v-for="(item, key) in list" :xxl="{ span: 6 }" :xl="{ span: 8 }" :lg="{ span: 12 }" :sm="{ span: 24 }" :xs="{ span: 24 }" >
                <div class="approve-col-box approve-col-for" @click="edit(item)">
                    <p>{{$L('流程名称：')}}<span style="font-weight: 500;">{{$L(item.name)}}</span></p>
                    <Divider style="margin: 12px 0;"/>
                    <div class="approve-button-box" @click.stop="edit(item)">
                        <p>{{$L('是否发布')}}： </p>
                        <p>
                            <i-switch v-model="item.issue" :disabled="true" />
                            <!-- <Icon type="md-create" />
                            <Icon type="md-trash" /> -->
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
import {languageType} from "../../../language";
export default {
    name: "approve",
    components: {DrawerOverlay},
    data(){
        return{
            value:false,
            loadIng:0,
            approvalSettingShow:false,
            iframeSrc:"",
            name:"",
            list:[
                {name:"请假",issue:false},
                {name:"加班申请",issue:false},
            ]
        }
    },
    watch: {
        approvalSettingShow(val) {
            if (val) this.iframeSrc = `/workflow/#/?name=${this.name}&token=${store.userToken}&en=${languageType}`
        }
    },
    mounted() {
        window.addEventListener('message', this.saveSuccess)
    },
    beforeDestroy() {
        window.removeEventListener("message", this.saveSuccess);
    },
    methods: {
        // 保存成功回调
        saveSuccess(e){
            if (typeof e.data === 'string') {
                let propsBody = JSON.parse(e.data);
                if( propsBody.method == "saveSuccess" ) {
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
            this.name = "请假"
            this.approvalSettingShow = true;
        },
        // 编辑
        edit(item){
            console.log(item.issue)
            if(!item.issue){
                this.name = item.name
                this.approvalSettingShow = true;
            }
        }
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
</style>
