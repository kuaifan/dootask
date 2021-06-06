<template>
    <div class="manage-box">
        <div class="manage-box-menu">
            <div class="manage-box-title">
                <div class="manage-box-logo"></div>
                <span>Doo Task</span>
            </div>
            <ul>
                <li @click="toggleRoute('dashboard')" :class="classNameRoute('dashboard')">
                    <Icon type="ios-speedometer-outline" />
                    <div class="menu-title">{{$L('仪表板')}}</div>
                </li>
                <li @click="toggleRoute('calendar')" :class="classNameRoute('calendar')">
                    <Icon type="ios-calendar-outline" />
                    <div class="menu-title">{{$L('日历')}}</div>
                </li>
                <li @click="toggleRoute('messenger')" :class="classNameRoute('messenger')">
                    <Icon type="ios-chatbubbles-outline" />
                    <div class="menu-title">{{$L('消息')}}</div>
                </li>
                <li @click="toggleRoute('setting/personal')" :class="classNameRoute('setting')">
                    <Icon type="ios-cog-outline" />
                    <div class="menu-title">{{$L('设置')}}</div>
                </li>
                <li class="menu-project">
                    <ul>
                        <li v-for="(item, key) in projectList" :key="key" @click="toggleRoute('project/' + item.id)" :class="classNameRoute('project/' + item.id)">{{item.name}}</li>
                    </ul>
                    <Loading v-if="loadIng > 0"/>
                </li>
            </ul>
            <Button class="manage-box-new" type="primary" icon="md-add" @click="addShow=true">{{$L('新建项目')}}</Button>
        </div>

        <div class="manage-box-main">
            <div class="manage-box-body">
                <div class="manage-box-body-content">
                    <keep-alive>
                        <router-view class="manage-box-view"></router-view>
                    </keep-alive>
                </div>
            </div>
        </div>

        <!--新建项目-->
        <Modal
            v-model="addShow"
            :title="$L('新建项目')"
            :mask-closable="false"
            class-name="simple-modal">
            <Form ref="addProject" :model="addData" :rules="addRule" label-width="auto" @submit.native.prevent>
                <FormItem prop="name" :label="$L('项目名称')">
                    <Input type="text" v-model="addData.name"></Input>
                </FormItem>
                <FormItem prop="columns" :label="$L('项目模板')">
                    <Select v-model="addData.template" @on-change="(res) => {$set(addData, 'columns', columns[res].value)}" :placeholder="$L('请选择模板')">
                        <Option v-for="(item, index) in columns" :value="index" :key="index">{{ item.label }}</Option>
                    </Select>
                </FormItem>
                <FormItem v-if="addData.columns.length > 0" :label="$L('任务分类')">
                    <div style="line-height:38px">
                        <span v-for="(item, index) in addData.columns">
                            <Tag @on-close="() => { addData.columns.splice(index, 1)}" closable size="large" color="primary">{{item}}</Tag>
                        </span>
                    </div>
                    <div style="margin-top:4px;"></div>
                    <div style="margin-bottom:-16px">
                        <Button icon="ios-add" type="dashed" @click="addColumns">{{$L('添加分类')}}</Button>
                    </div>
                </FormItem>
            </Form>
            <div slot="footer">
                <Button type="default" @click="addShow=false">{{$L('取消')}}</Button>
                <Button type="primary" :loading="loadIng > 0" @click="onAddProject">{{$L('添加')}}</Button>
            </div>
        </Modal>
    </div>
</template>

