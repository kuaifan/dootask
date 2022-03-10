<template>
    <div class="project-workflow">
        <div class="workflow-title">
            {{$L('工作流设置')}}
            <div class="title-icon">
                <Loading v-if="loadIng > 0"/>
                <Icon v-else type="ios-refresh" @click="getData"/>
            </div>
        </div>
        <div v-if="list.length > 0" class="workflow-content">
            <Collapse v-model="openIndex" accordion>
                <Panel v-for="data in list" :key="data.id" :name="'index_' + data.id">
                    <div class="workflow-item">
                        <div class="workflow-name">{{data.name}}</div>
                        <div class="workflow-status">
                            <div v-for="item in data.project_flow_item" :class="item.status">{{item.name}}</div>
                        </div>
                        <div class="workflow-save" @click.stop="">
                            <template v-if="contrast(data.project_flow_item, data.project_flow_bak)">
                                <Button :loading="loadIng > 0" type="primary" @click="onSave(data)">{{$L('保存')}}</Button>
                                <Button v-if="data.id > 0" :disabled="loadIng > 0" type="primary" ghost @click="onReduction(data, $event)">{{$L('还原')}}</Button>
                            </template>
                            <Button :disabled="loadIng > 0" type="error" ghost @click="onDelete(data)">{{$L('删除')}}</Button>
                        </div>
                    </div>
                    <div slot="content" class="taskflow-config">
                        <div class="taskflow-config-table">
                            <div class="taskflow-config-table-left-container">
                                <div class="taskflow-config-table-column-header left-header">{{$L('配置项')}}</div>
                                <div :ref="`overlay_${data.id}`" class="taskflow-config-table-column-body overlay-y">
                                    <div class="taskflow-config-table-block">
                                        <div class="taskflow-config-table-block-title">{{$L('设置状态为')}}</div>
                                        <div class="taskflow-config-table-block-item">
                                            <div>
                                                <div class="title">{{$L('开始状态')}}</div>
                                                <div class="subtitle">{{$L('新建任务默认状态')}}</div>
                                            </div>
                                        </div>
                                        <div class="taskflow-config-table-block-item">
                                            <div>
                                                <div class="title">{{$L('进行中')}}</div>
                                                <div class="subtitle">{{$L('可设置多个状态为进行中')}}</div>
                                            </div>
                                        </div>
                                        <div class="taskflow-config-table-block-item">
                                            <div>
                                                <div class="title">{{$L('验收/测试')}}</div>
                                                <div class="subtitle">{{$L('只能设置单个状态为验收/测试')}}</div>
                                            </div>
                                        </div>
                                        <div class="taskflow-config-table-block-item">
                                            <div>
                                                <div class="title">{{$L('结束状态')}}</div>
                                                <div class="subtitle">{{$L('该状态下任务自动标记完成')}}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="taskflow-config-table-block hr">
                                        <div class="taskflow-config-table-block-title">{{$L('可流转到')}}</div>
                                        <div v-for="item in data.project_flow_item" class="taskflow-config-table-block-item">
                                            <span class="transform-status-name">{{item.name}}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="taskflow-config-table-right-container">
                                <Draggable
                                    :list="data.project_flow_item"
                                    :animation="150"
                                    :disabled="!isDesktop"
                                    class="taskflow-config-table-list-wrapper"
                                    tag="div"
                                    draggable=".column-border"
                                    @sort="">
                                    <div v-for="item in data.project_flow_item" class="taskflow-config-table-status-column column-border" :class="item.status">
                                        <div
                                            class="taskflow-config-table-status-item taskflow-config-table-column-header">
                                            <div class="status-label-with-menu" :class="item.status">
                                                <div class="name">{{$L(item.name)}}</div>
                                                <EDropdown
                                                    trigger="click"
                                                    class="more"
                                                    :class="{opacity: item.userids.length > 0}"
                                                    @command="onMore($event, item)">
                                                    <div class="more-icon">
                                                        <EAvatar v-if="item.userids.length > 1" :size="20">{{item.userids.length}}</EAvatar>
                                                        <UserAvatar v-else-if="item.userids.length > 0" :userid="item.userids[0]" :size="20" tooltipDisabled/>
                                                        <Icon v-else type="ios-more" />
                                                    </div>
                                                    <EDropdownMenu slot="dropdown" class="taskflow-config-more-dropdown-menu">
                                                        <EDropdownItem v-if="item.userids.length > 0" command="user">
                                                            <div class="users">
                                                                <UserAvatar v-for="(uid, ukey) in item.userids" :key="ukey" :userid="uid" :size="28" :borderWitdh="1" :showName="item.userids.length === 1" tooltipDisabled/>
                                                            </div>
                                                        </EDropdownItem>
                                                        <EDropdownItem command="user">
                                                            <div class="item">
                                                                <Icon type="md-person" />
                                                                {{$L('状态负责人')}}
                                                            </div>
                                                        </EDropdownItem>
                                                        <EDropdownItem command="name">
                                                            <div class="item">
                                                                <Icon type="md-create" />{{$L('修改名称')}}
                                                            </div>
                                                        </EDropdownItem>
                                                        <EDropdownItem command="remove">
                                                            <div class="item delete">
                                                                <Icon type="md-trash" />{{$L('删除')}}
                                                            </div>
                                                        </EDropdownItem>
                                                    </EDropdownMenu>
                                                </EDropdown>
                                            </div>
                                        </div>
                                        <div :ref="`overlay_${data.id}`" class="taskflow-config-table-column-body overlay-y">
                                            <div class="taskflow-config-table-block">
                                                <div class="taskflow-config-table-block-title"></div>
                                                <RadioGroup v-model="item.status">
                                                    <Radio label="start"><span></span></Radio>
                                                    <Radio label="progress"><span></span></Radio>
                                                    <Radio label="test"><span></span></Radio>
                                                    <Radio label="end"><span></span></Radio>
                                                </RadioGroup>
                                            </div>
                                            <div class="taskflow-config-table-block">
                                                <div class="taskflow-config-table-block-title"></div>
                                                <CheckboxGroup v-model="item.turns" @on-change="onTurns(item)">
                                                    <Checkbox v-for="v in data.project_flow_item" :key="v.id" :label="v.id" :disabled="v.id==item.id"><span></span></Checkbox>
                                                </CheckboxGroup>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="taskflow-config-table-status-column addnew" @click="onAdd(data)">{{$L('添加状态')}}</div>
                                </Draggable>
                            </div>
                        </div>
                    </div>
                </Panel>
            </Collapse>
        </div>
        <div v-else-if="loadIng == 0" class="workflow-no">
            {{$L('当前项目还没有创建工作流')}}
            <Button type="primary" @click="onCreate">{{$L('创建工作流')}}</Button>
        </div>

        <!--状态负责人-->
        <Modal
            v-model="userShow"
            :title="`${$L('状态负责人')} (${userData.name})`"
            :mask-closable="false">
            <Form :model="userData" label-width="auto" @submit.native.prevent>
                <FormItem prop="userids" :label="$L('状态负责人')">
                    <UserInput v-model="userData.userids" :project-id="projectId" :multiple-max="5" :placeholder="$L('选择状态负责人')"/>
                </FormItem>
                <FormItem prop="usertype" :label="$L('流转模式')">
                    <RadioGroup v-model="userData.usertype">
                        <Radio label="add">{{$L('添加模式')}}</Radio>
                        <Radio label="replace">{{$L('流转模式')}}</Radio>
                        <Radio label="merge">{{$L('剔除模式')}}</Radio>
                    </RadioGroup>
                    <div v-if="userData.usertype=='replace'" class="form-tip">{{$L('流转到此状态时改变任务负责人为状态负责人，原本的任务负责人移至协助人员。')}}</div>
                    <div v-else-if="userData.usertype=='merge'" class="form-tip">{{$L('流转到此状态时改变任务负责人为状态负责人（并保留操作状态的人员），原本的任务负责人移至协助人员。')}}</div>
                    <div v-else class="form-tip">{{$L('流转到此状态时添加状态负责人至任务负责人。')}}</div>
                </FormItem>
                <FormItem prop="userlimit" :label="$L('限制负责人')">
                    <iSwitch v-model="userData.userlimit" :true-value="1" :false-value="0"/>
                    <div v-if="userData.userlimit===1" class="form-tip">{{$L('在此状态的任务状态负责人、项目管理员可以修改状态。')}}</div>
                    <div v-else class="form-tip">{{$L('在此状态的任务任务负责人、项目管理员可以修改状态。')}}</div>
                </FormItem>
            </Form>
            <div slot="footer" class="adaption">
                <Button type="default" @click="userShow=false">{{$L('取消')}}</Button>
                <Button type="primary" @click="onUser">{{$L('保存')}}</Button>
            </div>
        </Modal>
    </div>
