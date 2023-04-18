<template>
    <div class="page-review">
        <PageTitle :title="$L('审批中心')"/>
        <div class="review-wrapper" ref="fileWrapper">

            <div class="review-head">
                <div class="review-nav">
                    <h1>{{$L('审批中心')}}</h1>
                </div>
                <Button v-for="item in procdefList" :loading="loadIng > 0" type="primary" @click="initiate(item)" style="margin-right:10px;">{{item.name}}</Button>
            </div>

            <Tabs :value="tabsValue" @on-click="tabsClick" style="margin: 0 20px;height: 100%;">
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
                            <listDetails v-if="!detailsShow" :data="details" @approve="tabsClick" @revocation="tabsClick"></listDetails>
                        </div>
                    </div>
                </TabPane>
                <TabPane label="已办" name="done">
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
                            <listDetails v-if="!detailsShow" :data="details" @approve="tabsClick" @revocation="tabsClick"></listDetails>
                        </div>
                    </div>
                </TabPane>
                <TabPane label="抄送我" name="notify">
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
                            <listDetails v-if="!detailsShow" :data="details" @approve="tabsClick" @revocation="tabsClick"></listDetails>
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
                            <listDetails v-if="!detailsShow" :data="details" @approve="tabsClick" @revocation="tabsClick"></listDetails>
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
        <Modal v-model="addShow"  :title="$L(addTitle)" :mask-closable="false">
            <Form ref="initiateRef" :model="addData" :rules="addRule" label-width="auto" @submit.native.prevent>
                <FormItem v-if="(addTitle || '').indexOf('班') == -1" prop="type" :label="$L('假期类型')">
                    <Select v-model="addData.type" :placeholder="$L('请选择')">
                        <Option v-for="(item, index) in selectTypes" :value="item" :key="index">{{ item }}</Option>
                    </Select>
                </FormItem>
                <FormItem prop="startTime" :label="$L('开始时间')">
                    <DatePicker type="datetime" format="yyyy-MM-dd HH:mm" 
                        v-model="addData.startTime" 
                        :value="addData.startTime" 
                        @on-change="(e)=>{ addData.startTime = e }" 
                        :placeholder="$L('请选择开始时间')" style="width: 100%"
                    ></DatePicker>
                </FormItem>
                <FormItem prop="endTime" :label="$L('结束时间')">
                    <DatePicker type="datetime" format="yyyy-MM-dd HH:mm" 
                        v-model="addData.endTime" 
                        @on-change="(e)=>{ addData.endTime = e }" 
                        :placeholder="$L('请选择结束时间')" 
                        style="width: 100%"
                    ></DatePicker>
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
export default {
    components:{list,listDetails,DrawerOverlay},
    name: "review",
    data(){
        return{
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
                {value:"all",label:"全部审批"},
                {value:"请假",label:"请假"},
                {value:"加班申请",label:"加班申请"},
            ],
            searchState:"all",
            searchStateList:[
                {value:"all",label:"全部状态"},
                {value:0,label:"待审批"},
                {value:1,label:"审批中"},
                {value:2,label:"已通过"},
                {value:3,label:"已拒绝"},
                {value:4,label:"已撤回"}
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
            addData: {
                type: '',
                startTime:"",
                endTime:"",
            },
            addRule: {
                type: { type: 'string',required: true, message: this.$L('请选择假期类型！'), trigger: 'change' },
                startTime: { type: 'string',required: true, message: this.$L('请选择开始时间！'), trigger: 'change' },
                endTime:{ type: 'string',required: true, message: this.$L('请选择结束时间！'), trigger: 'change' },
                description:{ type: 'string',required: true, message: this.$L('请选择结束时间！'), trigger: 'change' },
            },
            selectTypes:["年假","事假","病假","调休","产假","陪产假","婚假","例假","丧假","哺乳假"]
        }
    },
    mounted() {
        this.tabsValue = "initiated"
        this.tabsClick()
        this.getProcdef()
        this.getBacklogList()
    },
    methods:{

        // tab切换事件
        tabsClick(val){
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
            if( window.innerWidth < 425 ){
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
            this.addTitle = item.name;
            this.addShow = true;
        },

        // 提交发起
        onInitiate(){
            this.$refs.initiateRef.validate((valid) => {
                if (valid) {
                    this.loadIng++;
                    this.$store.dispatch("call", {
                        url: 'workflow/process/start',
                        data: {
                            proc_name:this.addTitle,
                            department_id:1,
                            var: JSON.stringify(this.addData)
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

<style scoped>

</style>
