<template>
    <div class="project-list">
        <div class="project-head">
            <div class="project-titbox">
                <div class="project-title">
                    <h1>{{projectDetail.name}}</h1>
                    <div v-if="projectLoad > 0" class="project-load"><Loading/></div>
                </div>
                <div v-if="projectDetail.desc" class="project-subtitle">{{projectDetail.desc}}</div>
            </div>
            <div class="project-icobox">
                <ul class="project-icons">
                    <li>
                        <UserAvatar :userid="projectDetail.owner_userid" :size="36"/>
                    </li>
                    <li class="project-icon" @click="addOpen(0)">
                        <Icon type="md-add" />
                    </li>
                    <li class="project-icon">
                        <Tooltip theme="light" :always="searchText!=''" transfer>
                            <Icon type="ios-search" />
                            <div slot="content">
                                <Input v-model="searchText" placeholder="Search task..." clearable autofocus/>
                            </div>
                        </Tooltip>
                    </li>
                    <li :class="['project-icon', $store.state.projectChatShow ? 'active' : '']" @click="$store.commit('toggleProjectChatShow')">
                        <Icon type="ios-chatbubbles" />
                        <Badge :count="projectMsgUnread"></Badge>
                    </li>
                    <li class="project-icon">
                        <Dropdown @on-click="projectDropdown" transfer>
                            <Icon type="ios-more" />
                            <DropdownMenu slot="list">
                                <DropdownItem name="setting">{{$L('项目设置')}}</DropdownItem>
                                <DropdownItem name="transfer">{{$L('移交项目')}}</DropdownItem>
                                <DropdownItem name="delete" divided>{{$L('删除项目')}}</DropdownItem>
                            </DropdownMenu>
                        </Dropdown>

                    </li>
                </ul>
                <div class="project-switch">
                    <div :class="['project-switch-button', $store.state.projectListPanel ? 'menu' : '']" @click="$store.commit('toggleProjectListPanel')">
                        <div><i class="iconfont">&#xe60c;</i></div>
                        <div><i class="iconfont">&#xe66a;</i></div>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="$store.state.projectListPanel" class="project-column">
            <ul>
                <li v-for="column in projectDetail.project_column">
                    <div class="column-head">
                        <div class="column-head-title">{{column.name}}</div>
                        <div :class="['column-head-num', column.project_task.length > 0 ? 'have' : '']">{{column.project_task.length}}</div>
                    </div>
                    <ul>
                        <li v-for="item in panelTask(column.project_task)">
                            <div :class="['task-head', item.desc ? 'has-desc' : '']">
                                <div class="task-title"><pre>{{item.name}}</pre></div>
                                <Icon type="ios-more" />
                            </div>
                            <div v-if="item.desc" class="task-desc" v-html="item.desc"></div>
                            <div v-if="item.task_tag.length > 0" class="task-tags">
                                <Tag v-for="(tag, keyt) in item.task_tag" :key="keyt" :color="tag.color">{{tag.name}}</Tag>
                            </div>
                            <div class="task-users">
                                <ul>
                                    <li v-for="(user, keyu) in item.task_user" :key="keyu">
                                        <UserAvatar :userid="user.userid" size="28"/>
                                    </li>
                                </ul>
                                <div v-if="item.file_num > 0" class="task-icon">{{item.file_num}}<Icon type="ios-link-outline" /></div>
                                <div v-if="item.msg_num > 0" class="task-icon">{{item.msg_num}}<Icon type="ios-chatbubbles-outline" /></div>
                            </div>
                            <div class="task-progress">
                                <Progress :percent="item.percent" :stroke-width="6" />
                                <Tooltip
                                    v-if="item.end_at"
                                    :class="['task-time', item.today ? 'today' : '', item.overdue ? 'overdue' : '']"
                                    :delay="600"
                                    :content="item.end_at"
                                    transfer>
                                    <Icon type="ios-time-outline"/>{{ expiresFormat(item.end_at) }}
                                </Tooltip>
                            </div>
                            <em v-if="item.p_name" class="priority-color" :style="{backgroundColor:item.p_color}"></em>
                        </li>
                    </ul>
                    <div class="column-add" @click="addOpen(column.id)"><Icon type="md-add" /></div>
                </li>
            </ul>
        </div>
        <div v-else class="project-table">
            <div class="project-table-head">
                <Row class="project-row">
                    <Col span="12"># Task name</Col>
                    <Col span="3">Task Column</Col>
                    <Col span="3">Priority</Col>
                    <Col span="3">Member</Col>
                    <Col span="3">Expiration</Col>
                </Row>
            </div>
            <!--我的任务-->
            <div :class="['project-table-body', !$store.state.taskMyShow ? 'project-table-hide' : '']">
                <div @click="$store.commit('toggleTaskMyShow')">
                    <Row class="project-row">
                        <Col span="12" class="row-title">
                            <i class="iconfont">&#xe689;</i>
                            <div class="row-h1">My task</div>
                            <div class="row-num">({{myList.length}})</div>
                        </Col>
                        <Col span="3"></Col>
                        <Col span="3"></Col>
                        <Col span="3"></Col>
                        <Col span="3"></Col>
                    </Row>
                </div>
                <div class="project-rows">
                    <Row v-for="(item, key) in myList" :key="key" class="project-row">
                        <Col span="12" class="row-item">
                            <Icon v-if="item.complete_at" class="completed" type="md-checkmark-circle" />
                            <Icon v-else type="md-radio-button-off" />
                            <div class="item-title">{{item.name}}</div>
                            <div v-if="item.file_num > 0" class="item-icon">{{item.file_num}}<Icon type="ios-link-outline" /></div>
                            <div v-if="item.msg_num > 0" class="item-icon">{{item.msg_num}}<Icon type="ios-chatbubbles-outline" /></div>
                        </Col>
                        <Col span="3">{{item.column_name}}</Col>
                        <Col span="3"><TaskPriority v-if="item.p_name" :backgroundColor="item.p_color">{{item.p_name}}</TaskPriority></Col>
                        <Col span="3" class="row-member">
                            <ul>
                                <li v-for="(user, keyu) in item.task_user" :key="keyu">
                                    <UserAvatar :userid="user.userid" size="28"/>
                                </li>
                            </ul>
                        </Col>
                        <Col span="3">
                            <Tooltip
                                v-if="item.end_at"
                                :class="['task-time', item.today ? 'today' : '', item.overdue ? 'overdue' : '']"
                                :delay="600"
                                :content="item.end_at"
                                transfer>
                                {{item.end_at ? expiresFormat(item.end_at) : ''}}
                            </Tooltip>
                        </Col>
                        <em v-if="item.p_name" class="priority-color" :style="{backgroundColor:item.p_color}"></em>
                    </Row>
                </div>
                <div @click="addOpen(0)">
                    <Row class="project-row">
                        <Col span="12" class="row-add">
                            <Icon type="ios-add" /> {{$L('添加任务')}}
                        </Col>
                        <Col span="3"></Col>
                        <Col span="3"></Col>
                        <Col span="3"></Col>
                        <Col span="3"></Col>
                    </Row>
                </div>
            </div>
            <!--未完成任务-->
            <div :class="['project-table-body', !$store.state.taskUndoneShow ? 'project-table-hide' : '']">
                <div @click="$store.commit('toggleTaskUndoneShow')">
                    <Row class="project-row">
                        <Col span="12" class="row-title">
                            <i class="iconfont">&#xe689;</i>
                            <div class="row-h1">Undone</div>
                            <div class="row-num">({{undoneList.length}})</div>
                        </Col>
                        <Col span="3"></Col>
                        <Col span="3"></Col>
                        <Col span="3"></Col>
                        <Col span="3"></Col>
                    </Row>
                </div>
                <div class="project-rows">
                    <Row v-for="(item, key) in undoneList" :key="key" class="project-row">
                        <Col span="12" class="row-item">
                            <Icon v-if="item.complete_at" class="completed" type="md-checkmark-circle" />
                            <Icon v-else type="md-radio-button-off" />
                            <div class="item-title">{{item.name}}</div>
                            <div v-if="item.file_num > 0" class="item-icon">{{item.file_num}}<Icon type="ios-link-outline" /></div>
                            <div v-if="item.msg_num > 0" class="item-icon">{{item.msg_num}}<Icon type="ios-chatbubbles-outline" /></div>
                        </Col>
                        <Col span="3">{{item.column_name}}</Col>
                        <Col span="3"><TaskPriority v-if="item.p_name" :backgroundColor="item.p_color">{{item.p_name}}</TaskPriority></Col>
                        <Col span="3" class="row-member">
                            <ul>
                                <li v-for="(user, keyu) in item.task_user" :key="keyu">
                                    <UserAvatar :userid="user.userid" size="28"/>
                                </li>
                            </ul>
                        </Col>
                        <Col span="3">
                            <Tooltip
                                v-if="item.end_at"
                                :class="['task-time', item.today ? 'today' : '', item.overdue ? 'overdue' : '']"
                                :delay="600"
                                :content="item.end_at"
                                transfer>
                                {{item.end_at ? expiresFormat(item.end_at) : ''}}
                            </Tooltip>
                        </Col>
                        <em v-if="item.p_name" class="priority-color" :style="{backgroundColor:item.p_color}"></em>
                    </Row>
                </div>
            </div>
            <!--已完成任务-->
            <div :class="['project-table-body', !$store.state.taskCompletedShow ? 'project-table-hide' : '']">
                <div @click="$store.commit('toggleTaskCompletedShow')">
                    <Row class="project-row">
                        <Col span="12" class="row-title">
                            <i class="iconfont">&#xe689;</i>
                            <div class="row-h1">Completed</div>
                            <div class="row-num">({{completedList.length}})</div>
                        </Col>
                        <Col span="3"></Col>
                        <Col span="3"></Col>
                        <Col span="3"></Col>
                        <Col span="3"></Col>
                    </Row>
                </div>
                <div class="project-rows">
                    <Row v-for="(item, key) in completedList" :key="key" class="project-row">
                        <Col span="12" class="row-item">
                            <Icon v-if="item.complete_at" class="completed" type="md-checkmark-circle" />
                            <Icon v-else type="md-radio-button-off" />
                            <div class="item-title">{{item.name}}</div>
                            <div v-if="item.file_num > 0" class="item-icon">{{item.file_num}}<Icon type="ios-link-outline" /></div>
                            <div v-if="item.msg_num > 0" class="item-icon">{{item.msg_num}}<Icon type="ios-chatbubbles-outline" /></div>
                        </Col>
                        <Col span="3">{{item.column_name}}</Col>
                        <Col span="3"><TaskPriority v-if="item.p_name" :backgroundColor="item.p_color">{{item.p_name}}</TaskPriority></Col>
                        <Col span="3" class="row-member">
                            <ul>
                                <li v-for="(user, keyu) in item.task_user" :key="keyu">
                                    <UserAvatar :userid="user.userid" size="28"/>
                                </li>
                            </ul>
                        </Col>
                        <Col span="3">
                            <Tooltip
                                v-if="item.end_at"
                                :class="['task-time', item.today ? 'today' : '', item.overdue ? 'overdue' : '']"
                                :delay="600"
                                :content="item.end_at"
                                transfer>
                                {{item.end_at ? expiresFormat(item.end_at) : ''}}
                            </Tooltip>
                        </Col>
                        <em v-if="item.p_name" class="priority-color" :style="{backgroundColor:item.p_color}"></em>
                    </Row>
                </div>
            </div>
        </div>

        <!--添加任务-->
        <Modal
            v-model="addShow"
            :title="$L('添加任务')"
            :styles="{
                width: '90%',
                maxWidth: '640px'
            }"
            :mask-closable="false"
            class-name="simple-modal">
            <TaskAdd v-model="addData"/>
            <div slot="footer">
                <Button type="default" @click="addShow=false">{{$L('取消')}}</Button>
                <Button type="primary" :loading="taskLoad > 0" @click="onAddTask">{{$L('添加')}}</Button>
            </div>
        </Modal>

        <!--项目设置-->
        <Modal
            v-model="settingShow"
            :title="$L('项目设置')"
            :mask-closable="false"
            class-name="simple-modal">
            <Form ref="addProject" :model="settingData" label-width="auto" @submit.native.prevent>
                <FormItem prop="name" :label="$L('项目名称')">
                    <Input type="text" v-model="settingData.name" :maxlength="32" :placeholder="$L('必填')"></Input>
                </FormItem>
                <FormItem prop="desc" :label="$L('项目描述')">
                    <Input type="textarea" :autosize="{ minRows: 3, maxRows: 5 }" v-model="settingData.desc" :maxlength="255" :placeholder="$L('选填')"></Input>
                </FormItem>
            </Form>
            <div slot="footer">
                <Button type="default" @click="settingShow=false">{{$L('取消')}}</Button>
                <Button type="primary" :loading="settingLoad > 0" @click="onSetting">{{$L('修改')}}</Button>
            </div>
        </Modal>

        <!--移交项目-->
        <Modal
            v-model="transferShow"
            :title="$L('移交项目')"
            :mask-closable="false"
            class-name="simple-modal">
            <Form ref="addProject" :model="transferData" label-width="auto" @submit.native.prevent>
                <FormItem v-if="transferShow" prop="owner_userid" :label="$L('项目负责人')">
                    <UserInput v-model="transferData.owner_userid" :multiple-max="1" :placeholder="$L('选择项目负责人')"/>
                </FormItem>
            </Form>
            <div slot="footer">
                <Button type="default" @click="transferShow=false">{{$L('取消')}}</Button>
                <Button type="primary" :loading="transferLoad > 0" @click="onTransfer">{{$L('移交')}}</Button>
            </div>
        </Modal>
    </div>