</template>

<script>
import Draggable from "vuedraggable";
import UserInput from "../../../components/UserInput";
import {mapState} from "vuex";

export default {
    name: "ProjectWorkflow",
    components: {UserInput, Draggable},
    props: {
        projectId: {
            type: Number,
            default: 0
        },
    },
    data() {
        return {
            loadIng: 0,

            list: [],
            openIndex: "",

            userShow: false,
            userData: {},
        }
    },

    mounted() {

    },

    computed: {
        ...mapState(['isDesktop'])
    },

    watch: {
        projectId: {
            handler(val) {
                if (val) {
                    this.getData()
                }
            },
            immediate: true
        },
    },

    methods: {
        getData() {
            this.loadIng++;
            this.$store.dispatch("call", {
                url: 'project/flow/list',
                data: {
                    project_id: this.projectId,
                },
            }).then(({data}) => {
                this.loadIng--;
                this.list = data.map(item => {
                    item.project_flow_bak = JSON.stringify(item.project_flow_item)
                    return item;
                });
                this.openIndex = this.list.length === 1 ? ("index_" + this.list[0].id) : ""
                this.$nextTick(this.syncScroller);
            }).catch(({msg}) => {
                this.loadIng--;
                $A.modalError(msg);
            });
        },

        syncScroller() {
            this.list.some(data => {
                this.$refs[`overlay_${data.id}`] && this.$refs[`overlay_${data.id}`].some(el => {
                    if (!Object.keys(el.attributes).includes("sync-scroller")) {
                        el.setAttribute("sync-scroller", true);
                        el.addEventListener('scroll', ({target}) => {
                            let top = target.scrollTop;
                            let left = target.scrollLeft;
                            this.$nextTick(() => {
                                this.$refs[`overlay_${data.id}`].some(node => {
                                    if (node != el) {
                                        node.scrollTo(left, top)
                                    }
                                })
                            })
                        });
                    }
                });
            })

        },

        contrast(project_flow_item, project_flow_bak) {
            return JSON.stringify(project_flow_item) != project_flow_bak
        },

        existDiff() {
            return !!this.list.find(data => {
                return this.contrast(data.project_flow_item, data.project_flow_bak)
            });
        },

        onCreate() {
            let id = -1 * $A.randNum(1000, 10000);
            this.list.push({
                "id": id,
                "name": "Default",
                "project_flow_item": [
                    {
                        "id": -10,
                        "name": "待处理",
                        "status": "start",
                        "turns": [-10, -11, -12, -13, -14],
                        "userids": [],
                        "usertype": 'add',
                        "userlimit": 0,
                    },
                    {
                        "id": -11,
                        "name": "进行中",
                        "status": "progress",
                        "turns": [-10, -11, -12, -13, -14],
                        "userids": [],
                        "usertype": 'add',
                        "userlimit": 0,
                    },
                    {
                        "id": -12,
                        "name": "待测试",
                        "status": "test",
                        "turns": [-10, -11, -12, -13, -14],
                        "userids": [],
                        "usertype": 'add',
                        "userlimit": 0,
                    },
                    {
                        "id": -13,
                        "name": "已完成",
                        "status": "end",
                        "turns": [-10, -11, -12, -13, -14],
                        "userids": [],
                        "usertype": 'add',
                        "userlimit": 0,
                    },
                    {
                        "id": -14,
                        "name": "已取消",
                        "status": "end",
                        "turns": [-10, -11, -12, -13, -14],
                        "userids": [],
                        "usertype": 'add',
                        "userlimit": 0,
                    }
                ]
            })
            this.openIndex = "index_" + id;
            this.$nextTick(this.syncScroller);
        },

        onDelete(data) {
            $A.modalConfirm({
                title: '删除工作流',
                content: '你确定要删除工作流吗？',
                loading: true,
                onOk: () => {
                    if (data.id > 0) {
                        this.loadIng++;
                        this.$store.dispatch("call", {
                            url: 'project/flow/delete',
                            data: {
                                project_id: this.projectId,
                            },
                        }).then(({msg}) => {
                            this.loadIng--;
                            $.messageSuccess(msg);
                            this.$Modal.remove();
                            //
                            let index = this.list.findIndex(({id}) => id == data.id)
                            if (index > -1) {
                                this.list.splice(index, 1)
                            }
                        }).catch(({msg}) => {
                            this.loadIng--;
                            $A.modalError(msg, 301);
                            this.$Modal.remove();
                        });
                    } else {
                        let index = this.list.findIndex(({id}) => id == data.id)
                        if (index > -1) {
                            this.list.splice(index, 1)
                        }
                        this.$Modal.remove();
                    }
                }
            });
        },

        onMore(name, item) {
            switch (name) {
                case "user":
                    this.$set(this.userData, 'id', item.id);
                    this.$set(this.userData, 'name', item.name);
                    this.$set(this.userData, 'userids', item.userids);
                    this.$set(this.userData, 'usertype', item.usertype);
                    this.$set(this.userData, 'userlimit', item.userlimit);
                    this.userShow = true;
                    break;

                case "name":
                    this.onName(item);
                    break;

                case "remove":
                    this.onRemove(item);
                    break;
            }
        },

        onUser() {
            this.userShow = false;
            this.list.some(data => {
                let item = data.project_flow_item.find(item => item.id == this.userData.id)
                if (item) {
                    this.$set(item, 'userids', this.userData.userids)
                    this.$set(item, 'usertype', this.userData.usertype)
                    this.$set(item, 'userlimit', this.userData.userlimit)
                }
            })
        },

        onName(item) {
            $A.modalInput({
                value: item.name,
                title: "修改名称",
                placeholder: "输入流程名称",
                onOk: (name) => {
                    if (name) {
                        this.$set(item, 'name', name);
                    }
                    return true;
                }
            });
        },

        onRemove(item) {
            this.list.some(data => {
                let index = data.project_flow_item.findIndex(({id}) => id == item.id)
                if (index > -1) {
                    data.project_flow_item.splice(index, 1)
                }
            })
        },

        onTurns(item) {
            this.$set(item, 'turns', item.turns.sort())
        },

        onAdd(data) {
            $A.modalInput({
                title: "添加状态",
                placeholder: "输入状态名称",
                onOk: (name) => {
                    if (name) {
                        let id = $A.randNum(100000, 999999) * -1;
                        let turns = data.project_flow_item.map(({id}) => id)
                        data.project_flow_item.push({
                            id,
                            name,
                            status: 'end',
                            turns,
                            userids: [],
                            usertype: 'add',
                            userlimit: 0,
                        })
                        data.project_flow_item.some(item => {
                            item.turns.push(id)
                        })
                    }
                    return true;
                }
            });
        },

        onReduction(data) {
            this.$set(data, 'project_flow_item', JSON.parse(data.project_flow_bak))
        },

        onSave(formData) {
            let sort = 0;
            formData.project_flow_item.some(item => {
                item.sort = sort++
            })
            this.loadIng++;
            this.$store.dispatch("call", {
                url: 'project/flow/save',
                data: {
                    project_id: this.projectId,
                    flows: formData.project_flow_item,
                },
                method: 'post',
            }).then(({data, msg}) => {
                this.loadIng--;
                $.messageSuccess(msg)
                //
                data.project_flow_bak = JSON.stringify(data.project_flow_item)
                let index = this.list.findIndex(({id}) => id == formData.id)
                if (index > -1) {
                    this.list.splice(index, 1, data)
                } else {
                    this.list.push(data)
                }
                this.openIndex = "index_" + data.id;
                this.$nextTick(this.syncScroller);
            }).catch(({msg}) => {
                this.loadIng--;
                $A.modalError(msg);
            });
        },

        saveAll() {
            this.list.some(data => {
                if (this.contrast(data.project_flow_item, data.project_flow_bak)) {
                    this.onSave(data)
                }
            });
        },
    }
}
</script>
