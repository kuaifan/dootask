<template>
    <div class="page-review">
        <PageTitle :title="$L('审批中心')"/>
        <div class="review-wrapper" ref="fileWrapper">

            <div class="review-head">
                <div class="review-nav">
                    <h1>{{$L('审批中心')}}</h1>
                </div>
                <Button :loading="loadIng > 0" type="primary" @click="" style="margin-right:10px;">{{$L('发起请假')}}</Button>
                <Button :loading="loadIng > 0" type="primary" @click="">{{$L('加班申请')}}</Button>
            </div>

            <Tabs :value="tabsValue" @on-click="tabsClick" style="margin: 0 20px;height: 100%;">
                <TabPane :label="$L('待办') + (backlogList.length > 0 ? ('('+backlogList.length+')') : '')" name="backlog" style="height: 100%;">
                    <div class="review-main-search">
                        <div style="display: flex;gap: 10px;">
                            <Select v-model="approvalType" style="width: 150px;">
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
                            <listDetails :data="details" @approve="tabsClick" @revocation="tabsClick"></listDetails>
                        </div>
                    </div>
                </TabPane>
                <TabPane label="已办" name="done">
                    <div class="review-main-search">
                        <div style="display: flex;gap: 10px;">
                            <Select v-model="approvalType" style="width: 150px;">
                                <Option v-for="item in approvalList" :value="item.value" :key="item.value">{{ item.label }}</Option>
                            </Select>
                        </div>
                    </div>
                    <div v-if="doneList.length==0" class="noData">{{$L('暂无数据')}}</div>
                    <div v-else class="review-mains">
                        <div class="review-main-left">
                            <div class="review-main-list">
                                <div @click.stop="clickList(item,key)"  v-for="(item,key) in doneList">
                                    <list :class="{ 'review-list-active': item._active }" :data="item"></list>
                               </div>
                            </div>
                        </div>
                        <div class="review-main-right">
                            <listDetails :data="details" @approve="tabsClick" @revocation="tabsClick"></listDetails>
                        </div>
                    </div>
                </TabPane>
                <TabPane label="抄送我" name="notify">
                    <div class="review-main-search">
                        <div class="review-main-search">
                            <div style="display: flex;gap: 10px;">
                                <Select v-model="approvalType" style="width: 150px;">
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
                            <listDetails :data="details" @approve="tabsClick" @revocation="tabsClick"></listDetails>
                        </div>
                    </div>
                </TabPane>
                <TabPane :label="$L('已发起')" name="initiated">
                    <div class="review-main-search">
                        <div style="display: flex;gap: 10px;">
                            <Select v-model="approvalType" style="width: 150px;">
                                <Option v-for="item in approvalList" :value="item.value" :key="item.value">{{ item.label }}</Option>
                            </Select>
                            <Select v-model="searchState" style="width: 150px;">
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
                            <listDetails :data="details" @approve="tabsClick" @revocation="tabsClick"></listDetails>
                        </div>
                    </div>
                </TabPane>
            </Tabs>

            
        </div>
    </div>
</template>

<script>
import list from "./list.vue";
import listDetails from "./details.vue";
export default {
    components:{list,listDetails},
    name: "review",
    data(){
        return{
            timeChose:this.$L('所有时间'),

            list: [],
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
                {value:"加班",label:"加班"},
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
            backlogList: [],
            doneList:[],
            notifyList:[],
            initiatedList: [],
            details:{}
        }
    },
    mounted() {
        this.tabsValue = "initiated"
        this.tabsClick()
    },
    methods:{

        changeTime(e){
            switch (e) {
                case 'all':
                    this.timeChose = this.$L('所有时间');
                    return;
                case '24':
                    this.timeChose = this.$L('最近24小时');
                    return;
                case '7':
                    this.timeChose = this.$L('最近7天');
                    return;
                case '30':
                    this.timeChose = this.$L('最近30天');
                    return;
                case 'customize':
                    this.timeChose = this.$L('自定义时间');
                    return;
            }
        },

        // tab切换事件
        tabsClick(val){
            this.tabsValue = val || this.tabsValue
            // if(this.tabsValue == 'backlog'){
                this.getBacklogList();
            // }
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
            this.details = item
        },

        // 获取待办列表
        getBacklogList(){
            this.$store.dispatch("call", {
                method: 'get',
                url: 'workflow/process/findTask',
                data: {
                    page:this.page,
                    page_size: this.pageSize,
                }
            }).then(({data}) => {
                this.backlogList =  data.rows.map((h,index)=>{
                    h._active = index == 0; 
                    return h;
                })
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
                method: 'get',
                url: 'workflow/process/startByMyself',
                data: {
                    page:this.page,
                    page_size: this.pageSize,
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
        }

        
    },
}
</script>

<style scoped>

</style>
