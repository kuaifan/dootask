<template>
    <div class="page-approve">
        <PageTitle :title="$L('审批中心')"/>
        <div class="approve-wrapper" ref="fileWrapper">

            <div class="approve-head">
                <div class="approve-nav">
                    <h1>{{$L('审批中心')}}</h1>
                </div>
                <Button type="primary" @click="addApply">{{$L("添加申请")}}</Button>
            </div>

            <Tabs :value="tabsValue" @on-click="tabsClick" style="margin: 0 20px;height: 100%;"  size="small">
                <TabPane :label="$L('待办') + (unreadTotal > 0 ? ('('+unreadTotal+')') : '')" name="unread" style="height: 100%;">
                    <div class="approve-main-search">
                        <div style="display: flex;gap: 10px;">
                            <Select v-model="approvalType" @on-change="tabsClick('',0)" style="width: 150px;">
                                <Option v-for="item in approvalList" :value="item.value" :key="item.value">{{ item.label }}</Option>
                            </Select>
                        </div>
                    </div>
                    <div class="noData" v-if="unreadList.length==0">{{$L('暂无数据')}}</div>
                    <div v-else class="approve-mains">
                        <div class="approve-main-left">
                            <div class="approve-main-list">
                                <div @click.stop="clickList(item,key)"  v-for="(item,key) in unreadList">
                                    <list :class="{ 'approve-list-active': item._active }" :data="item"></list>
                                </div>
                            </div>
                        </div>
                        <div class="approve-main-right">
                            <listDetails v-if="!detailsShow && tabsValue=='unread'" :data="details" @approve="tabsClick" @revocation="tabsClick"></listDetails>
                        </div>
                    </div>
                </TabPane>
                <TabPane :label="$L('已办')" name="done">
                    <div class="approve-main-search">
                        <div style="display: flex;gap: 10px;">
                            <Select v-model="approvalType" @on-change="tabsClick('',0)" style="width: 150px;">
                                <Option v-for="item in approvalList" :value="item.value" :key="item.value">{{ item.label }}</Option>
                            </Select>
                        </div>
                    </div>
                    <div v-if="doneList.length==0" class="noData">{{$L('暂无数据')}}</div>
                    <div v-else class="approve-mains">
                        <div class="approve-main-left">
                            <div class="approve-main-list">
                                <div @click.stop="clickList(item,key)"  v-for="(item,key) in doneList" >
                                    <list :class="{ 'approve-list-active': item._active }" :data="item"></list>
                               </div>
                            </div>
                        </div>
                        <div class="approve-main-right">
                            <listDetails v-if="!detailsShow && tabsValue=='done'" :data="details" @approve="tabsClick" @revocation="tabsClick"></listDetails>
                        </div>
                    </div>
                </TabPane>
                <TabPane :label="$L('抄送我')" name="notify">
                    <div class="approve-main-search">
                        <div class="approve-main-search">
                            <div style="display: flex;gap: 10px;">
                                <Select v-model="approvalType" @on-change="tabsClick('',0)" style="width: 150px;">
                                    <Option v-for="item in approvalList" :value="item.value" :key="item.value">{{ item.label }}</Option>
                                </Select>
                            </div>
                        </div>
                    </div>
                    <div class="noData" v-if="notifyList.length==0">{{$L('暂无数据')}}</div>
                    <div v-else class="approve-mains">
                        <div class="approve-main-left">
                            <div class="approve-main-list">
                                <div @click.stop="clickList(item,key)"  v-for="(item,key) in notifyList">
                                    <list :class="{ 'approve-list-active': item._active }" :data="item"></list>
                               </div>
                            </div>
                        </div>
                        <div class="approve-main-right">
                            <listDetails v-if="!detailsShow && tabsValue=='notify'" :data="details" @approve="tabsClick" @revocation="tabsClick"></listDetails>
                        </div>
                    </div>
                </TabPane>
                <TabPane :label="$L('已发起')" name="initiated">
                    <div class="approve-main-search">
                        <div style="display: flex;gap: 10px;">
                            <Select v-model="approvalType" @on-change="tabsClick('',0)" style="width: 150px;">
                                <Option v-for="item in approvalList" :value="item.value" :key="item.value">{{ item.label }}</Option>
                            </Select>
                            <Select v-model="searchState" @on-change="tabsClick('',0)" style="width: 150px;">
                                <Option v-for="item in searchStateList" :value="item.value" :key="item.value">{{ item.label }}</Option>
                            </Select>
                        </div>
                    </div>
                    <div class="noData" v-if="initiatedList.length==0">{{$L('暂无数据')}}</div>
                    <div v-else class="approve-mains">
                        <div class="approve-main-left">
                            <div class="approve-main-list">
                                <div @click.stop="clickList(item,key)"  v-for="(item,key) in initiatedList">
                                    <list :class="{ 'approve-list-active': item._active }" :data="item"></list>
                               </div>
                            </div>
                        </div>
                        <div class="approve-main-right">
                            <listDetails v-if="!detailsShow && tabsValue=='initiated'" :data="details" @approve="tabsClick" @revocation="tabsClick"></listDetails>
                        </div>
                    </div>
                </TabPane>
            </Tabs>

        </div>

        <!--详情-->
        <DrawerOverlay v-model="detailsShow"  placement="right" :size="600">
            <listDetails v-if="detailsShow" :data="details" @approve="tabsClick" @revocation="tabsClick" style="height: 100%;border-radius: 10px;"></listDetails>
        </DrawerOverlay>

        <!--发起-->
        <Modal v-model="addShow" :title="$L(addTitle)" :mask-closable="false" class="page-approve-initiate">
            <Form ref="initiateRef" :model="addData" :rules="addRule" label-width="auto" @submit.native.prevent>
                <FormItem v-if="departmentList.length>1" prop="department_id" :label="$L('选择部门')">
                    <Select v-model="addData.department_id" :placeholder="$L('请选择部门')">
                        <Option v-for="(item, index) in departmentList" :value="item.id" :key="index">{{ item.name }}</Option>
                    </Select>
                </FormItem>
                <FormItem prop="applyType" :label="$L('申请类型')">
                    <Select v-model="addData.applyType" :placeholder="$L('请选择申请类型')">
                        <Option v-for="(item, index) in procdefList" :value="item.name" :key="index">{{ item.name }}</Option>
                    </Select>
                </FormItem>
                <FormItem v-if="(addData.applyType || '').indexOf('请假') !== -1" prop="type" :label="$L('假期类型')">
                    <Select v-model="addData.type" :placeholder="$L('请选择假期类型')">
                        <Option v-for="(item, index) in selectTypes" :value="item" :key="index">{{ $L(item) }}</Option>
                    </Select>
                </FormItem>
                <FormItem prop="startTime" :label="$L('开始时间')">
                    <div style="display: flex;gap: 3px;">
                        <DatePicker type="date" format="yyyy-MM-dd"
                            v-model="addData.startTime"
                            :editable="false"
                            @on-change="(e)=>{ addData.startTime = e }"
                            :placeholder="$L('请选择开始时间')"
                            style="flex: 1;min-width: 122px;"
                        ></DatePicker>
                        <Select v-model="addData.startTimeHour" style="max-width: 100px;">
                            <Option v-for="(item,index) in 24" :value="item-1 < 10 ? '0'+(item-1) : item-1 " :key="index">{{item-1 < 10 ? '0' : ''}}{{item-1}}</Option>
                        </Select>
                        <Select v-model="addData.startTimeMinute" style="max-width: 100px;">
                            <Option value="00">00</Option>
                            <Option value="30">30</Option>
                        </Select>
                    </div>
                </FormItem>
                <FormItem prop="endTime" :label="$L('结束时间')">
                    <div style="display: flex;gap: 3px;">
                        <DatePicker type="date" format="yyyy-MM-dd"
                            v-model="addData.endTime"
                            :editable="false"
                            @on-change="(e)=>{ addData.endTime = e }"
                            :placeholder="$L('请选择结束时间')"
                            style="flex: 1;min-width: 122px;"
                        ></DatePicker>
                        <Select v-model="addData.endTimeHour" style="max-width: 100px;">
                            <Option v-for="(item,index) in 24" :value="item-1 < 10 ? '0'+(item-1) : ((item-1)+'') " :key="index">{{item-1 < 10 ? '0' : ''}}{{item-1}}</Option>
                        </Select>
                        <Select v-model="addData.endTimeMinute" style="max-width: 100px;">
                            <Option value="00">00</Option>
                            <Option value="30">30</Option>
                        </Select>
                    </div>
                </FormItem>
                <FormItem prop="description" :label="$L('事由')">
                    <Input type="textarea" v-model="addData.description"></Input>
                </FormItem>
                <FormItem prop="other" :label="$L('图片')">
                    <ImgUpload v-model="addData.other" :num="3" :width="2000" :height="2000" :whcut="0"></ImgUpload>
                </FormItem>
            </Form>
            <div slot="footer" class="adaption">
                <Button type="default" @click="addShow=false">{{$L('取消')}}</Button>
                <Button type="primary" :loading="loadIng > 0" @click="onInitiate">{{$L('确认')}}</Button>
            </div>
        </Modal>

    </div>
