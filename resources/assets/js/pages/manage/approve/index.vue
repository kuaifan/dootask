<template>
    <div class="page-approve">
        <PageTitle :title="$L('审批中心')"/>
        <div class="approve-wrapper" ref="fileWrapper">
            <div class="approve-head">
                <div class="approve-nav">
                    <div class="common-nav-back" @click="goBack()"><i class="taskfont">&#xe676;</i></div>
                    <h1>{{$L('审批中心')}}</h1>
                </div>

                <Button v-show="showType == 1 && isShowIcon" @click="addApply" :loading="addLoadIng" type="primary" shape="circle" icon="md-add" class="ivu-btn-icon-only"></Button>
                <Button v-if="showType == 1 && !isShowIcon" :loading="addLoadIng" type="primary" @click="addApply">
                    <span> {{$L("添加申请")}} </span>
                </Button>

                <Button v-show="showType == 1 && userIsAdmin && !isShowIcon" @click="exportApproveShow = true">
                    <span> {{$L("导出审批数据")}} </span>
                </Button>
                <Button v-if="showType == 1 && userIsAdmin && isShowIcon" @click="exportApproveShow = true" shape="circle" class="ivu-btn-icon-only">
                    <i class="taskfont">&#xe7a8;</i>
                </Button>

                <Button v-if="userIsAdmin && !isShowIcon" @click="showType = showType == 1 ? 2 : 1">
                    <span> {{ showType == 1 ? $L("流程设置") : $L("返回") }} </span>
                </Button>
                <Button v-if="userIsAdmin && isShowIcon" @click="showType = showType == 1 ? 2 : 1" shape="circle" class="ivu-btn-icon-only">
                    <i v-if="showType == 1" class="taskfont">&#xe67b;</i>
                    <i v-if="showType == 2" class="taskfont">&#xe637;</i>
                </Button>
            </div>

            <Tabs class="page-approve-tabs" v-show="showType==1" :value="tabsValue" @on-click="tabsClick" size="small">
                <TabPane :label="$L('待办') + (unreadTotal > 0 ? ('('+unreadTotal+')') : '')" name="unread" style="height: 100%;">
                    <div class="approve-main-search">
                        <div>
                            <Select v-model="approvalType" @on-change="tabsClick(false,0)">
                                <Option v-for="item in approvalList" :value="item.value" :key="item.value">{{ item.label }}</Option>
                            </Select>
                            <Input v-model="approvalName" :placeholder="$L('请输入用户名')" ></Input>
                            <Button v-show="!isShowIcon" type="primary" :loading="loadIng" icon="ios-search" @click="tabsClick(false,0)">{{ $L('搜索') }}</Button>
                            <Button v-show="isShowIcon" type="primary" :loading="loadIng" icon="ios-search" @click="tabsClick(false,0)" />
                        </div>
                    </div>
                    <div v-if="loadIng && unreadList.length==0" class="approve-load">
                        <Loading/>
                    </div>
                    <div v-else-if="unreadList.length==0" class="noData">{{ $L('暂无数据')}}</div>
                    <div v-else class="approve-mains">
                        <div class="approve-main-left">
                            <div class="approve-main-list" @scroll="handleScroll">
                                <div @click.stop="clickList(item,key)"  v-for="(item,key) in unreadList">
                                    <list :class="{ 'approve-list-active': item._active }" :data="item"></list>
                                </div>
                                <div class="load" v-if="unreadList.length < unreadTotal">
                                    <Loading/>
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
                        <div>
                            <Select v-model="approvalType" @on-change="tabsClick(false,0)">
                                <Option v-for="item in approvalList" :value="item.value" :key="item.value">{{ item.label }}</Option>
                            </Select>
                            <Input v-model="approvalName" :placeholder="$L('请输入用户名')"></Input>
                            <Button v-show="!isShowIcon" type="primary" :loading="loadIng" icon="ios-search" @click="tabsClick(false,0)">{{ $L('搜索') }}</Button>
                            <Button v-show="isShowIcon" type="primary" :loading="loadIng" icon="ios-search" @click="tabsClick(false,0)"/>
                        </div>
                    </div>
                    <div v-if="loadIng && doneList.length==0" class="approve-load">
                        <Loading/>
                    </div>
                    <div v-else-if="doneList.length==0" class="noData">{{$L('暂无数据')}}</div>
                    <div v-else class="approve-mains">
                        <div class="approve-main-left">
                            <div class="approve-main-list" @scroll="handleScroll">
                                <div @click.stop="clickList(item,key)"  v-for="(item,key) in doneList" >
                                    <list :class="{ 'approve-list-active': item._active }" :data="item"></list>
                                </div>
                                <div class="load" v-if="doneList.length < doneTotal">
                                    <Loading/>
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
                            <div>
                                <Select v-model="approvalType" @on-change="tabsClick(false,0)">
                                    <Option v-for="item in approvalList" :value="item.value" :key="item.value">{{ item.label }}</Option>
                                </Select>
                                <Input v-model="approvalName" :placeholder="$L('请输入用户名')"></Input>
                                <Button v-show="!isShowIcon" type="primary" :loading="loadIng" icon="ios-search" @click="tabsClick(false,0)">{{ $L('搜索') }}</Button>
                                <Button v-show="isShowIcon" type="primary" :loading="loadIng" icon="ios-search" @click="tabsClick(false,0)"/>
                            </div>
                        </div>
                    </div>
                    <div v-if="loadIng && notifyList.length==0" class="approve-load">
                        <Loading/>
                    </div>
                    <div v-else-if="notifyList.length==0" class="noData">{{$L('暂无数据')}}</div>
                    <div v-else class="approve-mains">
                        <div class="approve-main-left">
                            <div class="approve-main-list" @scroll="handleScroll">
                                <div @click.stop="clickList(item,key)"  v-for="(item,key) in notifyList">
                                    <list :class="{ 'approve-list-active': item._active }" :data="item"></list>
                                </div>
                                <div class="load" v-if="notifyList.length < notifyTotal">
                                    <Loading/>
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
                        <div>
                            <Select v-model="approvalType" @on-change="tabsClick(false,0)">
                                <Option v-for="item in approvalList" :value="item.value" :key="item.value">{{ item.label }}</Option>
                            </Select>
                            <Select v-model="searchState" @on-change="tabsClick(false,0)">
                                <Option v-for="item in searchStateList" :value="item.value" :key="item.value">{{ item.label }}</Option>
                            </Select>
                            <Input v-model="approvalName" :placeholder="$L('请输入用户名')"></Input>
                            <Button v-show="!isShowIcon" type="primary" :loading="loadIng" icon="ios-search" @click="tabsClick(false,0)">{{ $L('搜索') }}</Button>
                            <Button v-show="isShowIcon" type="primary" :loading="loadIng" icon="ios-search" @click="tabsClick(false,0)"/>
                        </div>
                    </div>
                    <div v-if="loadIng && initiatedList.length==0" class="approve-load">
                        <Loading/>
                    </div>
                    <div v-else-if="initiatedList.length==0" class="noData">{{$L('暂无数据')}}</div>
                    <div v-else class="approve-mains">
                        <div class="approve-main-left">
                            <div class="approve-main-list" @scroll="handleScroll">
                                <div @click.stop="clickList(item,key)"  v-for="(item,key) in initiatedList">
                                    <list :class="{ 'approve-list-active': item._active }" :data="item"></list>
                                </div>
                                <div class="load" v-if="initiatedList.length < initiatedTotal">
                                    <Loading/>
                                </div>
                            </div>
                        </div>
                        <div class="approve-main-right">
                            <listDetails v-if="!detailsShow && tabsValue=='initiated'" :data="details" @approve="tabsClick" @revocation="tabsClick"></listDetails>
                        </div>
                    </div>
                </TabPane>
            </Tabs>

            <ApproveSetting v-show="showType!=1"/>

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

        <!--导出审批数据-->
        <ApproveExport v-model="exportApproveShow"/>

    </div>