<style lang="scss" scoped>
:global {
    .manage-box {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        display: flex;
        .manage-box-menu {
            flex-grow: 0;
            flex-shrink: 0;
            width: 255px;
            height: 100%;
            background: #F4F5F7;
            display: flex;
            flex-direction: column;
            align-items: center;
            transition: all 0.2s;
            .manage-box-title {
                display: flex;
                align-items: center;
                flex-shrink: 0;
                width: 86%;
                padding: 6px;
                margin-top: 27px;
                border-radius: 8px;
                background-color: #ffffff;
                .manage-box-logo {
                    width: 36px;
                    height: 36px;
                    background: url("../../statics/images/logo.svg") no-repeat center center;
                    background-size: contain;
                }
                > span {
                    padding-left: 12px;
                    font-size: 16px;
                    font-weight: 600;
                }
            }
            > ul {
                flex: 1;
                width: 100%;
                margin-top: 16px;
                overflow: auto;
                > li {
                    display: flex;
                    align-items: center;
                    height: 38px;
                    color: #6C7D8C;
                    cursor: pointer;
                    position: relative;
                    width: 80%;
                    max-width: 100%;
                    margin: 8px auto;
                    padding: 0 4%;
                    border-radius: 4px;
                    > i {
                        opacity: 0.5;
                        font-size: 22px;
                        margin-right: 10px;
                        margin-top: -1px;
                    }
                    .menu-title {
                        white-space: nowrap;
                        overflow: hidden;
                        text-overflow: ellipsis;
                    }
                    &.menu-project {
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        height: auto;
                        padding: 14px 0 0;
                        > ul {
                            width: 100%;
                            > li {
                                position: relative;
                                list-style: none;
                                padding: 0 8px 0 30px;
                                height: 38px;
                                line-height: 38px;
                                margin: 4px auto;
                                overflow: hidden;
                                text-overflow: ellipsis;
                                white-space: nowrap;
                                border-radius: 4px;
                                color: #333333;
                                &:before {
                                    content: "";
                                    position: absolute;
                                    top: 50%;
                                    left: 8px;
                                    transform: translateY(-50%);
                                    width: 12px;
                                    height: 12px;
                                    background: url("data:image/svg+xml;base64,PHN2ZyB0PSIxNjIyMzkwODExNTQxIiBjbGFzcz0iaWNvbiIgdmlld0JveD0iMCAwIDEwMjQgMTAyNCIgdmVyc2lvbj0iMS4xIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHAtaWQ9IjI0OTk3IiB3aWR0aD0iNDgiIGhlaWdodD0iNDgiPjxwYXRoIGQ9Ik0zNjYuMTgyNCAxMDguMjM2OEw4MTIuMDMyIDQyOC4wMzJhMTAyLjQgMTAyLjQgMCAwIDEgMCAxNjYuNTAyNEwzNjYuMTgyNCA5MTQuMzI5NmExMDIuNCAxMDIuNCAwIDAgMS0xNjIuMDk5Mi04My4yNTEyVjE5MS40ODhhMTAyLjQgMTAyLjQgMCAwIDEgMTYyLjA5OTItODMuMjUxMnoiIHAtaWQ9IjI0OTk4IiBmaWxsPSIjOTk5OTk5Ij48L3BhdGg+PC9zdmc+") no-repeat center center;
                                    background-size: contain;
                                }
                                &.active {
                                    background-color: #ffffff;
                                }
                            }
                        }
                        .common-loading {
                            margin: 6px;
                            width: 22px;
                            height: 22px;
                        }
                    }
                    &.active {
                        background-color: #ffffff;
                    }
                }
            }
            .manage-box-new {
                width: 80%;
                height: 38px;
                margin-top: 16px;
                margin-bottom: 20px;
            }
        }
        .manage-box-main {
            flex: 1;
            height: 100%;
            display: flex;
            flex-direction: column;
            background: white;
            .manage-box-body {
                width: 100%;
                flex: 1;
                position: relative;
                overflow: auto;
                display: flex;
                flex-direction: column;
                .manage-box-body-content {
                    flex: 1;
                    position: relative;
                    .manage-box-view {
                        position: absolute;
                        top: 0;
                        left: 0;
                        right: 0;
                        bottom: 0;
                        overflow: auto;
                    }
                }
            }
        }
    }
}
</style>

<script>
import { mapState } from 'vuex'

export default {
    data() {
        return {
            loadIng: 0,

            curPath: this.$route.path,

            addShow: false,
            addData: {
                name: '',
                columns: [],
                template: 0,
            },
            addRule: {},

            columns: [],
        }
    },
    mounted() {
        this.$store.commit('getUserInfo');
    },
    deactivated() {
        this.addShow = false;
    },
    computed: {
        ...mapState(['projectList']),
    },
    watch: {
        '$route' (route) {
            this.curPath = route.path;
        }
    },
    methods: {
        initLanguage() {
            this.columns = [{
                label: this.$L('空白模板'),
                value: [],
            }, {
                label: this.$L('软件开发'),
                value: [this.$L('产品规划'),this.$L('前端开发'),this.$L('后端开发'),this.$L('测试'),this.$L('发布'),this.$L('其它')],
            }, {
                label: this.$L('产品开发'),
                value: [this.$L('产品计划'), this.$L('正在设计'), this.$L('正在研发'), this.$L('测试'), this.$L('准备发布'), this.$L('发布成功')],
            }];
            this.addRule = {
                name: [
                    { required: true, message: this.$L('请填写项目名称！'), trigger: 'change' },
                    { type: 'string', min: 2, message: this.$L('项目名称至少2个字！'), trigger: 'change' }
                ]
            };
        },

        toggleRoute(path) {
            this.goForward({path: '/manage/' + path});
        },

        classNameRoute(path) {
            return {
                "active": $A.leftExists(this.curPath, '/manage/' + path)
            };
        },

        addColumns() {
            this.columnsValue = "";
            $A.modalConfirm({
                render: (h) => {
                    return h('div', [
                        h('div', {
                            style: {
                                fontSize: '16px',
                                fontWeight: '500',
                                marginBottom: '20px',
                            }
                        }, this.$L('添加流程')),
                        h('TagInput', {
                            props: {
                                value: this.columnsValue,
                                autofocus: true,
                                placeholder: this.$L('请输入流程名称，多个可用英文逗号分隔。')
                            },
                            on: {
                                input: (val) => {
                                    this.columnsValue = val;
                                }
                            }
                        })
                    ])
                },
                onOk: () => {
                    if (this.columnsValue) {
                        let array = $A.trim(this.columnsValue).split(",");
                        array.forEach((name) => {
                            if ($A.trim(name)) {
                                this.addData.columns.push($A.trim(name));
                            }
                        });
                    }
                },
            })
        },

        onAddProject() {
            this.$refs.addProject.validate((valid) => {
                if (valid) {
                    this.loadIng++;
                    $A.apiAjax({
                        url: 'project/add',
                        data: this.addData,
                        complete: () => {
                            this.loadIng--;
                        },
                        success: ({ret, data, msg}) => {
                            if (ret === 1) {
                                $A.messageSuccess(msg);
                                this.addShow = false;
                                this.$refs.addProject.resetFields();
                                this.$set(this.addData, 'template', 0);
                                //
                                this.loadIng++;
                                this.$store.commit('getProjectList', () => {
                                    this.loadIng--;
                                });
                            } else {
                                $A.modalError(msg);
                            }
                        }
                    });
                }
            });
        },
    }
}
</script>