</template>

<script>
import list from "./list.vue";
import listDetails from "./details.vue";
import DrawerOverlay from "../../../components/DrawerOverlay";
import ImgUpload from "../../../components/ImgUpload";
import {mapState} from 'vuex'

export default {
    components:{list,listDetails,DrawerOverlay,ImgUpload},
    name: "approve",
    data(){
        return{
            minDate: new Date(2020, 0, 1),
            maxDate: new Date(2025, 10, 1),
            currentDate: new Date(2021, 0, 17),

            procdefList: [],
            page: 1,
            pageSize: 250,
            total: 0,
            noText: '',
            loadIng:false,

            tabsValue:"",
            //
            approvalType:"all",
            approvalList:[
                {value:"all",label:this.$L("全部审批")},
                {value:"请假",label:this.$L("请假")},
                {value:"加班申请",label:this.$L("加班申请")},
            ],
            searchState:"all",
            searchStateList:[
                {value:"all",label:this.$L("全部状态")},
                {value:1,label:this.$L("审批中")},
                {value:2,label:this.$L("已通过")},
                {value:3,label:this.$L("已拒绝")},
                {value:4,label:this.$L("已撤回")}
            ],
            //
            unreadTotal:0,
            unreadList: [],
            doneList:[],
            notifyList:[],
            initiatedList: [],
            //
            details:{},
            detailsShow:false,
            //
            addTitle:'',
            addShow:false,
            startTimeOpen:false,
            endTimeOpen:false,
            addData: {
                department_id:0,
                applyType: '',
                type: '',
                startTime: "2023-04-20",
                startTimeHour:"09",
                startTimeMinute:"00",
                endTime: "2023-04-20",
                endTimeHour:"18",
                endTimeMinute:"00",
                other:""
            },
            addRule: {
                department_id:{ type: 'number',required: true, message: this.$L('请选择部门！'), trigger: 'change' },
                applyType: { type: 'string',required: true, message: this.$L('请选择申请类型！'), trigger: 'change' },
                type: { type: 'string',required: true, message: this.$L('请选择假期类型！'), trigger: 'change' },
                startTime: { type: 'string',required: true, message: this.$L('请选择开始时间！'), trigger: 'change' },
                endTime:{ type: 'string',required: true, message: this.$L('请选择结束时间！'), trigger: 'change' },
                description:{ type: 'string',required: true, message: this.$L('请输入事由！'), trigger: 'change' },
            },
            selectTypes:["年假","事假","病假","调休","产假","陪产假","婚假","丧假","哺乳假"],

            //
            showDateTime:false
        }
    },
    computed: {
        ...mapState([ 'wsMsg','userInfo','userIsAdmin' ]),
        departmentList(){
            let departmentNames = (this.userInfo.department_name || '').split(',');
            return (this.userInfo.department || []).map((h,index)=>{
                return {
                    id:h,
                    name:departmentNames[index]
                };
            })
        }
    },
    watch: {
        '$route' (to, from) {
            if(to.name == 'manage-approve'){
                this.tabsClick()
            }
        },
        wsMsg: {
            handler(info) {
                const {type, action} = info;
                switch (type) {
                    case 'approve':
                        if (action == 'unread') {
                            this.tabsClick()
                        }
                        break;
                }
            },
            deep: true,
        },
        addShow(val){
            if(!val){
                this.addData.other = ""
            }
        }
    },
    mounted() {
        this.tabsValue = "unread"
        this.tabsClick()
        this.getUnreadList()
        this.addData.department_id = this.userInfo.department[0] || 0;
        this.addData.startTime = this.addData.endTime = this.getCurrentDate();
    },
    methods:{
        getCurrentDate() {
            const today = new Date();
            const year = today.getFullYear();
            const month = String(today.getMonth() + 1).padStart(2, '0');
            const date = String(today.getDate()).padStart(2, '0');
            return `${year}-${month}-${date}`;
        },
        // tab切换事件
        tabsClick(val,time= 1000){
            if(!val && this.__tabsClick && time>0){
                return;
            }
            this.__tabsClick = setTimeout(() => {  this.__tabsClick =null; },time)

            this.tabsValue = val || this.tabsValue
            if(val!=""){
                this.approvalType = this.searchState = "all"
            }
            if(this.tabsValue == 'unread'){
                this.getUnreadList();
            }
            if(this.tabsValue == 'done'){
                this.getDoneList();
            }
            if(this.tabsValue == 'notify'){
                this.getNotifyList();
            }
            if(this.tabsValue == 'initiated'){
                this.getInitiatedList();
            }
        },

        // 列表点击事件
        clickList(item){
            this.unreadList.map(h=>{ h._active = false; })
            this.doneList.map(h=>{ h._active = false; })
            this.notifyList.map(h=>{ h._active = false; })
            this.initiatedList.map(h=>{ h._active = false; })
            item._active = true;
            //
            if( window.innerWidth < 426 ){
                this.goForward({name: 'manage-approve-details', query: { id: item.id } });
                return;
            }
            if( window.innerWidth < 1010 ){
                this.detailsShow = true;
            }
            this.details = {}
            this.$nextTick(()=>{
                this.details = item
            })
        },

        // 获取待办列表
        getUnreadList(){
            this.$store.dispatch("call", {
                method: 'get',
                url: 'approve/process/findTask',
                data: {
                    page:this.page,
                    page_size: this.pageSize,
                    proc_def_name: this.approvalType == 'all' ? '' : this.approvalType,
                }
            }).then(({data}) => {
                let activeId = 0;
                let activeIndex = 0;
                if( this.unreadList.length == 0 || this.unreadList.length == data.rows.length){
                    this.unreadList?.map((res)=>{ if(res._active)  activeId = res.id  })
                }
                this.unreadList = data.rows.map((h,index)=>{
                    h._active = activeId > 0 ? h.id == activeId : index == 0;
                    if(h._active) activeIndex = index
                    return h;
                })
                if(this.approvalType == 'all'){
                    this.unreadTotal = this.unreadList.length
                }
                if(this.tabsValue == 'unread'){
                    this.$nextTick(()=>{
                        this.details = this.unreadList[activeIndex] || {}
                    })
                }
            }).catch(({msg}) => {
                $A.modalError(msg);
            }).finally(_ => {
                this.loadIng--;
            });
        },

        // 获取已办列表
        getDoneList(){
            this.$store.dispatch("call", {
                method: 'get',
                url: 'approve/procHistory/findTask',
                data: {
                    page:this.page,
                    page_size: this.pageSize,
                    proc_def_name: this.approvalType == 'all' ? '' : this.approvalType,
                }
            }).then(({data}) => {
                let activeId = 0;
                let activeIndex = 0;
                if( this.doneList.length == 0 || this.doneList.length == data.rows.length){
                    this.doneList?.map((res)=>{ if(res._active)  activeId = res.id  })
                }
                this.doneList = data.rows.map((h,index)=>{
                    h._active = activeId > 0 ? h.id == activeId : index == 0;
                    if(h._active) activeIndex = index
                    return h;
                })
                if(this.tabsValue == 'done'){
                    this.$nextTick(()=>{
                        this.details = this.doneList[activeIndex] || {}
                    })
                }
            }).catch(({msg}) => {
                $A.modalError(msg);
            }).finally(_ => {
                this.loadIng--;
            });
        },

        // 获取抄送列表
        getNotifyList(){
            this.$store.dispatch("call", {
                method: 'get',
                url: 'approve/procHistory/findProcNotify',
                data: {
                    page:this.page,
                    page_size: this.pageSize,
                    proc_def_name: this.approvalType == 'all' ? '' : this.approvalType,
                }
            }).then(({data}) => {
                let activeId = 0;
                let activeIndex = 0;
                if( this.notifyList.length == 0 || this.notifyList.length == data.rows.length){
                    this.notifyList?.map((res)=>{ if(res._active)  activeId = res.id  })
                }
                this.notifyList = data.rows.map((h,index)=>{
                    h._active = activeId > 0 ? h.id == activeId : index == 0;
                    if(h._active) activeIndex = index
                    return h;
                })
                if(this.tabsValue == 'notify'){
                    this.$nextTick(()=>{
                        this.details = this.notifyList[activeIndex] || {}
                    })
                }
            }).catch(({msg}) => {
                $A.modalError(msg);
            }).finally(_ => {
                this.loadIng--;
            });
        },

        // 获取我发起的
        getInitiatedList(){
            this.$store.dispatch("call", {
                method: 'post',
                url: 'approve/process/startByMyselfAll',
                data: {
                    page: this.page,
                    page_size: this.pageSize,
                    proc_def_name: this.approvalType == 'all' ? '' : this.approvalType,
                    state: this.searchState == 'all' ? '' : this.searchState
                }
            }).then(({data}) => {
                let activeId = 0;
                let activeIndex = 0;
                if( this.initiatedList.length == 0 || this.initiatedList.length == data.rows.length){
                    this.initiatedList?.map((res)=>{ if(res._active)  activeId = res.id  })
                }
                this.initiatedList = data.rows.map((h,index)=>{
                    h._active = activeId > 0 ? h.id == activeId : index == 0;
                    if(h._active) activeIndex = index
                    return h;
                })
                if(this.tabsValue == 'initiated'){
                    this.$nextTick(()=>{
                        this.details = this.initiatedList[activeIndex] || {}
                    })
                }
            }).catch(({msg}) => {
                $A.modalError(msg);
            }).finally(_ => {
                this.loadIng--;
            });
        },

        // 添加申请
        addApply(){
            this.$store.dispatch("call", {
                url: 'users/basic',
                data: {
                    userid: [ this.userInfo.userid]
                },
                skipAuthError: true
            }).then(({data}) => {
                this.addData.department_id = data[0]?.department[0] || 0;
                this.$store.dispatch("call", {
                    url: 'approve/procdef/all',
                    method: 'post',
                }).then(({data}) => {
                    this.procdefList = data.rows || [];
                    this.addTitle = this.$L("添加申请");
                    this.addShow = true;
                }).catch(({msg}) => {
                    $A.modalError(msg);
                }).finally(_ => {
                    this.loadIng--;
                });
            }).catch(({msg}) => {
                $A.modalError(msg);
            }).finally(_ => {
                this.loadIng--;
            });
        },

        // 提交发起
        onInitiate(){
            this.$refs.initiateRef.validate((valid) => {
                if (valid) {
                    this.loadIng = 1;
                    var obj = JSON.parse(JSON.stringify(this.addData))

                    obj.startTime = obj.startTime +" "+ obj.startTimeHour + ":" + obj.startTimeMinute;
                    obj.endTime = obj.endTime +" "+ obj.endTimeHour + ":" + obj.endTimeMinute;

                    if(this.addData.other){
                        obj.other = this.addData.other.map((o) =>{
                            return o.path
                        }).join(',')
                    }

                    this.$store.dispatch("call", {
                        url: 'approve/process/start',
                        data: {
                            proc_name: obj.applyType,
                            department_id: obj.department_id,
                            var: JSON.stringify(obj),
                        },
                        method: 'post',
                    }).then(({data, msg}) => {
                        $A.messageSuccess(msg);
                        this.addShow = false;
                        this.$refs.initiateRef.resetFields();
                        this.tabsClick();
                    }).catch(({msg}) => {
                        $A.modalError(msg);
                    }).finally(_ => {
                        this.loadIng--;
                    });
                }
            });
        }


    },
}
</script>

<style lang="scss">
    .page-approve .approve-details{
        border-radius: 8px;
    }
    .page-approve .ivu-tabs-nav {
        display: flex;
        width: 350px;
        @media (max-width: 1010px) {
            width: 100%;
        }
        .ivu-tabs-tab{
            font-size: 15px;
            flex:1;
            text-align: center;
        }
    }
    .page-approve-initiate .ivu-modal-body{
        padding: 16px 22px 2px !important;
    }
</style>