</template>

<style lang="scss" scoped>
:global {
    .project-list {
        display: flex;
        flex-direction: column;
        .project-head {
            display: flex;
            align-items: flex-start;
            margin: 32px 32px 16px;
            .project-titbox {
                flex: 1;
                margin-bottom: 16px;
                .project-title {
                    display: flex;
                    align-items: center;
                    > h1 {
                        color: #333333;
                        font-size: 28px;
                        font-weight: 600;
                    }
                    .project-load {
                        display: flex;
                        align-items: center;
                        margin-left: 18px;
                        .common-loading {
                            width: 22px;
                            height: 22px;
                        }
                    }
                }
                .project-subtitle {
                    color: #999999;
                    margin-top: 18px;
                    line-height: 24px;
                }
            }
            .project-icobox {
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                height: 100%;
                margin-top: 2px;
                margin-left: 80px;
                .project-icons {
                    display: flex;
                    align-items: center;
                    flex-shrink: 0;
                    > li {
                        list-style: none;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        width: 36px;
                        height: 36px;
                        border-radius: 50%;
                        position: relative;
                        margin-left: 16px;
                        cursor: pointer;
                        transition: all 0.3s;
                        &:hover {
                            box-shadow: 0 0 6px #cccccc;
                        }
                        &.project-icon {
                            border-radius: 50%;
                            background-color: #F2F3F5;
                            .ivu-icon {
                                font-size: 20px;
                                width: 36px;
                                height: 36px;
                                line-height: 36px;
                            }
                            .ivu-badge {
                                position: absolute;
                                top: -6px;
                                left: 20px;
                                transform: scale(0.8);
                            }
                            &.active {
                                color: #ffffff;
                                background-color: #2d8cf0;
                            }
                        }
                    }
                }
                .project-switch {
                    display: flex;
                    justify-content: flex-end;
                    .project-switch-button {
                        display: flex;
                        align-items: center;
                        background-color: #ffffff;
                        border-radius: 6px;
                        position: relative;
                        &:before {
                            content: "";
                            position: absolute;
                            top: 0;
                            left: 0;
                            width: 50%;
                            height: 100%;
                            z-index: 0;
                            color: #2d8cf0;
                            border-radius: 6px;
                            border: 1px solid #2d8cf0;
                            background-color: #e6f7ff;
                            transition: left 0.2s;
                        }
                        > div {
                            z-index: 1;
                            width: 32px;
                            height: 30px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            border-radius: 6px;
                            cursor: pointer;
                            color: #515a6e;
                            > i {
                                font-size: 17px;
                            }
                            &:first-child {
                                color: #2d8cf0;
                            }
                        }
                        &.menu {
                            &:before {
                                left: 50%;
                            }
                            > div:first-child {
                                color: #515a6e;
                            }
                            > div:last-child {
                                color: #2d8cf0;
                            }
                        }
                    }
                }
            }
        }
        .project-column {
            display: flex;
            height: 100%;
            overflow-x: auto;
            > ul {
                display: inline-flex;
                justify-content: space-between;
                align-items: center;
                > li {
                    flex-shrink: 0;
                    list-style: none;
                    width: 260px;
                    height: 100%;
                    display: flex;
                    flex-direction: column;
                    &:first-child {
                        margin-left: 22px;
                    }
                    &:last-child {
                        margin-right: 22px;
                    }
                    .column-head {
                        display: flex;
                        align-items: center;
                        padding: 6px 10px;
                        margin: 0 10px;
                        background-color: #F2F3F5;
                        border-radius: 4px;
                        .column-head-title {
                            flex: 1;
                            font-size: 16px;
                            font-weight: 600;
                        }
                        .column-head-num {
                            font-size: 12px;
                            padding: 2px 7px;
                            line-height: 16px;
                            border-radius: 3px;
                            color: #ffffff;
                            background-color: #cccccc;
                            &.have {
                                background-color: #1C1D1E;
                            }
                        }
                    }
                    > ul {
                        flex: 1;
                        height: 0;
                        overflow: auto;
                        > li {
                            list-style: none;
                            margin: 0 10px 16px;
                            background-color: #ffffff;
                            border-radius: 12px;
                            padding: 12px;
                            transition: box-shadow 0.3s;
                            position: relative;
                            &:hover {
                                box-shadow: 0 0 10px #e6ecfa;
                            }
                            &:first-child {
                                margin-top: 16px;
                            }
                            .priority-color {
                                position: absolute;
                                top: 12px;
                                left: 0;
                                width: 3px;
                                height: 42px;
                                border-radius: 2px;
                            }
                            .task-head {
                                display: flex;
                                align-items: flex-start;
                                .task-title {
                                    flex: 1;
                                    padding-top: 1px;
                                    > pre {
                                        margin: 0;
                                        padding: 0;
                                        line-height: 1.5;
                                    }
                                }
                                .ivu-icon {
                                    font-size: 22px;
                                    color: #666666;
                                    margin-left: 8px;
                                }
                                &.has-desc {
                                    .task-title {
                                        font-weight: 600;
                                    }
                                }
                            }
                            .task-desc {
                                color: #999999;
                                margin-top: 10px;
                                line-height: 20px;
                            }
                            .task-tags {
                                margin-top: 10px;
                                .ivu-tag {

                                }
                            }
                            .task-users {
                                margin-top: 10px;
                                display: flex;
                                align-items: center;
                                > ul {
                                    flex: 1;
                                    width: 0;
                                    display: flex;
                                    align-items: center;
                                    overflow: auto;
                                    margin-right: 24px;
                                    > li {
                                        list-style: none;
                                        margin-left: -6px;
                                        &:first-child {
                                            margin-left: 0;
                                        }
                                        .common-avatar {
                                            border: 2px solid #ffffff;
                                        }
                                    }
                                }
                                .task-icon {
                                    margin-left: 6px;
                                    font-size: 12px;
                                    .ivu-icon {
                                        margin-left: 1px;
                                        font-size: 14px;
                                    }
                                }
                            }
                            .task-progress {
                                margin-top: 10px;
                                display: flex;
                                align-items: center;
                                justify-content: flex-end;
                                .task-time {
                                    flex-shrink: 0;
                                    color: #777777;
                                    background-color: #EAEDF2;
                                    padding: 1px 4px;
                                    font-size: 12px;
                                    font-weight: 500;
                                    border-radius: 3px;
                                    display: flex;
                                    align-items: center;
                                    &.overdue {
                                        font-weight: 600;
                                        color: #ffffff;
                                        background-color: #ed4014;
                                    }
                                    &.today {
                                        color: #ffffff;
                                        background-color: #ff9900;
                                    }
                                    .ivu-icon {
                                        margin-right: 3px;
                                        font-size: 14px;
                                    }
                                }
                            }
                        }
                    }
                    .column-add {
                        cursor: pointer;
                        border-radius: 6px;
                        border: 2px dashed #F1f1f1;
                        padding: 5px 12px;
                        text-align: center;
                        margin: 0 10px 18px;
                        transition: all 0.2s;
                        .ivu-icon {
                            color: #cccccc;
                            font-size: 22px;
                            transition: all 0.2s;
                        }
                        &:hover {
                            border-color: #e1e1e1;
                            .ivu-icon {
                                color: #aaaaaa;
                                transform: scale(1.1);
                            }
                        }
                    }
                }
            }
        }
        .project-table {
            height: 100%;
            overflow-y: auto;
            .project-row {
                background-color: #ffffff;
                border-bottom: 1px solid #F4F4F5;
                position: relative;
                > div {
                    display: flex;
                    align-items: center;
                    padding: 8px 12px;
                    border-right: 1px solid #F4F4F5;
                    &:first-child {
                        padding-left: 32px;
                    }
                    &:last-child {
                        border-right: 0;
                    }
                }
                .priority-color {
                    position: absolute;
                    top: 0;
                    left: 0;
                    bottom: -1px;
                    width: 3px;
                }
            }
            .project-table-head,
            .project-table-body {
                margin: 0 32px 12px;
                border-radius: 5px;
                border: 1px solid #F4F4F5;
                border-bottom: 0;
                overflow: hidden;
                &.project-table-hide {
                    .project-rows {
                        display: none;
                    }
                    .row-title {
                        .iconfont {
                            transform: rotate(-90deg);
                        }
                    }
                }
            }
            .project-table-head {
                .project-row {
                    > div {
                        color: #888888;
                        font-size: 13px;
                        font-weight: 500;
                    }
                }
            }
            .project-table-body {
                transition: box-shadow 0.3s;
                &:hover {
                    box-shadow: 0 0 10px #e6ecfa;
                }
                .project-row {
                    > div {
                        padding: 10px 12px;
                        .task-time {
                            &.overdue,
                            &.today {
                                color: #ffffff;
                                padding: 1px 5px;
                                font-size: 13px;
                                border-radius: 3px;
                            }
                            &.overdue {
                                font-weight: 600;
                                background-color: #ed4014;
                            }
                            &.today {
                                font-weight: 500;
                                background-color: #ff9900;
                            }
                        }
                        &.row-title {
                            font-size: 14px;
                            font-weight: 500;
                            color: #333333;
                            padding-left: 14px;
                            .iconfont {
                                cursor: pointer;
                                transition: transform 0.3s;
                                font-size: 12px;
                            }
                            .row-h1 {
                                padding-left: 18px;
                            }
                            .row-num {
                                color: #999999;
                                padding-left: 6px;
                            }
                        }
                        &.row-item {
                            padding-left: 24px;
                            .ivu-icon {
                                font-size: 16px;
                                color: #dddddd;
                                &.completed {
                                    color: #87d068;
                                }
                            }
                            .item-title {
                                padding: 0 22px 0 10px;
                            }
                            .item-icon {
                                font-size: 12px;
                                margin-right: 8px;
                                color: #777777;
                                .ivu-icon,
                                .iconfont {
                                    margin-left: 1px;
                                    font-size: 14px;
                                    color: #666666;
                                }
                                .iconfont {
                                    color: #999999;
                                }
                            }
                        }
                        &.row-member {
                            > ul {
                                display: flex;
                                align-items: center;
                                overflow: auto;
                                margin-left: -4px;
                                > li {
                                    list-style: none;
                                    margin-left: -6px;
                                    &:first-child {
                                        margin-left: 0;
                                    }
                                }
                            }
                        }
                        &.row-add {
                            display: flex;
                            align-items: center;
                            height: 48px;
                            cursor: pointer;
                            > i {
                                font-size: 24px;
                                color: #777777;
                                margin-left: 32px;
                                margin-right: 4px;
                            }
                        }
                    }
                }
            }
        }
    }
}
</style>

