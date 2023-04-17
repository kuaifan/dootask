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
                <TabPane :label="$L('待办')" name="backlog" style="height: 100%;">
                    <div class="review-main-search">
                        <div>
                            <Dropdown @on-click="changeTime" trigger="click">
                                <a href="javascript:void(0)">
                                    {{ timeChose }}<Icon type="ios-arrow-down"></Icon>
                                </a>
                                <template #list>
                                    <DropdownMenu>
                                        <DropdownItem name="all">{{$L('所有时间')}}</DropdownItem>
                                        <DropdownItem name="24">{{$L('最近24小时')}}</DropdownItem>
                                        <DropdownItem name="7">{{$L('最近7天')}}</DropdownItem>
                                        <DropdownItem name="30">{{$L('最近30天')}}</DropdownItem>
                                        <DropdownItem name="customize">{{$L('自定义时间')}}</DropdownItem>
                                    </DropdownMenu>
                                </template>
                            </Dropdown>

                            <Dropdown @on-click="" trigger="click">
                                <a href="javascript:void(0)">
                                    {{ timeChose }}<Icon type="ios-arrow-down"></Icon>
                                </a>
                                <template #list>
                                    <DropdownMenu>
                                        <DropdownItem name="all">{{$L('全部状态')}}</DropdownItem>
                                        <DropdownItem name="0">{{$L('审批中')}}</DropdownItem>
                                        <DropdownItem name="1">{{$L('已通过')}}</DropdownItem>
                                        <DropdownItem name="-1">{{$L('已拒绝')}}</DropdownItem>
                                        <DropdownItem name="-2">{{$L('已撤回')}}</DropdownItem>
                                        <DropdownItem name="-3">{{$L('已删除')}}</DropdownItem>
                                    </DropdownMenu>
                                </template>
                            </Dropdown>
                        </div>
                        <Dropdown @on-click="" trigger="click">
                            <a href="javascript:void(0)">
                                <Icon type="ios-arrow-down" />
                            </a>
                            <template #list>
                                <DropdownMenu>
                                    <DropdownItem name="all">{{$L('最新发起优先')}}</DropdownItem>
                                    <DropdownItem name="24">{{$L('最早发起优先')}}</DropdownItem>
                                </DropdownMenu>
                            </template>
                        </Dropdown>
                    </div>
                    <div class="review-mains">
                        <div class="review-main-left">
                            <div class="review-main-list">
                                <list></list>
                                <list></list>
                            </div>
                        </div>
                        <div class="review-main-right">
                            <listDetails></listDetails>
                        </div>
                    </div>
                </TabPane>
                <TabPane label="已办" name="name2">2</TabPane>
                <TabPane label="抄送我" name="name3">3</TabPane>
                <TabPane :label="$L('已发起')" name="initiated">
                    <div class="review-main-search">
                        <div>
                            <Dropdown @on-click="changeTime" trigger="click">
                                <a href="javascript:void(0)">
                                    {{ timeChose }}<Icon type="ios-arrow-down"></Icon>
                                </a>
                                <template #list>
                                    <DropdownMenu>
                                        <DropdownItem name="all">{{$L('全部审批')}}</DropdownItem>
                                        <DropdownItem name="24">{{$L('最近24小时')}}</DropdownItem>
                                        <DropdownItem name="7">{{$L('最近7天')}}</DropdownItem>
                                        <DropdownItem name="30">{{$L('最近30天')}}</DropdownItem>
                                        <DropdownItem name="customize">{{$L('自定义时间')}}</DropdownItem>
                                    </DropdownMenu>
                                </template>
                            </Dropdown>

                            <Dropdown @on-click="" trigger="click">
                                <a href="javascript:void(0)">
                                    {{ timeChose }}<Icon type="ios-arrow-down"></Icon>
                                </a>
                                <template #list>
                                    <DropdownMenu>
                                        <DropdownItem name="all">{{$L('全部状态')}}</DropdownItem>
                                        <DropdownItem name="0">{{$L('审批中')}}</DropdownItem>
                                        <DropdownItem name="1">{{$L('已通过')}}</DropdownItem>
                                        <DropdownItem name="-1">{{$L('已拒绝')}}</DropdownItem>
                                        <DropdownItem name="-2">{{$L('已撤回')}}</DropdownItem>
                                        <DropdownItem name="-3">{{$L('已删除')}}</DropdownItem>
                                    </DropdownMenu>
                                </template>
                            </Dropdown>
                        </div>
                        <Dropdown @on-click="" trigger="click">
                            <a href="javascript:void(0)">
                                <Icon type="ios-arrow-down" />
                            </a>
                            <template #list>
                                <DropdownMenu>
                                    <DropdownItem name="all">{{$L('最新发起优先')}}</DropdownItem>
                                    <DropdownItem name="24">{{$L('最早发起优先')}}</DropdownItem>
                                </DropdownMenu>
                            </template>
                        </Dropdown>
                    </div>
                    <div v-if="initiatedList.length==0"  style="text-align: center;line-height: 150px;">{{$L('暂无数据')}}</div>
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
            backlogList: [],
            initiatedList: [],
            details:{}
        }
    },
    mounted() {
        this.tabsValue = "initiated"
        this.tabsClick()
    },
    methods:{

        tabsClick(){
            if(this.tabsValue == 'backlog'){
                this.getBacklogList();
            }
            if(this.tabsValue == 'initiated'){
                this.getInitiatedList();
            }
        },

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

        // 列表点击事件
        clickList(item){
            this.initiatedList.map(h=>{
                h._active = false;
            })
            item._active = true;
            this.details = item
        },

        // 获取待办列表
        getBacklogList(){
            this.$store.dispatch("call", {
                method: 'get',
                url: 'workflow/process/startByMyself',
                data: {
                    page:this.page,
                    page_size: this.pageSize,
                }
            }).then(({data}) => {
                this.backlogList = []
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
                this.details = this.initiatedList[0]
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
