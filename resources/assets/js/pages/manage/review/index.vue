<template>
    <div class="page-review">
        <PageTitle :title="$L('审批中心')"/>
        <div class="review-wrapper" ref="fileWrapper">

            <div class="review-head">
                <div class="review-nav">
                    <h1>{{$L('审批中心')}}</h1>
                </div>
                <Button v-for="(item,key) in procdefList" :loading="loadIng > 0" :key="key" type="primary" @click="initiate(item)" style="margin-right:10px;">{{$L(item.name)}}</Button>
            </div>

            <Tabs :value="tabsValue" @on-click="tabsClick" style="margin: 0 20px;height: 100%;"  size="small">
                <TabPane :label="$L('待办') + (backlogTotal > 0 ? ('('+backlogTotal+')') : '')" name="backlog" style="height: 100%;">
                    <div class="review-main-search">
                        <div style="display: flex;gap: 10px;">
                            <Select v-model="approvalType" @on-change="tabsClick('')" style="width: 150px;">
                                <Option v-for="item in approvalList" :value="item.value" :key="item.value">{{ item.label }}</Option>
                            </Select>
                        </div>
                    </div>
                    <div class="noData" v-if="backlogList.length==0">{{$L('暂无数据')}}</div>
                    <div v-else class="review-mains">
                        <div class="review-main-left">
                            <div class="review-main-list">
                                <div @click.stop="clickList(item,key)"  v-for="(item,key) in backlogList">
                                    <list :class="{ 'review-list-active': item._active }" :data="item"></list>
                                </div>
                            </div>
                        </div>
                        <div class="review-main-right">
                            <listDetails v-if="!detailsShow && tabsValue=='backlog'" :data="details" @approve="tabsClick" @revocation="tabsClick"></listDetails>
                        </div>
                    </div>
                </TabPane>
                <TabPane :label="$L('已办')" name="done">
                    <div class="review-main-search">
                        <div style="display: flex;gap: 10px;">
                            <Select v-model="approvalType" @on-change="tabsClick('')" style="width: 150px;">
                                <Option v-for="item in approvalList" :value="item.value" :key="item.value">{{ item.label }}</Option>
                            </Select>
                        </div>
                    </div>
                    <div v-if="doneList.length==0" class="noData">{{$L('暂无数据')}}</div>
                    <div v-else class="review-mains">
                        <div class="review-main-left">
                            <div class="review-main-list">
                                <div @click.stop="clickList(item,key)"  v-for="(item,key) in doneList" >
                                    <list :class="{ 'review-list-active': item._active }" :data="item"></list>
                               </div>
                            </div>
                        </div>
                        <div class="review-main-right">
                            <listDetails v-if="!detailsShow && tabsValue=='done'" :data="details" @approve="tabsClick" @revocation="tabsClick"></listDetails>
                        </div>
                    </div>
                </TabPane>
                <TabPane :label="$L('抄送我')" name="notify">
                    <div class="review-main-search">
                        <div class="review-main-search">
                            <div style="display: flex;gap: 10px;">
                                <Select v-model="approvalType" @on-change="tabsClick('')" style="width: 150px;">
                                    <Option v-for="item in approvalList" :value="item.value" :key="item.value">{{ item.label }}</Option>
                                </Select>
                            </div>
                        </div>
                    </div>
                    <div class="noData" v-if="notifyList.length==0">{{$L('暂无数据')}}</div>
                    <div v-else class="review-mains">
                        <div class="review-main-left">
                            <div class="review-main-list">
                                <div @click.stop="clickList(item,key)"  v-for="(item,key) in notifyList">
                                    <list :class="{ 'review-list-active': item._active }" :data="item"></list>
                               </div>
                            </div>
                        </div>
                        <div class="review-main-right">
                            <listDetails v-if="!detailsShow && tabsValue=='notify'" :data="details" @approve="tabsClick" @revocation="tabsClick"></listDetails>
                        </div>
                    </div>
                </TabPane>
                <TabPane :label="$L('已发起')" name="initiated">
                    <div class="review-main-search">
                        <div style="display: flex;gap: 10px;">
                            <Select v-model="approvalType" @on-change="tabsClick('')" style="width: 150px;">
                                <Option v-for="item in approvalList" :value="item.value" :key="item.value">{{ item.label }}</Option>
                            </Select>
                            <Select v-model="searchState" @on-change="tabsClick('')" style="width: 150px;">
                                <Option v-for="item in searchStateList" :value="item.value" :key="item.value">{{ item.label }}</Option>
                            </Select>
                        </div>
                    </div>
                    <div class="noData" v-if="initiatedList.length==0">{{$L('暂无数据')}}</div>
                    <div v-else class="review-mains">
                        <div class="review-main-left">
                            <div class="review-main-list">
                                <div @click.stop="clickList(item,key)"  v-for="(item,key) in initiatedList">
                                    <list :class="{ 'review-list-active': item._active }" :data="item"></list>
                               </div>
                            </div>
                        </div>
                        <div class="review-main-right">
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
        <Modal v-model="addShow" :title="$L(addTitle)" :mask-closable="false" class="page-review-initiate">
            <Form ref="initiateRef" :model="addData" :rules="addRule" label-width="auto" @submit.native.prevent>
                <FormItem v-if="departmentList.length>1" prop="department_id" :label="$L('选择部门')">
                    <Select v-model="addData.department_id" :placeholder="$L('请选择部门')">
                        <Option v-for="(item, index) in departmentList" :value="item.id" :key="index">{{ item.name }}</Option>
                    </Select>
                </FormItem>
                <FormItem v-if="(addTitle || '').indexOf('班') == -1" prop="type" :label="$L('假期类型')">
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
import { mapState } from 'vuex'

