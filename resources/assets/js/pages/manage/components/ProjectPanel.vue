<template>
    <div class="project-panel" :class="[tabTypeActive]">
        <PageTitle :title="projectData.name"/>
        <div class="project-titbox">
            <div class="project-title">
                <div class="project-back" @click="onBack">
                    <i class="taskfont">&#xe676;</i>
                </div>
                <h1 @click="showName">{{projectData.name}}</h1>
                <div v-if="loading" class="project-load"><Loading/></div>
            </div>
            <ul class="project-icons">
                <li class="project-avatar" :class="{'cursor-default': projectData.owner_userid !== userId}" @click="projectDropdown('user')">
                    <ul>
                        <li>
                            <UserAvatarTip :userid="projectData.owner_userid" :size="36" :borderWitdh="2" :openDelay="0">
                                <p>{{$L('项目负责人')}}</p>
                            </UserAvatarTip>
                            <Badge v-if="(windowWidth <= 980 || projectData.cacheParameter.chat) && projectUser.length > 0" type="normal" :overflow-count="999" :count="projectData.project_user.length"/>
                        </li>
                        <template v-if="!(windowWidth <= 980 || projectData.cacheParameter.chat) && projectUser.length > 0" v-for="item in projectUser">
                            <li v-if="item.userid === -1" class="more">
                                <ETooltip :disabled="$isEEUiApp || windowTouch" :content="$L('共' + (projectData.project_user.length) + '个成员')">
                                    <Icon type="ios-more"/>
                                </ETooltip>
                            </li>
                            <li v-else>
                                <UserAvatarTip :userid="item.userid" :size="36" :borderWitdh="2" :openDelay="0"/>
                            </li>
                        </template>
                    </ul>
                </li>
                <li class="project-icon" @click="addTaskOpen(0)">
                    <ETooltip :disabled="$isEEUiApp || windowTouch" :content="$L('添加任务')">
                        <Icon class="menu-icon" type="md-add" />
                    </ETooltip>
                </li>
                <li :class="['project-icon', searchText!='' ? 'active' : '']">
                    <Tooltip :always="searchText!=''" @on-popper-show="searchFocus" theme="light" :rawIndex="10">
                        <Icon class="menu-icon" type="ios-search" @click="searchFocus" />
                        <div slot="content">
                            <Input v-model="searchText" ref="searchInput" :placeholder="$L('ID、名称、描述...')" class="search-input" clearable/>
                        </div>
                    </Tooltip>
                </li>
                <li :class="['project-icon', windowLandscape && projectData.cacheParameter.chat ? 'active' : '']" @click="toggleParameter('chat')">
                    <Icon class="menu-icon" type="ios-chatbubbles" />
                    <Badge class="menu-badge" :overflow-count="999" :count="msgUnread"></Badge>
                </li>
                <li class="project-icon">
                    <EDropdown @command="projectDropdown" trigger="click" transfer>
                        <Icon class="menu-icon" type="ios-more" />
                        <EDropdownMenu v-if="projectData.owner_userid === userId" slot="dropdown">
                            <EDropdownItem command="setting">{{$L('项目设置')}}</EDropdownItem>
                            <EDropdownItem command="permissions">{{$L('权限设置')}}</EDropdownItem>
                            <EDropdownItem command="task_template">{{$L('任务模板')}}</EDropdownItem>
                            <EDropdownItem command="task_tag">{{$L('任务标签')}}</EDropdownItem>
                            <EDropdownItem command="workflow">{{$L('工作流设置')}}</EDropdownItem>
                            <EDropdownItem command="user" divided>{{$L('成员管理')}}</EDropdownItem>
                            <EDropdownItem command="invite">{{$L('邀请链接')}}</EDropdownItem>
                            <EDropdownItem command="log" divided>{{$L('项目动态')}}</EDropdownItem>
                            <EDropdownItem command="archived_task">{{$L('已归档任务')}}</EDropdownItem>
                            <EDropdownItem command="deleted_task">{{$L('已删除任务')}}</EDropdownItem>
                            <EDropdownItem command="transfer" divided>{{$L('移交项目')}}</EDropdownItem>
                            <EDropdownItem command="archived">{{$L('归档项目')}}</EDropdownItem>
                            <EDropdownItem command="delete" style="color:#f40">{{$L('删除项目')}}</EDropdownItem>
                        </EDropdownMenu>
                        <EDropdownMenu v-else slot="dropdown">
                            <EDropdownItem command="log">{{$L('项目动态')}}</EDropdownItem>
                            <EDropdownItem command="archived_task">{{$L('已归档任务')}}</EDropdownItem>
                            <EDropdownItem command="deleted_task">{{$L('已删除任务')}}</EDropdownItem>
                            <EDropdownItem command="exit" divided style="color:#f40">{{$L('退出项目')}}</EDropdownItem>
                        </EDropdownMenu>
                    </EDropdown>
                </li>
            </ul>
        </div>
        <div class="project-subbox">
            <div class="project-subtitle" @click="showDesc">
                <VMPreviewNostyle ref="descPreview" :value="projectData.desc"/>
            </div>
            <div class="project-switch">
                <div v-if="completedCount > 0" class="project-checkbox">
                    <Checkbox :value="projectData.cacheParameter.completedTask" @on-change="toggleParameter('completedTask')">{{$L('显示已完成')}}</Checkbox>
                </div>
                <div class="project-select">
                    <Cascader ref="flow" :data="flowData" @on-change="flowChange" transfer-class-name="project-panel-flow-cascader" transfer>
                        <span :class="`project-flow ${flowInfo.status || ''}`">{{ flowTitle }}</span>
                    </Cascader>
                </div>
                <div class="project-switch-button">
                    <div class="slider" :style="tabTypeStyle"></div>
                    <div @click="tabTypeChange('column')" :class="{ 'active': tabTypeActive === 'column'}"><i class="taskfont">&#xe60c;</i></div>
                    <div @click="tabTypeChange('table')" :class="{ 'active': tabTypeActive === 'table'}"><i class="taskfont">&#xe66a;</i></div>
                    <div @click="tabTypeChange('gantt')" :class="{ 'active': tabTypeActive === 'gantt'}"><i class="taskfont">&#xe797;</i></div>
                </div>
            </div>
        </div>
        <div
            v-if="tabTypeActive === 'column'"
            ref="projectColumn"
            class="project-column"
            @scroll="handleColumnScroll">
            <Draggable
                :list="columnList"
                :animation="150"
                :disabled="sortDisabled || $isEEUiApp || windowTouch"
                class="column-list"
                tag="ul"
                draggable=".column-item"
                @sort="sortUpdate(true)">
                <li
                    v-for="column in columnList"
                    :ref="`column_${column.id}`"
                    :key="column.id"
                    :data-id="column.id"
                    class="column-item">
                    <div
                        :class="['column-head', column.color ? 'custom-color' : '']"
                        :style="column.color ? {backgroundColor: column.color} : {}">
                        <div class="column-head-title">
                            <AutoTip>{{column.name}}</AutoTip>
                            <em>({{panelTask(column.tasks).length}})</em>
                        </div>
                        <div class="column-head-icon">
                            <div v-if="columnLoad[column.id] === true" class="loading"><Loading /></div>
                            <EDropdown
                                v-else
                                trigger="click"
                                size="small"
                                @command="dropColumn(column, $event)">
                                <Icon type="ios-more" />
                                <EDropdownMenu slot="dropdown" class="project-panel-more-dropdown-menu">
                                    <EDropdownItem command="title">
                                        <div class="item">
                                            <Icon type="md-create" />{{$L('修改')}}
                                        </div>
                                    </EDropdownItem>
                                    <EDropdownItem command="remove">
                                        <div class="item">
                                            <Icon type="md-trash" />{{$L('删除')}}
                                        </div>
                                    </EDropdownItem>
                                    <EDropdownItem v-for="(c, k) in $store.state.columnColorList" :key="k" :divided="k==0" :command="c">
                                        <div class="item">
                                            <i class="taskfont" :style="{color:c.color||'#ddd'}" v-html="c.color == column.color ? '&#xe61d;' : '&#xe61c;'"></i>{{$L(c.name)}}
                                        </div>
                                    </EDropdownItem>
                                </EDropdownMenu>
                            </EDropdown>
                            <Icon class="last" type="md-add" @click="addTopShow(column.id, true)" />
                        </div>
                    </div>
                    <Scrollbar
                        class="column-task"
                        class-name="task-scrollbar"
                        @on-scroll="handleTaskScroll">
                        <div v-if="!!columnTopShow[column.id]" class="task-item additem">
                            <TaskAddSimple
                                :column-id="column.id"
                                :project-id="projectId"
                                :add-top="true"
                                @on-close="addTopShow(column.id, false)"
                                @on-priority="addTaskOpen"
                                auto-active/>
                        </div>
                        <Draggable
                            :list="column.tasks"
                            :animation="150"
                            :disabled="sortDisabled || $isEEUiApp || windowTouch"
                            class="task-list"
                            draggable=".task-draggable"
                            filter=".complete"
                            group="task"
                            @sort="sortUpdate"
                            @remove="sortUpdate">
                            <div
                                v-for="item in column.tasks"
                                :data-id="item.id"
                                :class="['task-item task-draggable', item.complete_at ? 'complete' : '', taskIsHidden(item) ? 'hidden' : '']"
                                :style="taskItemStyle(item)"
                                @click="openTask(item)">
                                <template v-if="taskItemVisible(item)">
                                    <div :class="['task-head', item.desc ? 'has-desc' : '']">
                                        <div class="task-title">
                                            <!--工作流状态-->
                                            <span v-if="item.flow_item_name" :class="item.flow_item_status" @click.stop="openMenu($event, item)">{{item.flow_item_name}}</span>
                                            <!--任务描述-->
                                            <pre>{{item.name}}</pre>
                                        </div>
                                        <div class="task-menu" @click.stop="">
                                            <TaskMenu :ref="`taskMenu_${item.id}`" :task="item" icon="ios-more"/>
                                        </div>
                                    </div>
                                    <template v-if="!item.complete_at">
                                        <div v-if="item.desc" class="task-desc"><pre v-html="item.desc"></pre></div>
                                        <TaskTag v-if="item.task_tag.length > 0" class="task-tags" :tags="item.task_tag"/>
                                        <div class="task-users">
                                            <ul>
                                                <li v-for="(user, keyu) in ownerUser(item.task_user)" :key="keyu">
                                                    <UserAvatar :userid="user.userid" size="32" :borderWitdh="2" :borderColor="item.color"/>
                                                </li>
                                                <li v-if="ownerUser(item.task_user).length === 0" class="no-owner">
                                                    <Button type="primary" size="small" ghost @click.stop="openTask(item, true)">{{$L('领取任务')}}</Button>
                                                </li>
                                            </ul>
                                            <div v-if="item.file_num > 0" class="task-icon">{{item.file_num}}<Icon type="ios-link-outline" /></div>
                                            <div v-if="item.msg_num > 0" class="task-icon">{{item.msg_num}}<Icon type="ios-chatbubbles-outline" /></div>
                                        </div>
                                        <div class="task-progress">
                                            <div v-if="item.sub_num > 0" class="task-sub-num">{{item.sub_complete}}/{{item.sub_num}}</div>
                                            <Progress :percent="item.percent" :stroke-width="6" />
                                            <ETooltip
                                                v-if="item.end_at"
                                                :class="['task-time', item.today ? 'today' : '', item.overdue ? 'overdue' : '']"
                                                :disabled="$isEEUiApp || windowTouch"
                                                :open-delay="600"
                                                :content="item.end_at">
                                                <div v-if="!item.complete_at"><i class="taskfont">&#xe71d;</i>{{ expiresFormat(item.end_at) }}</div>
                                            </ETooltip>
                                        </div>
                                        <em v-if="item.p_name" class="priority-color" :style="{backgroundColor:item.p_color}"></em>
                                    </template>
                                </template>
                            </div>
                            <div class="task-item additem">
                                <TaskAddSimple
                                    :column-id="column.id"
                                    :project-id="projectId"
                                    @on-priority="addTaskOpen"/>
                            </div>
                        </Draggable>
                    </Scrollbar>
                </li>
                <li :class="['add-column', addColumnShow ? 'show-input' : '']">
                    <div class="add-column-text" @click="addColumnOpen">
                        <Icon type="md-add" />{{$L('添加列表')}}
                    </div>
                    <div class="add-column-input">
                        <Input
                            ref="addColumnName"
                            v-model="addColumnName"
                            @on-blur="addColumnBlur"
                            @on-enter="addColumnSubmit"
                            @on-clear="addColumnShow=false"
                            :placeholder="$L('列表名称，回车创建')"
                            clearable/>
                    </div>
                </li>
            </Draggable>
        </div>
        <Scrollbar
            v-else-if="tabTypeActive === 'table'"
            ref="projectTableScroll"
            class="project-table"
            enable-x
            @on-scroll="handleTaskScroll">
            <div class="project-table-head">
                <Row class="task-row">
                    <Col span="12">
                        <div class="ellipsis"># {{$L('任务名称')}}</div>
                    </Col>
                    <Col span="3">
                        <div class="ellipsis">{{$L('列表')}}</div>
                    </Col>
                    <Col span="3">
                        <div class="sort" @click="onSort('level')">
                            <div class="head-title">{{$L('优先级')}}</div>
                            <div class="head-sort">
                                <Icon :class="{on:sortField=='level' && sortType=='asc'}" type="md-arrow-dropup" />
                                <Icon :class="{on:sortField=='level' && sortType=='desc'}" type="md-arrow-dropdown" />
                            </div>
                        </div>
                    </Col>
                    <Col span="3">
                        <div class="ellipsis">{{$L('负责人')}}</div>
                    </Col>
                    <Col span="3">
                        <div class="sort" @click="onSort('end_at')">
                            <div class="head-title">{{$L('到期时间')}}</div>
                            <div class="head-sort">
                                <Icon :class="{on:sortField=='end_at' && sortType=='asc'}" type="md-arrow-dropup" />
                                <Icon :class="{on:sortField=='end_at' && sortType=='desc'}" type="md-arrow-dropdown" />
                            </div>
                        </div>
                    </Col>
                </Row>
            </div>
            <!--我的任务-->
            <div :class="['project-table-body', !projectData.cacheParameter.showMy ? 'project-table-hide' : '']">
                <Row class="task-row">
                    <Col span="12" class="row-title">
                        <i class="taskfont" @click="toggleParameter('showMy')">&#xe689;</i>
                        <div class="row-h1">{{$L('我的任务')}}</div>
                        <div class="row-num">({{myList.length}})</div>
                    </Col>
                    <Col span="3"></Col>
                    <Col span="3"></Col>
                    <Col span="3"></Col>
                    <Col span="3"></Col>
                </Row>
                <TaskRow
                    v-if="projectData.cacheParameter.showMy"
                    :list="transforTasks(myList)"
                    :task-visibilitys="taskRowVisibilitys"
                    open-key="my"
                    @on-priority="addTaskOpen"
                    fast-add-task/>
            </div>
            <!--协助的任务-->
            <div v-if="helpList.length" :class="['project-table-body', !projectData.cacheParameter.showHelp ? 'project-table-hide' : '']">
                <Row class="task-row">
                    <Col span="12" class="row-title">
                        <i class="taskfont" @click="toggleParameter('showHelp')">&#xe689;</i>
                        <div class="row-h1">{{$L('协助的任务')}}</div>
                        <div class="row-num">({{helpList.length}})</div>
                    </Col>
                    <Col span="3"></Col>
                    <Col span="3"></Col>
                    <Col span="3"></Col>
                    <Col span="3"></Col>
                </Row>
                <TaskRow
                    v-if="projectData.cacheParameter.showHelp"
                    :list="helpList"
                    :task-visibilitys="taskRowVisibilitys"
                    open-key="help"
                    @on-priority="addTaskOpen"/>
            </div>
            <!--未完成任务-->
            <div v-if="projectData.task_num > 0" :class="['project-table-body', !projectData.cacheParameter.showUndone ? 'project-table-hide' : '']">
                <Row class="task-row">
                    <Col span="12" class="row-title">
                        <i class="taskfont" @click="toggleParameter('showUndone')">&#xe689;</i>
                        <div class="row-h1">{{$L('未完成任务')}}</div>
                        <div class="row-num">({{unList.length}})</div>
                    </Col>
                    <Col span="3"></Col>
                    <Col span="3"></Col>
                    <Col span="3"></Col>
                    <Col span="3"></Col>
                </Row>
                <TaskRow
                    v-if="projectData.cacheParameter.showUndone"
                    :list="unList"
                    :task-visibilitys="taskRowVisibilitys"
                    open-key="undone"
                    @on-priority="addTaskOpen"/>
            </div>
            <!--已完成任务-->
            <div v-if="projectData.task_num > 0" :class="['project-table-body', !projectData.cacheParameter.showCompleted ? 'project-table-hide' : '']">
                <Row class="task-row">
                    <Col span="12" class="row-title">
                        <i class="taskfont" @click="toggleParameter('showCompleted')">&#xe689;</i>
                        <div class="row-h1">{{$L('已完成任务')}}</div>
                        <div class="row-num">({{completedList.length}})</div>
                    </Col>
                    <Col span="3"></Col>
                    <Col span="3"></Col>
                    <Col span="3"></Col>
                    <Col span="3">
                        <div class="ellipsis">{{projectData.task_num > 0 && projectData.cacheParameter.showCompleted ? $L('完成时间') : ''}}</div>
                    </Col>
                </Row>
                <TaskRow
                    v-if="projectData.cacheParameter.showCompleted"
                    :list="completedList"
                    :task-visibilitys="taskRowVisibilitys"
                    open-key="completed"
                    @on-priority="addTaskOpen"
                    showCompleteAt/>
            </div>
        </Scrollbar>
        <div v-else-if="tabTypeActive === 'gantt'" class="project-gantt">
            <!--甘特图-->
            <ProjectGantt :projectColumn="columnList" :flowInfo="flowInfo"/>
        </div>

        <!--项目设置-->
        <Modal
            v-model="settingShow"
            :title="$L('项目设置')"
            :mask-closable="false">
            <Form :model="settingData" v-bind="formOptions" @submit.native.prevent>
                <FormItem prop="name" :label="$L('项目名称')">
                    <Input ref="projectName" type="text" v-model="settingData.name" :maxlength="32" :placeholder="$L('必填')"></Input>
                </FormItem>
                <FormItem prop="desc" :label="$L('项目介绍')">
                    <Input ref="projectDesc" type="textarea" :autosize="{ minRows: 3, maxRows: 5 }" v-model="settingData.desc" :maxlength="255" :placeholder="`${$L('选填')} (${$L('支持 Markdown 格式')})`"></Input>
                </FormItem>
                <FormItem :label="$L('自动归档')" prop="archive_method">
                    <RadioGroup :value="settingData.archive_method" @on-change="formArchived">
                        <Radio label="system">{{$L('系统默认')}}</Radio>
                        <Radio label="custom">{{$L('自定义')}}</Radio>
                    </RadioGroup>
                    <template v-if="settingData.archive_method=='custom'">
                        <div class="form-tip">{{$L('任务完成 (*) 天后自动归档。', settingData.archive_days || 'n')}}</div>
                        <div class="setting-auto-day">
                            <Input v-model="settingData.archive_days" type="number">
                                <span slot="append">{{$L('天')}}</span>
                            </Input>
                        </div>
                    </template>

                </FormItem>
            </Form>
            <div slot="footer" class="adaption">
                <Button type="default" @click="settingShow=false">{{$L('取消')}}</Button>
                <Button type="primary" :loading="settingLoad > 0" @click="onSetting">{{$L('修改')}}</Button>
            </div>
        </Modal>

        <!--项目权限-->
        <DrawerOverlay
            v-model="permissionShow"
            placement="right"
            :size="800">
            <ProjectPermission ref="permission" v-if="permissionShow" @close="()=>{ this.permissionShow = false }" :project-id="projectId"/>
        </DrawerOverlay>

        <!--成员管理-->
        <Modal
            v-model="userShow"
            :title="$L('成员管理')"
            :mask-closable="false">
            <Form :model="userData" v-bind="formOptions" @submit.native.prevent>
                <FormItem prop="userids" :label="$L('项目成员')">
                    <UserSelect v-model="userData.userids" :uncancelable="userData.uncancelable" :multiple-max="100" :title="$L('选择项目成员')"/>
                </FormItem>
            </Form>
            <div slot="footer" class="adaption">
                <Button type="default" @click="userShow=false">{{$L('取消')}}</Button>
                <Poptip
                    v-if="userWaitRemove.length > 0"
                    confirm
                    placement="bottom"
                    style="margin-left:8px"
                    :ok-text="$L('确定')"
                    :cancel-text="$L('取消')"
                    @on-ok="onUser"
                    transfer>
                    <div slot="title">
                        <p><strong>{{$L('移除成员负责的任务将变成无负责人，')}}</strong></p>
                        <p>{{$L('注意此操作不可逆！')}}</p>
                        <ul class="project-panel-wait-remove">
                            <li>{{$L('即将移除')}}：</li>
                            <li v-for="id in userWaitRemove" :key="id">
                                <UserAvatar :userid="id" :size="20" showName/>
                            </li>
                        </ul>
                    </div>
                    <Button type="primary" :loading="userLoad > 0">{{$L('保存')}}</Button>
                </Poptip>
                <Button v-else type="primary" :loading="userLoad > 0" @click="onUser">{{$L('保存')}}</Button>
            </div>
        </Modal>

        <!--邀请链接-->
        <Modal
            v-model="inviteShow"
            :title="$L('邀请链接')"
            :mask-closable="false">
            <Form :model="inviteData" v-bind="formOptions" @submit.native.prevent>
                <FormItem :label="$L('链接地址')">
                    <Input ref="inviteInput" v-model="inviteData.url" type="textarea" :rows="3" @on-focus="inviteFocus" readonly/>
                    <div class="form-tip">
                        {{$L('可通过此链接直接加入项目。')}}
                        <Poptip
                            confirm
                            placement="bottom"
                            :ok-text="$L('确定')"
                            :cancel-text="$L('取消')"
                            @on-ok="inviteGet(true)"
                            transfer>
                            <div slot="title">
                                <p><strong>{{$L('注意：刷新将导致原来的邀请链接失效！')}}</strong></p>
                            </div>
                            <a href="javascript:void(0)">{{$L('刷新链接')}}</a>
                        </Poptip>
                    </div>
                </FormItem>
            </Form>
            <div slot="footer" class="adaption">
                <Button type="default" @click="inviteShow=false">{{$L('取消')}}</Button>
                <Button type="primary" :loading="inviteLoad > 0" @click="inviteCopy">{{$L('复制')}}</Button>
            </div>
        </Modal>

        <!--移交项目-->
        <Modal
            v-model="transferShow"
            :title="$L('移交项目')"
            :mask-closable="false">
            <Form :model="transferData" v-bind="formOptions" @submit.native.prevent>
                <FormItem prop="owner_userid" :label="$L('新项目负责人')">
                    <UserSelect v-model="transferData.owner_userid" :multiple-max="1" :title="$L('选择项目负责人')"/>
                </FormItem>
            </Form>
            <div slot="footer" class="adaption">
                <Button type="default" @click="transferShow=false">{{$L('取消')}}</Button>
                <Button type="primary" :loading="transferLoad > 0" @click="onTransfer">{{$L('移交')}}</Button>
            </div>
        </Modal>

        <!--任务模板-->
        <DrawerOverlay
            v-model="taskTemplateShow"
            placement="right"
            :size="720">
            <ProjectTaskTemplate ref="taskTemplate" v-if="taskTemplateShow" :project-id="projectId"/>
        </DrawerOverlay>

        <!--任务标签-->
        <DrawerOverlay
            v-model="taskTagShow"
            placement="right"
            :size="720">
            <ProjectTaskTag ref="taskTag" v-if="taskTagShow" :project-id="projectId"/>
        </DrawerOverlay>

        <!--工作流程设置-->
        <DrawerOverlay
            v-model="workflowShow"
            placement="right"
            :beforeClose="workflowBeforeClose"
            :size="1280">
            <ProjectWorkflow ref="workflow" v-if="workflowShow" :project-id="projectId"/>
        </DrawerOverlay>

        <!--查看项目动态-->
        <DrawerOverlay
            v-model="logShow"
            placement="right"
            :size="720">
            <ProjectLog v-if="logShow" :project-id="projectId"/>
        </DrawerOverlay>

        <!--查看归档任务-->
        <DrawerOverlay
            v-model="archivedTaskShow"
            placement="right"
            :size="1000">
            <TaskArchived v-if="archivedTaskShow" :project-id="projectId"/>
        </DrawerOverlay>

        <!--查看已删除任务-->
        <DrawerOverlay
            v-model="deletedTaskShow"
            placement="right"
            :size="1000">
            <TaskDeleted v-if="deletedTaskShow" :project-id="projectId"/>
        </DrawerOverlay>
    </div>