<script>
import TaskPriority from "./task-priority";
import TaskAdd from "./task-add";
import {mapState} from "vuex";
import UserInput from "../../../components/UserInput";
export default {
    name: "ProjectList",
    components: {UserInput, TaskAdd, TaskPriority},
    data() {
        return {
            nowTime: Math.round(new Date().getTime() / 1000),
            searchText: '',

            addShow: false,
            addData: {
                owner: 0,
                column_id: 0,
                times: [],
                subtasks: [],
                p_level: 0,
                p_name: '',
                p_color: '',
            },
            taskLoad: 0,

            settingShow: false,
            settingData: {},
            settingLoad: 0,

            transferShow: false,
            transferData: {},
            transferLoad: 0,
        }
    },
    mounted() {
        setInterval(() => {
            this.nowTime = Math.round(new Date().getTime() / 1000);
        }, 1000)
    },
    computed: {
        ...mapState(['userId', 'projectDetail', 'projectLoad', 'projectMsgUnread']),

        panelTask() {
            const {searchText} = this;
            return function (project_task) {
                if (searchText) {
                    return project_task.filter((task) => {
                        return $A.strExists(task.name, searchText) || $A.strExists(task.desc, searchText);
                    });
                }
                return project_task;
            }
        },

        myList() {
            const {searchText, userId, projectDetail} = this;
            const array = [];
            projectDetail.project_column.forEach(({project_task, name}) => {
                project_task.some((task) => {
                    if (searchText) {
                        if (!$A.strExists(task.name, searchText) && !$A.strExists(task.desc, searchText)) {
                            return false;
                        }
                    }
                    if (task.task_user.find(({userid}) => userid == userId)) {
                        task.column_name = name;
                        array.push(task);
                    }
                });
            });
            return array;
        },

        undoneList() {
            const {searchText, projectDetail} = this;
            const array = [];
            projectDetail.project_column.forEach(({project_task, name}) => {
                project_task.some((task) => {
                    if (searchText) {
                        if (!$A.strExists(task.name, searchText) && !$A.strExists(task.desc, searchText)) {
                            return false;
                        }
                    }
                    if (!task.complete_at) {
                        task.column_name = name;
                        array.push(task);
                    }
                });
            });
            return array;
        },

        completedList() {
            const {searchText, projectDetail} = this;
            const array = [];
            projectDetail.project_column.forEach(({project_task, name}) => {
                project_task.some((task) => {
                    if (searchText) {
                        if (!$A.strExists(task.name, searchText) && !$A.strExists(task.desc, searchText)) {
                            return false;
                        }
                    }
                    if (task.complete_at) {
                        task.column_name = name;
                        array.push(task);
                    }
                });
            });
            return array;
        },

        expiresFormat() {
            const {nowTime} = this;
            return function (date) {
                let time = Math.round(new Date(date).getTime() / 1000) - nowTime;
                if (time > 0 && time < 86400 * 4) {
                    return this.formatSeconds(time);
                }
                return this.formatTime(date)
            }
        },
    },
    methods: {
        addOpen(column_id) {
            this.$set(this.addData, 'owner', this.userId);
            this.$set(this.addData, 'column_id', column_id);
            this.$set(this.addData, 'project_id', this.projectDetail.id);
            this.addShow = true;
        },

        onAddTask() {
            this.taskLoad++;
            $A.apiAjax({
                url: 'project/task/add',
                data: this.addData,
                method: 'post',
                complete: () => {
                    this.taskLoad--;
                },
                success: ({ret, data, msg}) => {
                    if (ret === 1) {
                        $A.messageSuccess(msg);
                        this.$store.commit('getProjectDetail', this.addData.project_id);
                        this.addShow = false;
                    } else {
                        $A.modalError(msg);
                    }
                }
            });
        },

        onSetting() {
            this.settingLoad++;
            $A.apiAjax({
                url: 'project/edit',
                data: this.settingData,
                complete: () => {
                    this.settingLoad--;
                },
                success: ({ret, data, msg}) => {
                    if (ret === 1) {
                        $A.messageSuccess(msg);
                        this.$set(this.projectDetail, 'name', this.settingData.name);
                        this.$set(this.projectDetail, 'desc', this.settingData.desc);
                        this.settingShow = false;
                    } else {
                        $A.modalError(msg);
                    }
                }
            });
        },

        onTransfer() {
            this.transferLoad++;
            $A.apiAjax({
                url: 'project/transfer',
                data: {
                    project_id: this.transferData.project_id,
                    owner_userid: this.transferData.owner_userid[0],
                },
                complete: () => {
                    this.transferLoad--;
                },
                success: ({ret, data, msg}) => {
                    if (ret === 1) {
                        $A.messageSuccess(msg);
                        this.$store.commit('getProjectDetail', this.transferData.project_id);
                        this.transferShow = false;
                    } else {
                        $A.modalError(msg);
                    }
                }
            });
        },

        onDelete() {
            $A.modalConfirm({
                title: '删除项目',
                content: '你确定要删除此项目吗？',
                loading: true,
                onOk: () => {
                    $A.apiAjax({
                        url: 'project/delete',
                        data: {
                            project_id: this.projectDetail.id,
                        },
                        error: () => {
                            this.$Modal.remove();
                            $A.modalAlert('网络繁忙，请稍后再试！');
                        },
                        success: ({ret, data, msg}) => {
                            this.$Modal.remove();
                            if (ret === 1) {
                                $A.messageSuccess(msg);
                                this.goForward({path: '/manage/dashboard'}, true);
                            }else{
                                $A.modalError(msg, 301);
                            }
                        }
                    });
                }
            });
        },

        projectDropdown(name) {
            switch (name) {
                case "setting":
                    this.$set(this.settingData, 'project_id', this.projectDetail.id);
                    this.$set(this.settingData, 'name', this.projectDetail.name);
                    this.$set(this.settingData, 'desc', this.projectDetail.desc);
                    this.settingShow = true;
                    break;

                case "transfer":
                    this.$set(this.transferData, 'project_id', this.projectDetail.id);
                    this.$set(this.transferData, 'owner_userid', [this.projectDetail.owner_userid]);
                    this.transferShow = true;
                    break;

                case "delete":
                    this.onDelete();
                    break;
            }
        },

        formatTime(date) {
            let time = Math.round(new Date(date).getTime() / 1000),
                string = '';
            if ($A.formatDate('Ymd') === $A.formatDate('Ymd', time)) {
                string = $A.formatDate('H:i', time)
            } else if ($A.formatDate('Y') === $A.formatDate('Y', time)) {
                string = $A.formatDate('m-d', time)
            } else {
                string = $A.formatDate('Y-m-d', time)
            }
            return string || '';
        },

        formatBit: function formatBit(val) {
            val = +val
            return val > 9 ? val : '0' + val
        },

        formatSeconds: function formatSeconds(second) {
            let duration
            let days = Math.floor(second / 86400);
            let hours = Math.floor((second % 86400) / 3600);
            let minutes = Math.floor(((second % 86400) % 3600) / 60);
            let seconds = Math.floor(((second % 86400) % 3600) % 60);
            if (days > 0) {
                return days + "d," + this.formatBit(hours) + "h";
            }
            else if (hours > 0) duration = this.formatBit(hours) + ":" + this.formatBit(minutes) + ":" + this.formatBit(seconds);
            else if (minutes > 0) duration = this.formatBit(minutes) + ":" + this.formatBit(seconds);
            else if (seconds > 0) duration = this.formatBit(seconds) + "s";
            return duration;
        },
    }
}
</script>