export default {
    components:{list,listDetails,DrawerOverlay},
    name: "review",
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
            backlogTotal:0,
            backlogList: [],
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
                type: '',
                startTime: "2023-04-20",
                startTimeHour:"09",
                startTimeMinute:"00",
                endTime: "2023-04-20",
                endTimeHour:"18",
                endTimeMinute:"00",

            },
            addRule: {
                department_id:{ type: 'number',required: true, message: this.$L('请选择部门！'), trigger: 'change' },
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
            if(to.name == 'manage-review'){
                this.tabsClick()
            }
        },
        wsMsg: {
            handler(info) {
                const {type, action} = info;
                switch (type) {
                    case 'workflow':
                        if (action == 'backlog') {
                            this.tabsClick()
                        }
                        break;
                }
            },
            deep: true,
        },
    },
    mounted() {
        this.tabsValue = "backlog"
        this.tabsClick()
        this.getProcdef()
        this.getBacklogList()
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
        tabsClick(val){
            if(!val && this.__tabsClick){
                return;
            }
            this.__tabsClick = setTimeout(() => {  this.__tabsClick =null; },1000)

            this.tabsValue = val || this.tabsValue
            if(val!=""){
                this.approvalType = this.searchState = "all"
            }
            if(this.tabsValue == 'backlog'){
                this.getBacklogList();
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
            this.backlogList.map(h=>{ h._active = false; })
            this.doneList.map(h=>{ h._active = false; })
            this.notifyList.map(h=>{ h._active = false; })
            this.initiatedList.map(h=>{ h._active = false; })
            item._active = true;
            // 
            if( window.innerWidth < 426 ){
                this.goForward({name: 'manage-review-details', query: { id: item.id } });
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

        // 获取列表数据
        getProcdef(){
            this.$store.dispatch("call", {
                url: 'workflow/procdef/all',
                method: 'post',
            }).then(({data}) => {
                this.procdefList = data.rows || [];
            }).catch(({msg}) => {
                $A.modalError(msg);
            }).finally(_ => {
                this.loadIng--;
            });
        },

        // 获取待办列表
        getBacklogList(){
            this.$store.dispatch("call", {
                method: 'get',
                url: 'workflow/process/findTask',
                data: {
                    page:this.page,
                    page_size: this.pageSize,
                    proc_def_name: this.approvalType == 'all' ? '' : this.approvalType,
                }
            }).then(({data}) => {
                this.backlogList =  data.rows.map((h,index)=>{
                    h._active = index == 0; 
                    return h;
                })
                if(this.approvalType == 'all'){
                    this.backlogTotal = this.backlogList.length
                }
                if(this.tabsValue == 'backlog'){
                    this.$nextTick(()=>{
                        this.details = this.backlogList[0] || {}
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
                url: 'workflow/procHistory/findTask',
                data: {
                    page:this.page,
                    page_size: this.pageSize,
                    proc_def_name: this.approvalType == 'all' ? '' : this.approvalType,
                }
            }).then(({data}) => {
                this.doneList =  data.rows.map((h,index)=>{
                    h._active = index == 0; 
                    return h;
                })
                if(this.tabsValue == 'done'){
                    this.$nextTick(()=>{
                        this.details = this.doneList[0] || {}
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
                url: 'workflow/procHistory/findProcNotify',
                data: {
                    page:this.page,
                    page_size: this.pageSize,
                    proc_def_name: this.approvalType == 'all' ? '' : this.approvalType,
                }
            }).then(({data}) => {
                this.notifyList =  data.rows.map((h,index)=>{
                    h._active = index == 0; 
                    return h;
                })
                if(this.tabsValue == 'notify'){
                    this.$nextTick(()=>{
                        this.details = this.notifyList[0] || {}
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
                url: 'workflow/process/startByMyselfAll',
                data: {
                    page: this.page,
                    page_size: this.pageSize,
                    proc_def_name: this.approvalType == 'all' ? '' : this.approvalType,
                    state: this.searchState == 'all' ? '' : this.searchState
                }
            }).then(({data}) => {
                this.initiatedList = data.rows.map((h,index)=>{
                    h._active = index == 0; 
                    return h;
                })
                if(this.tabsValue == 'initiated'){
                    this.$nextTick(()=>{
                        this.details = this.initiatedList[0] || {}
                    })
                }
            }).catch(({msg}) => {
                $A.modalError(msg);
            }).finally(_ => {
                this.loadIng--;
            });
        },

        // 发起
        initiate(item){
            this.$store.dispatch("call", {
                url: 'users/basic',
                data: {
                    userid: [ this.userInfo.userid]
                },
                skipAuthError: true
            }).then(({data}) => {
                this.addData.department_id = data[0]?.department[0] || 0;
                if( !this.addData.department_id ){
                    $A.modalError("您当前未加入任何部门，不能发起！");
                    return false;
                }
                this.addTitle = item.name;
                this.addShow = true;
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
                    // if((addTitle || '').indexOf('班') == -1){
                        obj.startTime = obj.startTime +" "+ obj.startTimeHour + ":" + obj.startTimeMinute;
                        obj.endTime = obj.endTime +" "+ obj.endTimeHour + ":" + obj.endTimeMinute;
                    // }
                    this.$store.dispatch("call", {
                        url: 'workflow/process/start',
                        data: {
                            proc_name:this.addTitle,
                            department_id: obj.department_id,
                            var: JSON.stringify(obj)
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
    .page-review .review-details{
        border-radius: 8px;
    }
    .page-review .ivu-tabs-nav {
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
    .page-review-initiate .ivu-modal-body{
        padding: 16px 22px 2px !important;
    }
</style>