</template>

<script>
import {debounce} from "lodash";
import Draggable from 'vuedraggable'
import TaskPriority from "./TaskPriority";
import {mapGetters, mapState} from "vuex";
import TaskAddSimple from "./TaskAddSimple";
import TaskRow from "./TaskRow";
import TaskArchived from "./TaskArchived";
import ProjectLog from "./ProjectLog";
import DrawerOverlay from "../../../components/DrawerOverlay";
import ProjectTaskTag from "./ProjectTaskTag";
import ProjectTaskTemplate from "./ProjectTaskTemplate";
import ProjectWorkflow from "./ProjectWorkflow";
import ProjectPermission from "./ProjectPermission";
import TaskMenu from "./TaskMenu";
import TaskDeleted from "./TaskDeleted";
import TaskTag from "./ProjectTaskTag/tags.vue";
import ProjectGantt from "./ProjectGantt";
import UserSelect from "../../../components/UserSelect.vue";
import UserAvatarTip from "../../../components/UserAvatar/tip.vue";
import VMPreviewNostyle from "../../../components/VMEditor/nostyle.vue";
import emitter from "../../../store/events";

export default {
    name: "ProjectPanel",
    components: {
        VMPreviewNostyle,
        UserAvatarTip,
        UserSelect,
        TaskMenu,
        ProjectTaskTag,
        ProjectTaskTemplate,
        ProjectWorkflow,
        ProjectPermission,
        DrawerOverlay,
        ProjectLog,
        TaskArchived,
        TaskRow,
        Draggable,
        TaskAddSimple,
        TaskPriority,
        TaskDeleted,
        TaskTag,
        ProjectGantt,
    },
    data() {
        return {
            loading: false,

            nowTime: $A.dayjs().unix(),
            nowInterval: null,

            columnLoad: {},
            columnTopShow: {},

            sortField: 'end_at',
            sortType: 'desc',

            searchText: '',

            addColumnShow: false,
            addColumnLoad: false,
            addColumnName: '',

            sortData: [],
            sortDisabled: false,

            settingShow: false,
            settingData: {},
            settingLoad: 0,

            permissionShow: false,
            permissionShowData: {},
            permissionShowLoad: 0,

            userShow: false,
            userData: {},
            userLoad: 0,

            inviteShow: false,
            inviteData: {},
            inviteLoad: 0,

            transferShow: false,
            transferData: {},
            transferLoad: 0,

            taskTemplateShow: false,
            taskTagShow: false,

            workflowShow: false,
            logShow: false,
            archivedTaskShow: false,
            deletedTaskShow: false,

            flowInfo: {},
            flowList: [],

            columnVisibilitys: {},
            taskVisibilitys: {},
            taskRowVisibilitys: {},
        }
    },

    mounted() {
        this.nowInterval = setInterval(() => {
            this.nowTime = $A.dayjs().unix();
        }, 1000);
    },

    destroyed() {
        clearInterval(this.nowInterval);
    },

    computed: {
        ...mapState([
            'cacheDialogs',

            'projectId',
            'projectLoad',
            'cacheTasks',
            'cacheColumns',

            'taskCompleteTemps',

            'cacheUserBasic',

            'formOptions',
        ]),

        ...mapGetters(['projectData', 'transforTasks']),

        tabTypeActive() {
            return this.projectData.cacheParameter.menuType
        },

        tabTypeStyle() {
            const style = {}
            switch (this.tabTypeActive) {
                case 'column':
                    style.left = '0'
                    break
                case 'table':
                    style.left = '33.33%'
                    break
                case 'gantt':
                    style.left = '66.66%'
                    break
                default:
                    style.display = 'none'
            }
            return style
        },

        userWaitRemove() {
            const {userids, useridbak} = this.userData;
            if (!userids) {
                return [];
            }
            let wait = [];
            useridbak.some(id => {
                if (!userids.includes(id)) {
                    wait.push(id)
                }
            })
            return wait;
        },

        msgUnread() {
            const {cacheDialogs, projectData} = this;
            const dialog = cacheDialogs.find(({id}) => id === projectData.dialog_id);
            return $A.getDialogNum(dialog);
        },

        panelTask() {
            const {searchText, flowInfo} = this;
            return function (list) {
                if (!this.projectData.cacheParameter.completedTask) {
                    list = list.filter(({complete_at}) => {
                        return !complete_at;
                    });
                }
                if ($A.leftExists(flowInfo.value, "user:")) {
                    list = list.filter(({task_user}) => task_user.find(({userid, owner}) => userid === flowInfo.userid && owner));
                } else if (flowInfo.value > 0) {
                    list = list.filter(({flow_item_id}) => flow_item_id === flowInfo.value);
                } else if (flowInfo.value == -1) {
                    list = list.filter(({start_at}) => !start_at);
                }
                if (searchText) {
                    list = list.filter(({id, name, desc}) => {
                        return id == searchText || $A.strExists(`${name} ${desc}`, searchText)
                    });
                }
                return list;
            }
        },

        projectUser() {
            const {projectData, windowWidth} = this;
            if (!projectData.project_user) {
                return [];
            }
            let max = windowWidth > 1200 ? 8 : 3
            let list = projectData.project_user.filter(({userid}) => userid != projectData.owner_userid)
            if (list.length <= max) {
                return list
            }
            let array = list.slice(0, max - 1);
            array.push({userid: -1})
            array.push(list[list.length - 1])
            return array;
        },

        allTask() {
            const {cacheTasks, projectId} = this;
            return cacheTasks.filter(task => {
                if (task.archived_at || !task.created_at) {
                    return false;
                }
                return task.project_id == projectId
            })
        },

        columnList() {
            const {projectId, cacheColumns, allTask} = this;
            const list = $A.cloneJSON(cacheColumns).filter(({project_id}) => {
                return project_id == projectId
            }).sort((a, b) => {
                if (a.sort != b.sort) {
                    return a.sort - b.sort;
                }
                return a.id - b.id;
            });
            list.forEach((column) => {
                column.tasks = this.transforTasks(allTask.filter(task => {
                    return task.column_id == column.id;
                })).sort((a, b) => {
                    if (a.complete_at || b.complete_at) {
                        return $A.dayjs(a.complete_at) - $A.dayjs(b.complete_at);
                    }
                    if (a.sort != b.sort) {
                        return a.sort - b.sort;
                    }
                    return a.id - b.id;
                });
            })
            return list;
        },

        myList() {
            const {allTask, taskCompleteTemps, sortField, sortType} = this;
            let array = allTask.filter(task => this.myFilter(task));
            if (taskCompleteTemps.length > 0) {
                let tmps = allTask.filter(task => taskCompleteTemps.includes(task.id) && this.myFilter(task, false));
                if (tmps.length > 0) {
                    array = $A.cloneJSON(array)
                    array.push(...tmps);
                }
            }
            return array.sort((a, b) => {
                if (sortType == 'asc') {
                    [a, b] = [b, a];
                }
                if (sortField == 'level') {
                    return a.p_level - b.p_level;
                } else if (sortField == 'end_at') {
                    if (a.end_at == b.end_at) {
                        return a.p_level - b.p_level;
                    }
                    return $A.dayjs(a.end_at || "2099-12-31 23:59:59") - $A.dayjs(b.end_at || "2099-12-31 23:59:59");
                }
            });
        },

        helpList() {
            const {allTask, taskCompleteTemps, sortField, sortType} = this;
            let array = allTask.filter(task => this.helpFilter(task));
            if (taskCompleteTemps.length > 0) {
                let tmps = allTask.filter(task => taskCompleteTemps.includes(task.id) && this.helpFilter(task, false));
                if (tmps.length > 0) {
                    array = $A.cloneJSON(array)
                    array.push(...tmps);
                }
            }
            return array.sort((a, b) => {
                if (sortType == 'asc') {
                    [a, b] = [b, a];
                }
                if (sortField == 'level') {
                    return a.p_level - b.p_level;
                } else if (sortField == 'end_at') {
                    if (a.end_at == b.end_at) {
                        return a.p_level - b.p_level;
                    }
                    return $A.dayjs(a.end_at || "2099-12-31 23:59:59") - $A.dayjs(b.end_at || "2099-12-31 23:59:59");
                }
            });
        },

        unList() {
            const {allTask, searchText, sortField, sortType} = this;
            const array = allTask.filter(task => {
                if (task.parent_id > 0) {
                    return false;
                }
                if (this.flowTask(task)) {
                    return false;
                }
                if (searchText) {
                    if (task.id != searchText && !$A.strExists(task.name, searchText) && !$A.strExists(task.desc, searchText)) {
                        return false;
                    }
                }
                return !task.complete_at;
            });
            return array.sort((a, b) => {
                if (sortType == 'asc') {
                    [a, b] = [b, a];
                }
                if (sortField == 'level') {
                    return a.p_level - b.p_level;
                } else if (sortField == 'end_at') {
                    if (a.end_at == b.end_at) {
                        return a.p_level - b.p_level;
                    }
                    return $A.dayjs(a.end_at || "2099-12-31 23:59:59") - $A.dayjs(b.end_at || "2099-12-31 23:59:59");
                }
            });
        },

        completedList() {
            const {allTask, searchText} = this;
            const array = allTask.filter(task => {
                if (task.parent_id > 0) {
                    return false;
                }
                if (this.flowTask(task)) {
                    return false;
                }
                if (searchText) {
                    if (task.id != searchText && !$A.strExists(task.name, searchText) && !$A.strExists(task.desc, searchText)) {
                        return false;
                    }
                }
                return task.complete_at;
            });
            return array.sort((a, b) => {
                return $A.dayjs(b.complete_at) - $A.dayjs(a.complete_at);
            });
        },

        completedCount() {
            const {allTask} = this;
            return allTask.filter(task => {
                if (task.parent_id > 0) {
                    return false;
                }
                return task.complete_at;
            }).length;
        },

        flowTitle() {
            const {flowInfo, flowData, allTask} = this;
            if (flowInfo.value==-1) {
                return flowInfo.label;
            }
            if (flowInfo.value) {
                const item = flowData.find(item => item.value === flowInfo.value);
                return item ? item.label : flowInfo.label;
            }
            return `${this.$L('全部')} (${allTask.length})`
        },

        flowData() {
            const {flowList, allTask, cacheUserBasic} = this;
            const list = [{
                value: 0,
                label: `${this.$L('全部')} (${allTask.length})`,
                children: []
            }];
            list.push({
                value: -1,
                label: `${this.$L('未计划')} (${allTask.filter(({start_at, complete_at}) => !start_at && !complete_at).length})`,
                children: []
            });
            const flows = flowList.map(item1 => {
                return {
                    value: item1.id,
                    label: item1.name,
                    status: item1.status,
                    children: item1.project_flow_item.map(item2 => {
                        const length = allTask.filter(({flow_item_id}) => {
                            return flow_item_id == item2.id;
                        }).length
                        return {
                            value: item2.id,
                            label: `${item2.name} (${length})`,
                            status: item2.status,
                            class: item2.status
                        }
                    })
                }
            });
            if (flows.length === 1) {
                list.push(...flows[0].children)
            } else if (flows.length > 0) {
                list.push(...flows)
            }
            //
            const {project_user} = this.projectData;
            if ($A.isArray(project_user)) {
                let userItems = project_user.map((item, index) => {
                    const userInfo = cacheUserBasic.find(({userid}) => userid === item.userid) || {}
                    const length = allTask.filter(({task_user, complete_at}) => {
                        if (!this.projectData.cacheParameter.completedTask) {
                            if (complete_at) {
                                return false;
                            }
                        }
                        return task_user.find(({userid, owner}) => userid === item.userid && owner);
                    }).length
                    return {
                        value: `user:${userInfo.userid}`,
                        label: `${userInfo.nickname} (${length})`,
                        userid: userInfo.userid || 0,
                        length,
                    }
                }).filter(({userid, length}) => userid > 0 && length > 0)
                if (userItems.length > 0) {
                    userItems.sort((a, b) => {
                        return a.userid == this.userId ? -1 : 1
                    })
                    userItems = userItems.map((item, index)=>{
                        item.class = `user-${index}`
                        return item;
                    })
                    list.push(...userItems)
                }
            }
            //
            return list
        },
    },

    watch: {
        projectId: {
            handler(id) {
                if (id > 0) {
                    this.getFlowData();
                    this.handleColumnDebounce();
                }
            },
            immediate: true,
        },
        'allTask.length'() {
            this.handleColumnDebounce();
        },
        windowWidth() {
            this.handleColumnDebounce();
        },
        projectData() {
            this.sortData = this.getSort();
        },
        projectLoad(n) {
            this._loadTimeout && clearTimeout(this._loadTimeout)
            if (n > 0) {
                this._loadTimeout = setTimeout(() => {
                    this.loading = true;
                }, 1000)
            } else {
                this.loading = false;
            }
        },
    },

    methods: {
        showName() {
            if (this.windowLandscape) {
                return;
            }
            $A.modalInfo({
                language: false,
                title: this.$L('项目名称'),
                content: this.projectData.name
            })
        },

        showDesc() {
            if (this.windowLandscape) {
                return;
            }
            $A.modalInfo({
                language: false,
                title: this.$L('项目描述'),
                content: this.$refs.descPreview.$el.innerHTML
            })
        },

        searchFocus() {
            this.$nextTick(() => {
                this.$refs.searchInput.focus({
                    cursor: "end"
                });
            })
        },

        getSort() {
            const sortData = [];
            this.columnList.forEach((column) => {
                sortData.push({
                    id: column.id,
                    task: column.tasks.map(({id}) => id)
                });
            })
            return sortData;
        },

        sortUpdate(only_column) {
            const oldSort = this.sortData;
            const newSort = this.getSort();
            if (JSON.stringify(oldSort) === JSON.stringify(newSort)) {
                return;
            }
            this.sortData = newSort;
            //
            const data = {
                project_id: this.projectId,
                sort: this.sortData,
                only_column: only_column === true ? 1 : 0
            };
            this.sortDisabled = true;
            this.$store.dispatch("call", {
                url: 'project/sort',
                data,
                method: 'post',
            }).then(({msg}) => {
                $A.messageSuccess(msg);
                this.sortDisabled = false;
                //
                let sort,
                    upData = [];
                if (data.only_column) {
                    sort = -1;
                    data.sort.forEach((item) => {
                        sort++;
                        upData.push({
                            id: item.id,
                            sort,
                        })
                    })
                    this.$store.dispatch("saveColumn", upData)
                } else {
                    data.sort.forEach((item) => {
                        sort = -1;
                        upData.push(...item.task.map(id => {
                            sort++;
                            upData.push(...this.allTask.filter(task => {
                                return task.parent_id == id
                            }).map(({id}) => {
                                return {
                                    id,
                                    sort,
                                    column_id: item.id,
                                }
                            }))
                            return {
                                id,
                                sort,
                                column_id: item.id,
                            }
                        }))
                    })
                    this.$store.dispatch("saveTask", upData)
                }
            }).catch(({msg}) => {
                $A.modalError(msg);
                this.sortDisabled = false;
                this.$store.dispatch("getTaskForProject", this.projectId).catch(() => {})
            });
        },

        addTopShow(id, show) {
            this.$set(this.columnTopShow, id, show);
        },

        addTaskOpen(params) {
            emitter.emit('addTask', params);
        },

        addColumnOpen() {
            this.addColumnShow = true;
            this.$nextTick(() => {
                this.$refs.addColumnName.focus();
            })
        },

        addColumnBlur() {
            if (this.addColumnName === '') {
                this.addColumnShow = false
            }
        },

        addColumnSubmit() {
            let name = this.addColumnName.trim();
            if (name === '' || this.addColumnLoad) {
                return;
            }
            this.addColumnLoad = true
            this.$store.dispatch("call", {
                url: 'project/column/add',
                data: {
                    project_id: this.projectId,
                    name: name,
                },
                spinner: 600,
            }).then(({data, msg}) => {
                $A.messageSuccess(msg);
                this.addColumnName = '';
                this.$store.dispatch("saveColumn", data);
            }).catch(({msg}) => {
                $A.modalError(msg);
            }).finally(() => {
                this.addColumnLoad = false;
            });
        },

        dropColumn(column, command) {
            if (command === 'title') {
                this.titleColumn(column);
            }
            else if (command === 'remove') {
                this.removeColumn(column);
            }
            else if (command.name) {
                this.updateColumn(column, {
                    color: command.color
                }).catch(e => {
                    $A.modalError(e)
                });
            }
        },

        titleColumn(column) {
            $A.modalInput({
                value: column.name,
                title: "修改列表",
                placeholder: "输入列表名称",
                onOk: (value) => {
                    if (!value) {
                        return '列表名称不能为空'
                    }
                    return this.updateColumn(column, {
                        name: value
                    })
                }
            });
        },

        updateColumn(column, updata) {
            return new Promise((resolve, reject) => {
                if (this.columnLoad[column.id] === true) {
                    resolve()
                    return;
                }
                this.$set(this.columnLoad, column.id, true);
                //
                Object.keys(updata).forEach(key => this.$set(column, key, updata[key]));
                //
                this.$store.dispatch("call", {
                    url: 'project/column/update',
                    data: Object.assign(updata, {
                        column_id: column.id,
                    }),
                }).then(({data}) => {
                    this.$set(this.columnLoad, column.id, false);
                    this.$store.dispatch("saveColumn", data);
                    resolve()
                }).catch(({msg}) => {
                    this.$set(this.columnLoad, column.id, false);
                    this.$store.dispatch("getColumns", this.projectId).catch(() => {})
                    reject(msg);
                });
            })
        },

        removeColumn(column) {
            $A.modalConfirm({
                title: '删除列表',
                content: '你确定要删除列表【' + column.name + '】及列表内的任务吗？',
                loading: true,
                onOk: () => {
                    if (this.columnLoad[column.id] === true) {
                        return;
                    }
                    this.$set(this.columnLoad, column.id, true);
                    //
                    return new Promise((resolve, reject) => {
                        this.$store.dispatch("removeColumn", column.id).then(({msg}) => {
                            resolve(msg);
                        }).catch(({msg}) => {
                            reject(msg);
                        }).finally(_ => {
                            this.$set(this.columnLoad, column.id, false);
                        });
                    })
                }
            });
        },

        onSort(field) {
            this.sortField = field;
            this.sortType = this.sortType == 'desc' ? 'asc' : 'desc';
        },

        onSetting() {
            this.settingLoad++;
            this.$store.dispatch("call", {
                url: 'project/update',
                data: Object.assign(this.settingData, {
                    project_id: this.projectId
                }),
            }).then(({data, msg}) => {
                $A.messageSuccess(msg);
                this.settingShow = false;
                this.$store.dispatch("saveProject", data)
            }).catch(({msg}) => {
                $A.modalError(msg);
            }).finally(_ => {
                this.settingLoad--;
            });
        },

        onUser() {
            this.userLoad++;
            this.$store.dispatch("call", {
                url: 'project/user',
                data: {
                    project_id: this.projectId,
                    userid: this.userData.userids,
                },
            }).then(({msg}) => {
                $A.messageSuccess(msg);
                this.userShow = false;
                this.$store.dispatch("getProjectOne", this.projectId).catch(() => {});
                this.$store.dispatch("getTaskForProject", this.projectId).catch(() => {})
            }).catch(({msg}) => {
                $A.modalError(msg);
            }).finally(_ => {
                this.userLoad--;
            });
        },

        onTransfer() {
            this.transferLoad++;
            this.$store.dispatch("call", {
                url: 'project/transfer',
                data: {
                    project_id: this.projectId,
                    owner_userid: this.transferData.owner_userid[0],
                },
            }).then(({msg}) => {
                $A.messageSuccess(msg);
                this.transferShow = false;
                this.$store.dispatch("getProjectOne", this.projectId).catch(() => {});
                this.$store.dispatch("getTaskForProject", this.projectId).catch(() => {})
            }).catch(({msg}) => {
                $A.modalError(msg);
            }).finally(_ => {
                this.transferLoad--;
            });
        },

        onArchived() {
            $A.modalConfirm({
                title: '归档项目',
                content: '你确定要归档项目【' + this.projectData.name + '】吗？',
                loading: true,
                onOk: () => {
                    return new Promise((resolve, reject) => {
                        this.$store.dispatch("archivedProject", this.projectId).then(({msg}) => {
                            resolve(msg);
                        }).catch(({msg}) => {
                            reject(msg);
                        });
                    })
                }
            });
        },

        onDelete() {
            $A.modalConfirm({
                title: '删除项目',
                content: '你确定要删除项目【' + this.projectData.name + '】吗？',
                loading: true,
                onOk: () => {
                    return new Promise((resolve, reject) => {
                        this.$store.dispatch("removeProject", this.projectId).then(({msg}) => {
                            resolve(msg);
                        }).catch(({msg}) => {
                            reject(msg);
                        });
                    })
                }
            });
        },

        onExit() {
            $A.modalConfirm({
                title: '退出项目',
                content: '你确定要退出项目【' + this.projectData.name + '】吗？',
                loading: true,
                onOk: () => {
                    return new Promise((resolve, reject) => {
                        this.$store.dispatch("exitProject", this.projectId).then(({msg}) => {
                            resolve(msg);
                        }).catch(({msg}) => {
                            reject(msg);
                        });
                    })
                }
            });
        },

        projectDropdown(name) {
            switch (name) {
                case "setting":
                    Object.assign(this.settingData, {
                        name: this.projectData.name,
                        desc: this.projectData.desc,
                        archive_method: this.projectData.archive_method,
                        archive_days: this.projectData.archive_days
                    });
                    this.settingShow = true;
                    this.$nextTick(() => {
                        this.$refs.projectName.focus()
                        setTimeout(this.$refs.projectDesc.resizeTextarea, 0)
                    });
                    break;

                case "permissions":
                    this.permissionShow = true;
                    break;

                case "user":
                    if (this.projectData.owner_userid !== this.userId) {
                        return;
                    }
                    const userids = this.projectData.project_user.map(({userid}) => userid);
                    this.$set(this.userData, 'userids', userids);
                    this.$set(this.userData, 'useridbak', userids);
                    this.$set(this.userData, 'uncancelable', [this.projectData.owner_userid]);
                    this.userShow = true;
                    break;

                case "invite":
                    this.inviteData = {};
                    this.inviteShow = true;
                    this.inviteGet()
                    break;

                case "task_template":
                    this.taskTemplateShow = true;
                    break;

                case "task_tag":
                    this.taskTagShow = true;
                    break;

                case "workflow":
                    this.workflowShow = true;
                    break;

                case "log":
                    this.logShow = true;
                    break;

                case "archived_task":
                    this.archivedTaskShow = true;
                    break;

                case "deleted_task":
                    this.deletedTaskShow = true;
                    break;

                case "transfer":
                    this.$set(this.transferData, 'owner_userid', []);
                    this.transferShow = true;
                    break;

                case "archived":
                    this.onArchived();
                    break;

                case "delete":
                    this.onDelete();
                    break;

                case "exit":
                    this.onExit();
                    break;
            }
        },

        openTask(task, receive) {
            this.$store.dispatch("openTask", task)
            if (receive === true) {
                // 向任务窗口发送领取任务请求
                setTimeout(() => {
                    emitter.emit('receiveTask', true);
                }, 300)
            }
        },

        openMenu(event, task) {
            const el = this.$refs[`taskMenu_${task.id}`];
            if (el) {
                el[0].handleClick(event)
            }
        },

        taskIsHidden(task) {
            const {id, name, desc, complete_at} = task;
            const {searchText} = this;
            if (!this.projectData.cacheParameter.completedTask) {
                if (complete_at) {
                    return true;
                }
            }
            if (this.flowTask(task)) {
                return true;
            }
            if (searchText) {
                if (id != searchText && !$A.strExists(`${name} ${desc}`, searchText)) {
                    return true;
                }
            }
            return false;
        },

        ownerUser(list) {
            return list.filter(({owner}) => owner == 1).sort((a, b) => {
                return a.id - b.id;
            });
        },

        inviteGet(refresh) {
            this.inviteLoad++;
            this.$store.dispatch("call", {
                url: 'project/invite',
                data: {
                    project_id: this.projectId,
                    refresh: refresh === true ? 'yes' : 'no'
                },
            }).then(({data}) => {
                this.inviteData = data;
                this.inviteCopy();
            }).catch(({msg}) => {
                $A.modalError(msg);
            }).finally(_ => {
                this.inviteLoad--;
            });
        },

        getFlowData() {
            this.flowInfo = {}
            this.$store.dispatch("call", {
                url: 'project/flow/list',
                data: {
                    project_id: this.projectId,
                },
            }).then(({data}) => {
                this.flowList = data;
                this.$refs.flow?.clearSelect();
            }).catch(() => {
                this.flowList = [];
            });
        },

        flowChange(_, data) {
            this.flowInfo = data.pop() || {};
            this.handleColumnDebounce();
        },

        inviteCopy() {
            if (!this.inviteData.url) {
                return;
            }
            this.inviteFocus();
            this.copyText(this.inviteData.url);
        },

        inviteFocus() {
            this.$nextTick(_ => {
                this.$refs.inviteInput.focus({cursor:'all'});
            });
        },

        workflowBeforeClose() {
            return new Promise(resolve => {
                if (!this.$refs.workflow) {
                    resolve();
                    return;
                }
                if (!this.$refs.workflow.existDiff()) {
                    resolve()
                    return
                }
                $A.modalConfirm({
                    content: '设置尚未保存，是否放弃修改？',
                    cancelText: '取消',
                    okText: '放弃',
                    onOk: () => {
                        resolve()
                    }
                });
            })
        },

        myFilter(task, chackCompleted = true) {
            if (!this.projectData.cacheParameter.completedTask && chackCompleted === true) {
                if (task.complete_at) {
                    return false;
                }
            }
            if (this.flowTask(task)) {
                return false;
            }
            if (this.searchText) {
                if (task.id != this.searchText && !$A.strExists(task.name, this.searchText) && !$A.strExists(task.desc, this.searchText)) {
                    return false;
                }
            }
            return task.owner == 1;
        },

        helpFilter(task, chackCompleted = true) {
            if (task.parent_id > 0) {
                return false;
            }
            if (!this.projectData.cacheParameter.completedTask && chackCompleted === true) {
                if (task.complete_at) {
                    return false;
                }
            }
            if (this.flowTask(task)) {
                return false;
            }
            if (this.searchText) {
                if (task.id != this.searchText && !$A.strExists(task.name, this.searchText) && !$A.strExists(task.desc, this.searchText)) {
                    return false;
                }
            }
            return task.task_user && task.task_user.find(({userid, owner}) => userid == this.userId && owner == 0);
        },

        flowTask(task) {
            if ($A.leftExists(this.flowInfo.value, "user:") && !task.task_user.find(({userid, owner}) => userid === this.flowInfo.userid && owner)) {
                return true;
            } else if (this.flowInfo.value > 0 && task.flow_item_id !== this.flowInfo.value) {
                return true;
            }else if (this.flowInfo.value == -1 && task.start_at) {
                return true;
            }
            return false;
        },

        expiresFormat(date) {
            return $A.countDownFormat(this.nowTime, date)
        },

        tabTypeChange(type) {
            switch (type) {
                case "column":
                    this.toggleParameter({
                        project_id: this.projectId,
                        key: 'menuType',
                        value: 'column'
                    });
                    break;
                case "table":
                    this.toggleParameter({
                        project_id: this.projectId,
                        key: 'menuType',
                        value: 'table'
                    });
                    break;
                case "gantt":
                    this.toggleParameter({
                        project_id: this.projectId,
                        key: 'menuType',
                        value: 'gantt'
                    });
                    break;
            }
        },

        toggleParameter(data) {
            if (data === 'chat') {
                if (this.windowPortrait) {
                    this.$store.dispatch('openDialog', this.projectData.dialog_id)
                    return;
                }
            } else if (data === 'completedTask') {
                this.$store.dispatch("forgetTaskCompleteTemp", true);
            }
            this.$store.dispatch('toggleProjectParameter', data);
            this.handleColumnDebounce();
        },

        onBack() {
            const {name, params} = this.$store.state.routeHistoryLast;
            if (name === this.$route.name && /^\d+$/.test(params.projectId)) {
                this.goForward({name: this.$route.name, params: {projectId: 'all'}});
            } else {
                this.goBack();
            }
        },

        formArchived(value) {
            this.settingData = { ...this.settingData, archive_method: value };
        },

        taskItemVisible({id, column_id}) {
            return this.columnVisibilitys[column_id] && this.taskVisibilitys[id]?.visible
        },

        taskItemStyle({id, column_id, color}) {
            const style = {}
            if (color) {
                style.backgroundColor = color;
            }
            if (!this.taskItemVisible({id, column_id})) {
                style.height = (this.taskVisibilitys[id]?.height || 146) + 'px';
            }
            return style;
        },

        handleColumnDebounce() {
            if (!this.columnDebounceInvoke) {
                this.columnDebounceInvoke = debounce(_ => {
                    this.$nextTick(_ => {
                        switch (this.tabTypeActive) {
                            case 'column':
                                this.handleColumnScroll()
                                break;
                            case 'table':
                                this.handleTaskScroll({target: this.$refs.projectTableScroll?.$el})
                                break;
                        }
                    })
                }, 10);
            }
            this.columnDebounceInvoke();
        },

        handleColumnScroll() {
            this.columnList.forEach(({id}) => {
                const ref = this.$refs[`column_${id}`];
                ref && this.checkColumnVisibility(ref[0])
            })
        },

        checkColumnVisibility(columnContainer = null) {
            if (!columnContainer) {
                return
            }
            const dataId = columnContainer.getAttribute('data-id')
            if (!dataId) {
                return
            }
            const mainContainer = this.$refs.projectColumn;
            if (!mainContainer) {
                return false;
            }

            // 获取容器的边界信息
            const mainRect = mainContainer.getBoundingClientRect();
            const columnRect = columnContainer.getBoundingClientRect();

            // 检查元素是否在容器的可视区域内
            const visible = (
                columnRect.left < mainRect.right &&
                columnRect.right > mainRect.left &&
                columnRect.top < mainRect.bottom &&
                columnRect.bottom > mainRect.top
            )
            if (visible) {
                this.handleTaskScroll({target: columnContainer.querySelector('.task-scrollbar')})
            }
            this.$set(this.columnVisibilitys, dataId, visible)
        },

        async handleTaskScroll({target}) {
            if (!target) {
                return
            }
            const targetItem = target.querySelectorAll('.task-item');
            if (!targetItem.length) {
                return
            }
            let visibleType = null;
            switch (this.tabTypeActive) {
                case 'column':
                    visibleType = 'taskVisibilitys'
                    break;
                case 'table':
                    visibleType = 'taskRowVisibilitys'
                    break;
                default:
                    return
            }
            const targetRect = target.getBoundingClientRect();
            for (const item of targetItem) {
                const dataId = item.getAttribute('data-id');
                if (!dataId) {
                    continue;
                }
                const taskRect = item.getBoundingClientRect();
                const originalVisible = this[visibleType][dataId]?.visible || false;
                const currentVisible = (
                    taskRect.top >= targetRect.top - taskRect.height &&
                    taskRect.bottom <= targetRect.bottom + taskRect.height
                );
                if (currentVisible === originalVisible) {
                    continue;
                }
                const firstVisible = this[visibleType][dataId] === undefined && currentVisible;
                this.$set(this[visibleType], dataId, {
                    visible: currentVisible,
                    height: taskRect.height
                });
                if (firstVisible) {
                    await this.$nextTick();
                }
            }
        },
    }
}
</script>