</template>

<script>
import list from "./list.vue";
import listDetails from "./details.vue";
import DrawerOverlay from "../../../components/DrawerOverlay";
import ImgUpload from "../../../components/ImgUpload";
import ApproveSetting from "./setting";
import ApproveExport from "../components/ApproveExport";
import {mapState} from 'vuex'

export default {
    components: {list, listDetails, DrawerOverlay, ImgUpload, ApproveSetting, ApproveExport},
    name: "approve",
    data() {
        return {
            showType: 1,
            exportApproveShow: false,
            isShowIcon: false,
            modalTransferIndex: window.modalTransferIndex,

            minDate: new Date(2020, 0, 1),
            maxDate: new Date(2025, 10, 1),
            currentDate: new Date(2021, 0, 17),

            procdefList: [],
            page: 1,
            pageSize: 10,
            total: 0,
            noText: '',
            loadIng: false,
            addLoadIng: false,

            tabsValue: "",
            //
            approvalType: "all",
            approvalName: "",
            approvalList: [
                {value: "all", label: this.$L("全部审批")},
            ],
            searchState: "all",
            searchStateList: [
                {value: "all", label: this.$L("全部状态")},
                {value: 1, label: this.$L("审批中")},
                {value: 2, label: this.$L("已通过")},
                {value: 3, label: this.$L("已拒绝")},
                {value: 4, label: this.$L("已撤回")}
            ],
            //
            unreadList: [],
            unreadPage: 1,
            unreadTotal: 0,
            unreadLoad: false,
            //
            doneList: [],
            donePage: 1,
            doneLoad: false,
            doneTotal: 0,
            //
            notifyList: [],
            notifyPage: 1,
            notifyLoad: false,
            notifyTotal: 0,
            //
            initiatedList: [],
            initiatedPage: 1,
            initiatedLoad: false,
            initiatedTotal: 0,
            //
            details: {},
            detailsShow: false,
            //
            addTitle: '',
            addShow: false,
            startTimeOpen: false,
            endTimeOpen: false,
            addData: {
                department_id: 0,
                applyType: '',
                type: '',
                startTime: "2023-04-20",
                startTimeHour: "09",
                startTimeMinute: "00",
                endTime: "2023-04-20",
                endTimeHour: "18",
                endTimeMinute: "00",
                other: ""
            },
            addRule: {
                department_id: {type: 'number', required: true, message: this.$L('请选择部门！'), trigger: 'change'},
                applyType: {type: 'string', required: true, message: this.$L('请选择申请类型！'), trigger: 'change'},
                type: {type: 'string', required: true, message: this.$L('请选择假期类型！'), trigger: 'change'},
                startTime: {type: 'string', required: true, message: this.$L('请选择开始时间！'), trigger: 'change'},
                endTime: {type: 'string', required: true, message: this.$L('请选择结束时间！'), trigger: 'change'},
                description: {type: 'string', required: true, message: this.$L('请输入事由！'), trigger: 'change'},
            },
            selectTypes: ["年假", "事假", "病假", "调休", "产假", "陪产假", "婚假", "丧假", "哺乳假"],

            //
            showDateTime: false
        }
    },
    computed: {
        ...mapState(['wsMsg', 'userInfo', 'userIsAdmin', 'windowWidth']),
        departmentList() {
            let departmentNames = (this.userInfo.department_name || '').split(',');
            return (this.userInfo.department || []).map((h, index) => {
                return {
                    id: h,
                    name: departmentNames[index]
                };
            })
        }
    },
    watch: {
        '$route'(to) {
            if (to.name == 'manage-approve') {
                this.init()
            }
        },
        wsMsg: {
            handler(info) {
                const {type, action, mode, data} = info;
                switch (type) {
                    case 'approve':
                        if (action == 'unread') {
                            this.tabsClick();
                        }
                        break;
                    case 'dialog':
                        if (mode == 'add' && data?.msg?.text?.indexOf('open-approve-details') != -1) {
                            this.tabsClick();
                        }
                        break;
                }
            },
            deep: true,
        },
        addShow(val) {
            if (!val) {
                this.addData.other = ""
            }
        },
        showType(val) {
            if (val == 1) {
                this.init()
            }
        },
        windowWidth(val) {
            this.isShowIcon = val < 515
        }
    },
    activated() {
        this.showType = 1
    },
    mounted() {
        this.tabsValue = "unread"
        this.init()
    },
    methods: {
        init() {
            this.tabsClick()
            this.getProcdefList()
            if (this.tabsValue != 'unread') {
                this.getUnreadList();
            }
            this.addData.department_id = this.userInfo.department[0] || 0;
            this.addData.startTime = this.addData.endTime = this.getCurrentDate();
            this.isShowIcon = this.windowWidth < 515
        },

        // 获取流程列表
        getProcdefList() {
            return new Promise((resolve, reject) => {
                this.$store.dispatch("call", {
                    url: 'approve/procdef/all',
                    method: 'post',
                }).then(({data}) => {
                    this.procdefList = data.rows || [];
                    this.approvalList = this.procdefList.map(h => {
                        return {value: h.name, label: h.name}
                    })
                    this.approvalList.unshift({value: "all", label: this.$L("全部审批")})
                    resolve()
                }).catch(({msg}) => {
                    $A.modalError(msg);
                    reject()
                });
            });
        },

        // 获取当前时间
        getCurrentDate() {
            const today = new Date();
            const year = today.getFullYear();
            const month = String(today.getMonth() + 1).padStart(2, '0');
            const date = String(today.getDate()).padStart(2, '0');
            return `${year}-${month}-${date}`;
        },

        // tab切换事件
        tabsClick(val, time = 1000) {
            if (!val && this.__tabsClick && time > 0) {
                return;
            }
            this.__tabsClick = setTimeout(() => {
                this.__tabsClick = null;
            }, time)
            this.tabsValue = val || this.tabsValue
            if (val) {
                this.approvalType = this.searchState = "all"
                this.approvalName = ""
            }
            //
            this.detailsShow = false;
            this.loadIng = true;
            //
            if (this.tabsValue == 'unread') {
                if (val === false) {
                    this.unreadPage = 1;
                    this.unreadList = [];
                }
                this.getUnreadList();
            }
            if (this.tabsValue == 'done') {
                if (val === false) {
                    this.donePage = 1;
                    this.doneList = [];
                }
                this.getDoneList();
            }
            if (this.tabsValue == 'notify') {
                if (val === false) {
                    this.notifyPage = 1;
                    this.notifyList = [];
                }
                this.getNotifyList();
            }
            if (this.tabsValue == 'initiated') {
                if (val === false) {
                    this.initiatedPage = 1;
                    this.initiatedList = [];
                }
                this.getInitiatedList();
            }
        },

        // 列表点击事件
        clickList(item) {
            this.unreadList.map(h => {
                h._active = false;
            })
            this.doneList.map(h => {
                h._active = false;
            })
            this.notifyList.map(h => {
                h._active = false;
            })
            this.initiatedList.map(h => {
                h._active = false;
            })
            //
            if (window.innerWidth < 426) {
                this.goForward({name: 'manage-approve-details', query: {id: item.id}});
                return;
            }
            if (window.innerWidth < 1010) {
                this.detailsShow = true;
            } else {
                item._active = true;
            }
            this.details = {}
            this.$nextTick(() => {
                this.details = item
            })
        },

        // 下拉加载
        handleScroll(e) {
            if (e.target.scrollTop + e.target.clientHeight >= e.target.scrollHeight) {
                if (this.tabsValue == 'unread' && !this.unreadLoad && this.unreadList.length < this.unreadTotal) {
                    this.unreadLoad = true;
                    this.unreadPage = this.unreadPage + 1;
                    this.getUnreadList('scroll');
                }
                if (this.tabsValue == 'done' && !this.doneLoad && this.doneList.length < this.doneTotal) {
                    this.doneLoad = true;
                    this.donePage = this.donePage + 1;
                    this.getDoneList('scroll');
                }
                if (this.tabsValue == 'notify' && !this.notifyLoad && this.notifyList.length < this.notifyTotal) {
                    this.notifyLoad = true;
                    this.notifyPage = this.notifyPage + 1;
                    this.getNotifyList('scroll');
                }
                if (this.tabsValue == 'initiated' && !this.initiatedLoad && this.initiatedList.length < this.initiatedTotal) {
                    this.initiatedLoad = true;
                    this.initiatedPage = this.initiatedPage + 1;
                    this.getInitiatedList('scroll');
                }
            }
        },

        // 获取待办列表
        getUnreadList(type = 'init') {
            this.$store.dispatch("call", {
                method: 'get',
                url: 'approve/process/findTask',
                data: {
                    page: type == 'scroll' ? this.unreadPage : 1,
                    page_size: type == 'scroll' ? this.pageSize : this.unreadPage * this.pageSize,
                    proc_def_name: this.approvalType == 'all' ? '' : this.approvalType,
                    username: this.approvalName,
                }
            }).then(({data}) => {
                this.updateData('unread', data, type)
            }).catch((msg) => {
                $A.modalError(msg);
            }).finally(_ => {
                this.loadIng = false;
                this.unreadLoad = false;
            });
        },

        // 获取已办列表
        getDoneList(type = 'init') {
            this.$store.dispatch("call", {
                method: 'get',
                url: 'approve/procHistory/findTask',
                data: {
                    page: type == 'scroll' ? this.donePage : 1,
                    page_size: type == 'scroll' ? this.pageSize : this.donePage * this.pageSize,
                    proc_def_name: this.approvalType == 'all' ? '' : this.approvalType,
                    username: this.approvalName,
                }
            }).then(({data}) => {
                this.updateData('done', data, type)
            }).catch(({msg}) => {
                $A.modalError(msg);
            }).finally(_ => {
                this.loadIng = false;
                this.doneLoad = false;
            });
        },

        // 获取抄送列表
        getNotifyList(type) {
            this.$store.dispatch("call", {
                method: 'get',
                url: 'approve/procHistory/findProcNotify',
                data: {
                    page: type == 'scroll' ? this.notifyPage : 1,
                    page_size: type == 'scroll' ? this.pageSize : this.notifyPage * this.pageSize,
                    proc_def_name: this.approvalType == 'all' ? '' : this.approvalType,
                    username: this.approvalName,
                }
            }).then(({data}) => {
                this.updateData('notify', data, type)
            }).catch(({msg}) => {
                $A.modalError(msg);
            }).finally(_ => {
                this.loadIng = false;
                this.notifyLoad = false;
            });
        },

        // 获取我发起的
        getInitiatedList(type) {
            this.$store.dispatch("call", {
                method: 'post',
                url: 'approve/process/startByMyselfAll',
                data: {
                    page: type == 'scroll' ? this.initiatedPage : 1,
                    page_size: type == 'scroll' ? this.pageSize : this.initiatedPage * this.pageSize,
                    proc_def_name: this.approvalType == 'all' ? '' : this.approvalType,
                    state: this.searchState == 'all' ? '' : this.searchState,
                    username: this.approvalName,
                }
            }).then(({data}) => {
                this.updateData('initiated', data, type)
            }).catch(({msg}) => {
                $A.modalError(msg);
            }).finally(_ => {
                this.loadIng = false;
                this.initiatedLoad = false;
            });
        },

        // 添加申请
        addApply() {
            this.addLoadIng = true;
            this.$store.dispatch("call", {
                url: 'users/basic',
                data: {
                    userid: [this.userInfo.userid]
                },
                skipAuthError: true
            }).then(({data}) => {
                this.addData.department_id = data[0]?.department[0] || 0;
                this.getProcdefList().then(_ => {
                    this.addTitle = this.$L("添加申请");
                    this.addShow = true;
                    this.addLoadIng = false;
                }).catch(_ => {
                    this.addLoadIng = false;
                });
            }).catch(({msg}) => {
                this.addLoadIng = false;
                $A.modalError(msg);
            });
        },

        // 更新数据
        updateData(key, data, type) {
            let listKey = key + 'List'
            this[key + 'Total'] = data.total;
            type != 'scroll' ? (this[listKey] = data.rows) : data.rows.map(h => {
                if (this[listKey].map(item => {
                    return item.id
                }).indexOf(h.id) == -1) {
                    this[listKey].push(h)
                }
            });
            if (window.innerWidth > 1010) {
                let activeIndex = this[listKey].map((h, index) => h._active ? index : -1).filter(h => h > -1)[0] || 0
                if (this[listKey].length > 0) {
                    this[listKey][activeIndex]._active = true;
                    if (this.tabsValue == key) {
                        this.$nextTick(() => {
                            this.details = this[listKey][activeIndex] || {}
                        })
                    }
                }
            }
        },

        // 提交发起
        onInitiate() {
            this.$refs.initiateRef.validate((valid) => {
                if (valid) {
                    this.loadIng = true;
                    var obj = JSON.parse(JSON.stringify(this.addData))

                    obj.startTime = obj.startTime + " " + obj.startTimeHour + ":" + obj.startTimeMinute;
                    obj.endTime = obj.endTime + " " + obj.endTimeHour + ":" + obj.endTimeMinute;

                    if (this.addData.other) {
                        obj.other = this.addData.other.map((o) => {
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
                        this.tabsValue = 'initiated';
                        this.initiatedList.map(h => {
                            h._active = false;
                        })
                        this.$nextTick(() => {
                            this.tabsClick(false, 0);
                        })
                    }).catch(({msg}) => {
                        $A.modalError(msg);
                    }).finally(_ => {
                        this.loadIng = false;
                    });
                }
            });
        }
    }
}
</script>

<style lang="scss">
.page-approve .approve-details {
    border-radius: 8px;
}

.page-approve .ivu-tabs-nav {
    display: flex;
    width: 350px;
    @media (max-width: 1010px) {
        width: 100%;
    }

    .ivu-tabs-tab {
        font-size: 15px;
        flex: 1;
        text-align: center;
    }
}

.page-approve-initiate .ivu-modal-body {
    padding: 16px 22px 2px !important;
}
</style>
