<template>
    <div
        v-if="isReady"
        class="dialog-wrapper"
        :class="wrapperClass"
        @drop.prevent="chatPasteDrag($event, 'drag')"
        @dragover.prevent="chatDragOver(true, $event)"
        @dragleave.prevent="chatDragOver(false, $event)"
        @touchstart="onTouchStart"
        @pointerover="onPointerover">
        <!--顶部导航-->
        <div ref="nav" class="dialog-nav">
            <slot name="head">
                <div class="nav-wrapper" :class="navClass">
                    <div class="dialog-back" @click="onBack">
                        <i class="taskfont">&#xe676;</i>
                        <div v-if="msgUnreadOnly" class="back-num">{{msgUnreadOnly}}</div>
                    </div>

                    <div class="dialog-block">
                        <div class="dialog-avatar" @click="onViewDetail">
                            <template v-if="dialogData.type=='group'">
                                <EAvatar v-if="dialogData.avatar" class="img-avatar" :src="dialogData.avatar" :size="42"></EAvatar>
                                <i v-else-if="dialogData.group_type=='department'" class="taskfont icon-avatar department">&#xe75c;</i>
                                <i v-else-if="dialogData.group_type=='project'" class="taskfont icon-avatar project">&#xe6f9;</i>
                                <i v-else-if="dialogData.group_type=='task'" class="taskfont icon-avatar task">&#xe6f4;</i>
                                <i v-else-if="dialogData.group_type=='okr'" class="taskfont icon-avatar task">&#xe6f4;</i>
                                <Icon v-else class="icon-avatar" type="ios-people" />
                            </template>
                            <div v-else-if="dialogData.dialog_user" class="user-avatar">
                                <UserAvatarTip :online.sync="dialogData.online_state" :userid="dialogData.dialog_user.userid" :size="42">
                                    <p v-if="dialogData.type === 'user' && dialogData.online_state !== true" slot="end">
                                        {{$L(dialogData.online_state)}}
                                    </p>
                                </UserAvatarTip>
                            </div>
                            <Icon v-else class="icon-avatar" type="md-person" />
                        </div>
                        <div class="dialog-title">
                            <div class="main-title">
                                <template v-for="tag in $A.dialogTags(dialogData)" v-if="tag.color != 'success'">
                                    <Tag :color="tag.color" :fade="false">{{$L(tag.text)}}</Tag>
                                </template>
                                <h2 class="user-select-auto" @click="onViewDetail" v-html="transformEmojiToHtml(dialogData.name)"></h2>
                                <em v-if="peopleNum > 0" @click="onDialogMenu('groupInfo')">({{peopleNum}})</em>
                                <Tag v-if="dialogData.bot" class="after" :fade="false">{{$L('机器人')}}</Tag>
                                <Tag v-if="dialogData.type === 'user' && approvaUserStatus" class="after" color="red" :fade="false">{{$L(approvaUserStatus)}}</Tag>
                                <Tag v-if="dialogData.group_type=='all'" class="after pointer" :fade="false" @on-click="onDialogMenu('groupInfo')">{{$L('全员')}}</Tag>
                                <Tag v-else-if="dialogData.group_type=='department'" class="after pointer" :fade="false" @on-click="onDialogMenu('groupInfo')">{{$L('部门')}}</Tag>
                                <div v-if="msgLoadIng > 0 && allMsgs.length > 0" class="load"><Loading/></div>
                            </div>
                            <ul class="title-desc">
                                <li v-if="dialogData.type === 'user'" :class="[dialogData.online_state === true ? 'online' : 'offline']">
                                    {{$L(dialogData.online_state === true ? '在线' : dialogData.online_state)}}
                                </li>
                            </ul>
                            <ul v-if="typeShow" class="title-types scrollbar-hidden">
                                <li
                                    v-for="item in msgTypes"
                                    :key="item.type"
                                    :class="{
                                        [item.type || 'msg']: true,
                                        active: msgType === item.type,
                                    }"
                                    @click="onMsgType(item.type)">
                                    <i class="no-dark-content"></i>
                                    <span>{{item.label}}</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <EDropdown
                        trigger="click"
                        class="dialog-menu"
                        @command="onDialogMenu">
                        <i class="taskfont dialog-menu-icon">&#xe6e9;</i>
                        <EDropdownMenu slot="dropdown">
                            <EDropdownItem command="searchMsg">
                                <div>{{$L('搜索消息')}}</div>
                            </EDropdownItem>
                            <EDropdownItem v-if="$isMainElectron" command="single">
                                <div>{{$L('独立窗口')}}</div>
                            </EDropdownItem>
                            <template v-if="dialogData.type === 'user'">
                                <EDropdownItem command="previewDetail">
                                    <div>{{$L('查看详情')}}</div>
                                </EDropdownItem>
                                <EDropdownItem v-if="isManageBot" command="modifyNormal">
                                    <div>{{$L('修改资料')}}</div>
                                </EDropdownItem>
                                <EDropdownItem v-if="isAiBot" command="modifyAi">
                                    <div>{{$L('修改提示词')}}</div>
                                </EDropdownItem>
                                <EDropdownItem command="openCreate">
                                    <div>{{$L('创建群组')}}</div>
                                </EDropdownItem>
                                <EDropdownItem v-if="dialogData.bot == 0" command="report">
                                    <div>{{$L('举报投诉')}}</div>
                                </EDropdownItem>
                            </template>
                            <template v-else>
                                <EDropdownItem command="groupInfo">
                                    <div>{{$L('群组设置')}}</div>
                                </EDropdownItem>
                                <EDropdownItem v-if="dialogData.owner_id == userId || (dialogData.group_type === 'all' && userIsAdmin)" command="modifyNormal">
                                    <div>{{$L('修改资料')}}</div>
                                </EDropdownItem>
                                <EDropdownItem v-if="dialogData.avatar" command="previewAvatar">
                                    <div>{{$L('查看头像')}}</div>
                                </EDropdownItem>
                                <template v-if="dialogData.owner_id != userId">
                                    <EDropdownItem command="report">
                                        <div>{{$L('举报投诉')}}</div>
                                    </EDropdownItem>
                                    <EDropdownItem command="exit">
                                        <div style="color:#f00">{{$L('退出群组')}}</div>
                                    </EDropdownItem>
                                </template>
                                <template v-else-if="dialogData.group_type === 'user'">
                                    <EDropdownItem command="transfer">
                                        <div>{{$L('转让群主')}}</div>
                                    </EDropdownItem>
                                    <EDropdownItem command="report">
                                        <div>{{$L('举报投诉')}}</div>
                                    </EDropdownItem>
                                    <EDropdownItem command="disband">
                                        <div style="color:#f00">{{$L('解散群组')}}</div>
                                    </EDropdownItem>
                                </template>
                            </template>
                        </EDropdownMenu>
                    </EDropdown>

                    <!--搜索框-->
                    <div v-if="searchShow" class="dialog-search">
                        <div class="search-location">
                            <i class="taskfont" @click="onSearchSwitch('prev')">&#xe702;</i>
                            <i class="taskfont" @click="onSearchSwitch('next')">&#xe705;</i>
                        </div>
                        <div class="search-input">
                            <div class="search-pre">
                                <Loading v-if="searchLoad > 0"/>
                                <Icon v-else type="ios-search" />
                            </div>
                            <Form class="search-form" action="javascript:void(0)" @submit.native.prevent="$A.eeuiAppKeyboardHide">
                                <Input type="search" ref="searchInput" v-model="searchKey" :placeholder="$L('搜索消息')" @on-keyup="onSearchKeyup" clearable/>
                                <div v-if="searchLoad === 0 && searchResult.length > 0" class="search-total">{{searchLocation}}/{{searchResult.length}}</div>
                            </Form>
                        </div>
                        <div class="search-cancel" @click="onSearchKeyup(null)">{{$L('取消')}}</div>
                    </div>
                </div>
            </slot>
        </div>

        <!--置顶消息-->
        <div v-if="topShow" class="dialog-top-message" @click="onPosTop">
            <div class="dialog-top-message-warp">
                <div class="dialog-top-message-font">
                    <i class="taskfont">&#xe7e6;</i>
                </div>
                <div class="dialog-top-message-content">
                    <p class="content">
                        <UserAvatar :userid="topMsg.userid" showName :showIcon="false"/>:
                        <span>{{$A.getMsgSimpleDesc(topMsg)}}</span>
                    </p>
                    <p class="personnel">
                        {{ $L('置顶人员') }}
                        <UserAvatar :userid="dialogData.top_userid" showName :showIcon="false"/>
                    </p>
                </div>
                <div class="dialog-top-message-btn">
                    <Loading v-if="topPosLoad > 0" type="pure"/>
                    <i v-else class="taskfont">&#xee15;</i>
                    <i class="taskfont" @click.stop="onCancelTop(topMsg)">&#xe6e5;</i>
                </div>
            </div>
        </div>

        <!--消息部分-->
        <div
            ref="msgs"
            class="dialog-msgs"
            v-longpress="{callback: handleLongpress, preventEndEvent: true}">
            <!--定位提示-->
            <div v-if="positionShow && positionMsg" class="dialog-position">
                <div class="position-label" @click="onPositionMark(positionMsg.msg_id)">
                    <Icon v-if="positionLoad > 0" type="ios-loading" class="icon-loading"></Icon>
                    <i v-else class="taskfont">&#xe624;</i>
                    {{positionMsg.label}}
                </div>
            </div>

            <!--消息列表-->
            <VirtualList
                ref="scroller"
                class="dialog-scroller scrollbar-virtual"
                active-prefix="item"
                :data-key="'id'"
                :data-sources="allMsgs"
                :data-component="msgItem"

                :extra-props="{dialogData, operateVisible, operateItem, pointerMouse, isMyDialog, msgId, unreadOne, scrollIng, readEnabled}"
                :estimate-size="dialogData.type=='group' ? 105 : 77"
                :keeps="dialogMsgKeep"
                :disabled="scrollDisabled"
                @activity="onActivity"
                @scroll="onScroll"
                @totop="onPrevPage"
                @range="onRange"
                @visible="onVisible"

                @on-view-reply="onViewReply"
                @on-view-text="onViewText"
                @on-view-file="onViewFile"
                @on-down-file="onDownFile"
                @on-reply-list="onReplyList"
                @on-error="onError"
                @on-emoji="onEmoji"
                @on-other="onOther"
                @on-show-emoji-user="onShowEmojiUser">
                <template #header v-if="!isChildComponent">
                    <div class="dialog-item head-box">
                        <div v-if="loadIng > 0 || prevId > 0" class="loading" :class="{filled: allMsgs.length === 0}">
                            <span v-if="scrollOffset < 100"></span>
                        </div>
                        <div v-else-if="allMsgs.length === 0" class="describe filled">{{$L('暂无消息')}}</div>
                    </div>
                </template>
            </VirtualList>
        </div>

        <!--底部输入-->
        <div ref="footer" class="dialog-footer" @click="onClickFooter">
            <!--滚动到底部-->
            <div
                v-if="scrollTail > 500 || (msgNew > 0 && allMsgs.length > 0)"
                class="dialog-goto"
                v-touchclick="onToBottom">
                <Badge :overflow-count="999" :count="msgNew">
                    <i class="taskfont">&#xe72b;</i>
                </Badge>
            </div>

            <!--待办-->
            <div v-if="todoShow" class="chat-bottom-menu">
                <div class="bottom-menu-label">{{$L('待办')}}:</div>
                <ul class="scrollbar-hidden">
                    <li v-for="item in todoList" @click.stop="onViewTodo(item)">
                        <div class="bottom-menu-desc no-dark-content">{{$A.getMsgSimpleDesc(item.msg_data)}}</div>
                    </li>
                </ul>
            </div>

            <!--菜单-->
            <div v-else-if="quickShow" class="chat-bottom-menu">
                <ul class="scrollbar-hidden">
                    <li v-for="item in quickMsgs" @click.stop="sendQuick(item, $event)">
                        <div class="bottom-menu-desc no-dark-content" :style="item.style || null">{{quickLabel(item)}}</div>
                    </li>
                </ul>
            </div>

            <!--禁言、停用、输入-->
            <div v-if="isMute" class="chat-mute">
                {{$L('禁言发言')}}
            </div>
            <div v-else-if="isDisable" class="chat-mute">
                {{$L('此账号已停用')}}
            </div>
            <ChatInput
                v-else
                ref="input"
                v-model="msgText"
                :dialog-id="dialogId"
                :emoji-bottom="windowPortrait"
                :maxlength="200000"
                :placeholder="$L('输入消息...')"
                :reply-msg-auto-mention="replyMsgAutoMention"
                @on-focus="onEventFocus"
                @on-blur="onEventBlur"
                @on-more="onEventMore"
                @on-file="sendFileMsg"
                @on-send="sendMsg"
                @on-record="sendRecord"
                @on-record-state="onRecordState"/>
        </div>

        <!--上传组件-->
        <DialogUpload
            ref="chatUpload"
            class="dialog-upload"
            :dialog-id="dialogId"
            :maxSize="maxSize"
            @on-progress="chatFile('progress', $event)"
            @on-success="chatFile('success', $event)"
            @on-error="chatFile('error', $event)"/>

        <!--长按、右键-->
        <div
            class="operate-position"
            v-transfer-dom
            :data-transfer="true"
            :style="operateStyles"
            v-show="operateVisible">
            <Dropdown
                ref="operate"
                trigger="custom"
                placement="top"
                :visible="operateVisible"
                @on-clickoutside="operateVisible = false"
                transferClassName="dialog-wrapper-operate"
                transfer>
                <div :style="{userSelect:operateVisible ? 'none' : 'auto', height: operateStyles.height}"></div>
                <DropdownMenu slot="list" v-resize-observer="handleOperateResize">
                    <template v-if="!operateItem.created_at">
                        <DropdownItem name="action">
                            <ul class="operate-action cancel">
                                <li @click="onOperate('cancel')">
                                    <i class="taskfont">&#xe6eb;</i>
                                    <span>{{ $L('取消发送') }}</span>
                                </li>
                            </ul>
                        </DropdownItem>
                    </template>
                    <template v-else>
                        <DropdownItem name="action">
                            <ul class="operate-action">
                                <li v-if="msgId === 0" @click="onOperate('reply')">
                                    <i class="taskfont">&#xe6eb;</i>
                                    <span>{{ $L('回复') }}</span>
                                </li>
                                <li v-if="operateItem.userid == userId && operateItem.type === 'text'" @click="onOperate('update')">
                                    <i class="taskfont">&#xe779;</i>
                                    <span>{{ $L('编辑') }}</span>
                                </li>
                                <li v-if="actionPermission(operateItem, 'voice2text')" @click="onOperate('voice2text')">
                                    <i class="taskfont">&#xe628;</i>
                                    <span>{{ $L('转文字') }}</span>
                                </li>
                                <li v-if="actionPermission(operateItem, 'translation')" @click="onOperate('translation')">
                                    <i class="taskfont">&#xe795;</i>
                                    <span>{{ $L('翻译') }}</span>
                                </li>
                                <li
                                    v-for="(item, index) in operateCopys"
                                    v-if="item.visible !== false"
                                    :key="index"
                                    @click="onOperate('copy', item)">
                                    <i class="taskfont" v-html="item.icon"></i>
                                    <span>{{ $L(item.label || item.title) }}</span>
                                </li>
                                <li v-if="actionPermission(operateItem, 'forward')" @click="onOperate('forward')">
                                    <i class="taskfont">&#xe638;</i>
                                    <span>{{ $L('转发') }}</span>
                                </li>
                                <li v-if="operateItem.userid == userId" @click="onOperate('withdraw')">
                                    <i class="taskfont">&#xe637;</i>
                                    <span>{{ $L('撤回') }}</span>
                                </li>
                                <template v-if="operateItem.type === 'file'">
                                    <li @click="onOperate('view')">
                                        <i class="taskfont">&#xe77b;</i>
                                        <span>{{ $L('查看') }}</span>
                                    </li>
                                    <li @click="onOperate('down')">
                                        <i class="taskfont">&#xe7a8;</i>
                                        <span>{{ $L('下载') }}</span>
                                    </li>
                                </template>
                                <li @click="onOperate('tag')">
                                    <i class="taskfont">&#xe61e;</i>
                                    <span>{{ $L(operateItem.tag ? '取消标注' : '标注') }}</span>
                                </li>
                                <li @click="onOperate('favorite')">
                                    <i class="taskfont">{{ operateItem.favorited ? '&#xe683;' : '&#xe679;' }}</i>
                                    <span>{{ $L(operateItem.favorited ? '取消收藏' : '收藏') }}</span>
                                </li>
                                <li v-if="actionPermission(operateItem, 'newTask')" @click="onOperate('newTask')">
                                    <i class="taskfont">&#xe7b8;</i>
                                    <span>{{ $L('新任务') }}</span>
                                </li>
                                <li @click="onOperate('todo')">
                                    <i class="taskfont">&#xe7b7;</i>
                                    <span>{{ $L(operateItem.todo ? '取消待办' : '设待办') }}</span>
                                </li>
                                <li @click="onOperate('top')">
                                    <i class="taskfont" v-html="dialogData.top_msg_id == operateItem.id ? '&#xe7e3;' : '&#xe7e6;'"></i>
                                    <span>{{ $L(dialogData.top_msg_id == operateItem.id ? '取消置顶' : '置顶') }}</span>
                                </li>
                                <li v-if="msgType !== ''" @click="onOperate('pos')">
                                    <i class="taskfont">&#xee15;</i>
                                    <span>{{ $L('完整对话') }}</span>
                                </li>
                            </ul>
                        </DropdownItem>
                        <DropdownItem name="emoji" class="dropdown-emoji">
                            <ul class="operate-emoji scrollbar-hidden">
                                <li
                                    v-for="(emoji, key) in operateEmojis"
                                    :key="key"
                                    v-html="emoji"
                                    class="no-dark-content"
                                    @click="onOperate('emoji', emoji)"></li>
                                <li></li>
                                <li class="more-emoji" @click="onOperate('emoji', 'more')">
                                    <i class="taskfont">&#xe790;</i>
                                </li>
                            </ul>
                        </DropdownItem>
                    </template>
                </DropdownMenu>
            </Dropdown>
        </div>

        <!--拖动提示-->
        <div v-if="dialogDrag" class="drag-over" @click="dialogDrag=false">
            <div class="drag-text">{{$L('拖动到这里发送')}}</div>
        </div>

        <!--拖动发送提示-->
        <Modal
            v-model="pasteShow"
            :title="$L(pasteTitle)"
            :cancel-text="$L('取消')"
            :ok-text="$L('发送')"
            :enter-ok="true"
            :closable="false"
            :mask-closable="false"
            @on-ok="pasteSend">
            <ul class="dialog-wrapper-paste" :class="pasteClass">
                <li v-for="item in pasteItem">
                    <img v-if="item.type == 'image'" :src="item.result"/>
                    <div v-else>{{$L('文件')}}: {{item.name}} ({{$A.bytesToSize(item.size)}})</div>
                </li>
            </ul>
        </Modal>

        <!--修改资料-->
        <Modal
            v-model="modifyShow"
            :title="$L('修改资料')"
            :mask-closable="false">
            <Form :model="modifyData" v-bind="formOptions" @submit.native.prevent>
                <Alert v-if="modifyData.system_name" type="error" style="margin-bottom:18px">{{$L(`正在修改系统机器人：${modifyData.system_name}`)}}</Alert>
                <FormItem prop="avatar" :label="$L('头像')">
                    <ImgUpload v-model="modifyData.avatar" :num="1" :width="512" :height="512" whcut="cover"/>
                </FormItem>
                <FormItem v-if="typeof modifyData.name !== 'undefined'" prop="name" :label="$L('名称')">
                    <Input v-model="modifyData.name" :maxlength="20" :disabled="!canModifyName" />
                    <div v-if="!canModifyName" class="form-tip">{{$L('仅个人群组可修改名称')}}</div>
                </FormItem>
                <template v-if="dialogData.bot == userId">
                    <FormItem v-if="typeof modifyData.clear_day !== 'undefined'" prop="clear_day" :label="$L('消息保留')">
                        <Input v-model="modifyData.clear_day" :maxlength="3" type="number">
                            <div slot="append">{{$L('天')}}</div>
                        </Input>
                    </FormItem>
                    <FormItem v-if="typeof modifyData.webhook_url !== 'undefined'" prop="webhook_url" label="Webhook">
                        <Input v-model="modifyData.webhook_url" :maxlength="255" />
                    </FormItem>
                    <FormItem v-if="typeof modifyData.webhook_events !== 'undefined'" prop="webhook_events" :label="$L('Webhook事件')">
                        <CheckboxGroup v-model="webhookEvents">
                            <Checkbox v-for="option in webhookEventOptions" :key="option.value" :label="option.value">
                                {{$L(option.label)}}
                            </Checkbox>
                        </CheckboxGroup>
                    </FormItem>
                </template>
            </Form>
            <div slot="footer" class="adaption">
                <Button type="default" @click="modifyShow=false">{{$L('取消')}}</Button>
                <Button type="primary" :loading="modifyLoad > 0" @click="onModify">{{$L('保存')}}</Button>
            </div>
        </Modal>

        <!--修改提示词-->
        <Modal
            v-model="modifyAiShow"
            :title="$L('修改提示词')"
            :mask-closable="false">
            <Form :model="modifyData" @submit.native.prevent>
                <FormItem prop="value" style="margin-bottom: 16px">
                    <Input
                        :maxlength="20000"
                        type="textarea"
                        :autosize="{minRows:3,maxRows:5}"
                        v-model="modifyData.value"
                        :placeholder="$L('例如：你是一个人开发的AI助手')"
                        :show-word-limit="0.9"/>
                </FormItem>
            </Form>
            <div slot="footer" class="adaption">
                <Button type="default" @click="modifyAiShow=false">{{$L('取消')}}</Button>
                <Button type="primary" :loading="modifyLoad > 0" @click="onAiModify">{{$L('保存')}}</Button>
            </div>
        </Modal>

        <!-- 转发 -->
        <Forwarder
            ref="forwarder"
            :title="$L('转发')"
            :confirm-title="$L('确认转发')"
            :multiple-max="50"
            :msg-detail="operateItem"
            :before-submit="onForward"/>

        <!-- 设置待办 -->
        <Modal
            v-model="todoSettingShow"
            :title="$L('设置待办')"
            :mask-closable="false">
            <Form ref="todoSettingForm" :model="todoSettingData" v-bind="formOptions" @submit.native.prevent>
                <FormItem prop="type" :label="$L('当前会话')">
                    <RadioGroup v-model="todoSettingData.type" @on-change="onTypeChange">
                        <Radio label="all">{{$L('所有成员')}}</Radio>
                        <Radio label="user">{{$L('指定成员')}}</Radio>
                        <Radio label="quick_select" v-show="false"></Radio>
                    </RadioGroup>
                    <CheckboxGroup v-model="todoSettingData.quick_value" @on-change="onQuickChange">
                        <Checkbox v-for="userid in todoSettingData.quick_list" :key="userid" :label="userid">
                            <div class="dialog-wrapper-todo">
                                <div>
                                    <UserAvatar :userid="userid" :show-icon="false" :show-name="true"/>
                                    <Tag v-if="userid==userId">{{$L('自己')}}</Tag>
                                </div>
                            </div>
                        </Checkbox>
                    </CheckboxGroup>
                </FormItem>
                <FormItem prop="userids" :label="$L('指定成员')" v-if="todoSettingData.type === 'user'">
                    <UserSelect ref="userSelect" v-model="todoSettingData.userids" :dialog-id="dialogId" :title="$L('选择指定成员')"/>
                </FormItem>
            </Form>
            <div slot="footer" class="adaption">
                <Button type="default" @click="todoSettingShow=false">{{$L('取消')}}</Button>
                <Button type="primary" :loading="todoSettingLoad > 0" @click="onTodo('submit')">{{$L('确定')}}</Button>
            </div>
        </Modal>
        <UserSelect
            v-if="todoSpecifyShow"
            ref="todoSpecifySelect"
            v-model="todoSpecifyData.userids"
            :dialog-id="dialogId"
            :title="$L('选择指定成员')"
            module
            border
            :before-submit="onTodoSpecify"/>

        <!--群设置-->
        <DrawerOverlay
            v-model="groupInfoShow"
            placement="right"
            :size="400">
            <DialogGroupInfo
                v-if="groupInfoShow"
                :dialogId="dialogId"
                @on-modify="onDialogMenu('modifyNormal')"
                @on-close="groupInfoShow=false"/>
        </DrawerOverlay>

        <!--举报投诉-->
        <DrawerOverlay
            v-model="reportShow"
            placement="right"
            :size="500">
            <DialogComplaint v-if="reportShow" :dialogId="dialogId" @on-close="reportShow=false"/>
        </DrawerOverlay>

        <!--群转让-->
        <Modal
            v-model="groupTransferShow"
            :title="$L('转让群主身份')"
            :mask-closable="false">
            <Form :model="groupTransferData" v-bind="formOptions" @submit.native.prevent>
                <FormItem prop="userid" :label="$L('新的群主')">
                    <UserSelect v-model="groupTransferData.userid" :disabledChoice="groupTransferData.disabledChoice" :multiple-max="1" :title="$L('选择新的群主')"/>
                </FormItem>
            </Form>
            <div slot="footer" class="adaption">
                <Button type="default" @click="groupTransferShow=false">{{$L('取消')}}</Button>
                <Button type="primary" :loading="groupTransferLoad > 0" @click="onDialogMenu('transferConfirm')">{{$L('确定转让')}}</Button>
            </div>
        </Modal>

        <!--回复列表-->
        <DrawerOverlay
            v-model="replyListShow"
            placement="right"
            class-name="dialog-wrapper-list"
            :size="500">
            <DialogWrapper
                v-if="replyListShow"
                :dialogId="dialogId"
                :msgId="replyListId"
                isChildComponent
                class="inde-list">
                <div slot="head" class="drawer-title">{{$L('回复消息')}}</div>
            </DialogWrapper>
        </DrawerOverlay>

        <!--回应详情-->
        <DrawerOverlay
            v-model="respondShow"
            placement="right"
            class-name="dialog-wrapper-list"
            :size="400">
            <DialogRespond v-if="respondShow" :respond-data="respondData" @on-close="respondShow=false"/>
        </DrawerOverlay>

        <!--历史会话-->
        <DrawerOverlay
            v-model="sessionHistoryShow"
            placement="right"
            class-name="dialog-wrapper-list"
            :size="500">
            <DialogSessionHistory
                v-if="sessionHistoryShow"
                :session-data="sessionHistoryData"
                @on-submit="onSessionSubmit"
                @on-close="sessionHistoryShow=false"/>
        </DrawerOverlay>

        <!--待办完成-->
        <DrawerOverlay
            v-model="todoViewShow"
            placement="right"
            class-name="dialog-wrapper-list"
            :size="500">
            <div class="dialog-wrapper inde-list">
                <div class="dialog-nav">
                    <div class="drawer-title">{{$L('待办消息')}}</div>
                </div>
                <Scrollbar class-name="dialog-scroller">
                    <template v-if="todoViewMsg">
                        <DialogItem
                            :source="todoViewMsg"
                            @on-view-text="onViewText"
                            @on-view-file="onViewFile"
                            @on-down-file="onDownFile"
                            @on-emoji="onEmoji"
                            @on-other="onOther"
                            simpleView/>
                        <Button class="original-button" icon="md-exit" type="text" :loading="todoViewPosLoad" @click="onPosTodo">{{ $L("回到原文") }}</Button>
                    </template>
                    <div v-else class="dialog-float-loading">
                        <Loading/>
                    </div>
                </Scrollbar>
                <div class="todo-button">
                    <Button type="primary" size="large" icon="md-checkbox-outline" @click="onDoneTodo" :loading="todoViewLoad" long>{{ $L("完成") }}</Button>
                </div>
            </div>
        </DrawerOverlay>

        <!-- 群接龙 -->
        <DialogGroupWordChain/>

        <!-- 群投票 -->
        <DialogGroupVote/>

    </div>
</template>

<script>
import {mapGetters, mapState} from "vuex";
import DialogItem from "./DialogItem";
import DialogUpload from "./DialogUpload";
import DrawerOverlay from "../../../components/DrawerOverlay";
import DialogGroupInfo from "./DialogGroupInfo";
import DialogRespond from "./DialogRespond";
import DialogSessionHistory from "./DialogSessionHistory";
import ChatInput from "./ChatInput";

import VirtualList from "vue-virtual-scroll-list-hi"
import ImgUpload from "../../../components/ImgUpload.vue";
import {choiceEmojiOne} from "./ChatInput/one";

import UserSelect from "../../../components/UserSelect.vue";
import UserAvatarTip from "../../../components/UserAvatar/tip.vue";
import DialogGroupWordChain from "./DialogGroupWordChain";
import DialogGroupVote from "./DialogGroupVote";
import DialogComplaint from "./DialogComplaint";
import touchclick from "../../../directives/touchclick";
import longpress from "../../../directives/longpress";
import TransferDom from "../../../directives/transfer-dom";
import resizeObserver from "../../../directives/resize-observer";
import {languageList} from "../../../language";
import {isLocalHost} from "../../../components/Replace/utils";
import emitter from "../../../store/events";
import Forwarder from "./Forwarder/index.vue";
import {throttle} from "lodash";
import transformEmojiToHtml from "../../../utils/emoji";
import {webhookEventOptions} from "../../../utils/webhook";

export default {
    name: "DialogWrapper",
    components: {
        Forwarder,
        UserAvatarTip,
        UserSelect,
        ImgUpload,
        DialogRespond,
        DialogSessionHistory,
        DialogItem,
        VirtualList,
        ChatInput,
        DialogGroupInfo,
        DrawerOverlay,
        DialogUpload,
        DialogGroupWordChain,
        DialogGroupVote,
        DialogComplaint,
    },
    directives: {touchclick, longpress, TransferDom, resizeObserver},

    props: {
        dialogId: {
            type: Number,
            default: 0
        },
        msgId: {
            type: Number,
            default: 0
        },
        autoFocus: {
            type: Boolean,
            default: false
        },
        // 组件使用位置
        location: {
            type: String,
            default: ""
        },
        // 当做子组件用，非正常聊天窗口
        isChildComponent: {
            type: Boolean,
            default: false
        },
        beforeBack: Function
    },

    data() {
        return {
            loadIng: 0,

            msgItem: DialogItem,
            msgText: '',
            msgNew: 0,              // 新消息数
            msgType: '',            // 消息类型
            msgActivity: false,     // 消息活动中
            msgPrepared: false,     // 消息已准备

            focusLazy: false,
            focusTimer: null,

            keepInterval: null,
            keepIntoTimer: null,

            allMsgs: [],
            tempMsgs: [],
            tempId: $A.randNum(1000000000, 9999999999),
            msgLoadIng: 0,
            msgActiveId: 0,

            pasteShow: false,
            pasteFile: [],
            pasteItem: [],

            searchShow: false,
            searchKey: '',
            searchLoad: 0,
            searchLocation: 1,
            searchResult: [],

            modifyShow: false,
            modifyAiShow: false,
            modifyData: {},
            modifyLoad: 0,
            webhookEventOptions,
            webhookEvents: [],

            openId: 0,
            errorId: 0,
            dialogDrag: false,
            groupInfoShow: false,
            reportShow: false,

            groupTransferShow: false,
            groupTransferLoad: 0,
            groupTransferData: {
                userid: [],
                disabledChoice: []
            },

            operateClient: {x: 0, y: 0},
            operateVisible: false,
            operatePreventScroll: 0,
            operateCopys: [],
            operateStyles: {},
            operateItem: {},

            recordState: '',
            pointerMouse: false,

            scrollTail: 0,
            scrollOffset: 0,

            replyListShow: false,
            replyListId: 0,

            respondShow: false,
            respondData: {},

            sessionHistoryShow: false,
            sessionHistoryData: {},

            todoSettingShow: false,
            todoSettingLoad: 0,
            todoSettingData: {
                type: 'all',
                userids: [],
                quick_value: [],
            },
            todoSpecifyShow: false,
            todoSpecifyData: {
                type: 'user',
                userids: [],
            },

            todoViewLoad: false,
            todoViewPosLoad: false,
            todoViewShow: false,
            todoViewData: {},
            todoViewMid: 0,
            todoViewId: 0,

            scrollDisabled: false,
            scrollDirection: null,
            scrollAction: 0,
            scrollTmp: 0,
            scrollIng: 0,
            scrollGroup: null,

            approvaUserStatus: '',

            observers: [],
            msgChangeCache: {},

            unreadOne: 0,                       // 最早未读消息id
            startMsgId: 0,                      // 可见区域第一个消息id
            topPosLoad: 0,                      // 置顶跳转加载中
            positionLoad: 0,                    // 定位跳转加载中
            positionShow: false,                // 定位跳转显示
            preventPrevLoad: 0,                 // 大于0阻止上一页加载
            preventRangeLoad: 0,                // 大于0阻止范围加载
            preventToBottom: false,             // 阻止滚动到底部
            scrollToBottomRefresh: false,       // 滚动到底部重新获取消息
            replyMsgAutoMention: false,         // 允许回复消息后自动@
            waitUnreadData: new Map(),          // 等待未读数据
            replyEmojiIngs: {},                 // 是否回复表情中（避免重复回复）
            dialogAiModel: [],                  // AI模型选择
        }
    },

    async created() {
        this.dialogAiModel = await $A.IDBArray('dialogAiModel')
    },

    mounted() {
        emitter.on('websocketMsg', this.onWebsocketMsg);
        emitter.on('streamMsgData', this.onMsgChange);
        this.keepInterval = setInterval(this.keepIntoInput, 1000)
        this.windowTouch && document.addEventListener('selectionchange', this.onSelectionchange);
    },

    beforeDestroy() {
        this.windowTouch && document.removeEventListener('selectionchange', this.onSelectionchange);
        clearInterval(this.keepInterval);
        emitter.off('streamMsgData', this.onMsgChange);
        emitter.off('websocketMsg', this.onWebsocketMsg);
        this.generateUnreadData(this.dialogId)
        //
        if (!this.isChildComponent) {
            this.$store.dispatch('forgetInDialog', {uid: this._uid})
            this.$store.dispatch('closeDialog', {id: this.dialogId})
        }
        //
        this.observers.forEach(({observer}) => observer.disconnect())
        this.observers = []
        //
        const scroller = this.$refs.scroller;
        if (scroller) {
            scroller.virtual.destroy()
        }
    },

    computed: {
        ...mapState([
            'systemConfig',
            'userIsAdmin',
            'taskId',
            'dialogSearchMsgId',
            'dialogMsgs',
            'dialogTodos',
            'dialogMsgTops',
            'dialogMsgTransfer',
            'dialogMsgKeep',
            'dialogIns',
            'cacheDialogs',
            'wsOpenNum',
            'touchBackInProgress',
            'cacheUserBasic',
            'fileLinks',
            'cacheEmojis',
            'readLoadNum',
            'readTimeout',
            'formOptions',
            'cacheTranslationLanguage',
            'longpressData',
            'keyboardShow',
            'keyboardHeight',
        ]),

        ...mapGetters(['isLoad', 'isMessengerPage', 'getDialogQuote']),

        isReady() {
            return this.dialogId > 0 && this.dialogData.id > 0
        },

        dialogData() {
            const data = this.cacheDialogs.find(({id}) => id == this.dialogId) || {}
            if (this.unreadOne === 0) {
                this.unreadOne = data.unread_one || 0
            }
            return data
        },
        canModifyName() {
            if (typeof this.modifyData.name === 'undefined') {
                return false
            }
            if (this.modifyData.userid) {
                return true
            }
            return this.dialogData.group_type === 'user'
        },

        dialogList() {
            return this.cacheDialogs.filter(dialog => {
                return !(dialog.name === undefined || dialog.dialog_delete === 1);
            }).sort((a, b) => {
                if (a.top_at || b.top_at) {
                    return $A.sortDay(b.top_at, a.top_at);
                }
                if (a.todo_num > 0 || b.todo_num > 0) {
                    return $A.sortFloat(b.todo_num, a.todo_num);
                }
                return $A.sortDay(b.last_at, a.last_at);
            });
        },

        dialogMsgList() {
            if (!this.isReady) {
                return [];
            }
            return this.dialogMsgs.filter(item => item.dialog_id == this.dialogId);
        },

        tempMsgList() {
            if (!this.isReady) {
                return [];
            }
            return this.tempMsgs.filter(item => item.dialog_id == this.dialogId);
        },

        allMsgList() {
            const array = [];
            array.push(...this.dialogMsgList.filter(item => this.msgFilter(item)));
            if (this.msgId > 0) {
                const dialogMsg = this.dialogMsgs.find(item => item.id == this.msgId)
                if (dialogMsg) {
                    array.unshift(dialogMsg)
                }
            }
            if (this.tempMsgList.length > 0) {
                const ids = array.map(({id}) => id)
                const tempMsgList = this.tempMsgList.filter(item => !ids.includes(item.id) && this.msgFilter(item))
                if (tempMsgList.length > 0) {
                    array.push(...tempMsgList)
                }
            }
            return array.sort((a, b) => {
                return a.id - b.id;
            })
        },

        loadMsg() {
            return this.isLoad(`msg::${this.dialogId}-${this.msgId}-${this.msgType}`)
        },

        prevId() {
            if (this.allMsgs.length > 0) {
                return $A.runNum(this.allMsgs[0].prev_id)
            }
            return 0
        },

        peopleNum() {
            return this.dialogData.type === 'group' ? $A.runNum(this.dialogData.people_user) : 0;
        },

        pasteTitle() {
            const {pasteItem} = this;
            let hasImage = pasteItem.find(({type}) => type == 'image')
            let hasFile = pasteItem.find(({type}) => type != 'image')
            if (hasImage && hasFile) {
                return '发送文件/图片'
            } else if (hasImage) {
                return '发送图片'
            }
            return '发送文件'
        },

        msgTypes({dialogData}) {
            const array = [
                {type: '', label: this.$L('消息')},
            ];
            if (dialogData.has_tag) {
                array.push({type: 'tag', label: this.$L('标注')})
            }
            if (dialogData.has_todo) {
                array.push({type: 'todo', label: this.$L('事项')})
            }
            if (dialogData.has_image) {
                array.push({type: 'image', label: this.$L('图片')})
            }
            if (dialogData.has_file) {
                array.push({type: 'file', label: this.$L('文件')})
            }
            if (dialogData.has_link) {
                array.push({type: 'link', label: this.$L('链接')})
            }
            if (dialogData.group_type === 'project') {
                array.push({type: 'project', label: this.$L('打开项目')})
            }
            if (dialogData.group_type === 'task') {
                array.push({type: 'task', label: this.$L('打开任务')})
            }
            if (dialogData.group_type === 'okr') {
                array.push({type: 'okr', label: this.$L('打开OKR')})
            }
            return array
        },

        topMsg() {
            return this.dialogData.top_msg_id && this.dialogMsgTops.find(({id}) => id == this.dialogData.top_msg_id)
        },

        quickMsgs() {
            return this.dialogData.quick_msgs || []
        },

        todoList() {
            if (!this.dialogData.todo_num) {
                return []
            }
            return this.dialogTodos.filter(item => !item.done_at && item.dialog_id == this.dialogId).sort((a, b) => {
                return b.id - a.id;
            });
        },

        isDefaultSize() {
            return !(this.keyboardShow && this.keyboardHeight > 120)
        },

        quickShow() {
            return this.isDefaultSize && this.quickMsgs.length > 0 && this.quoteId === 0
        },

        todoShow() {
            return this.isDefaultSize && this.todoList.length > 0 && this.quoteId === 0
        },

        typeShow() {
            return this.isDefaultSize && this.msgTypes.length > 1 && !this.searchShow
        },

        topShow() {
            return this.isDefaultSize && this.topMsg && !this.searchShow && this.msgType === ''
        },

        wrapperClass() {
            if (['ready', 'ing'].includes(this.recordState)) {
                return 'record-ready'
            }
            return null
        },

        navClass() {
            return {
                'completed': $A.dialogCompleted(this.dialogData),
                'tagged': this.typeShow
            }
        },

        pasteClass() {
            if (this.pasteItem.find(({type}) => type !== 'image')) {
                return ['multiple'];
            }
            return [];
        },

        msgUnreadOnly() {
            let num = 0;
            this.cacheDialogs.some(dialog => {
                if (dialog.id == this.dialogId) {
                    return false;
                }
                num += $A.getDialogNum(dialog);
            })
            if (num <= 0) {
                return '';
            }
            if (num > 999) {
                num = "999+"
            }
            return String(num);
        },

        isMyDialog() {
            const {dialogData, userId} = this;
            return dialogData.dialog_user && dialogData.dialog_user.userid == userId
        },

        isManageBot() {
            const {dialogData, userId, userIsAdmin} = this;
            if (!dialogData.bot) {
                return false
            }
            if (dialogData.bot == userId) {
                return true
            }
            return dialogData.dialog_user && dialogData.dialog_user.userid == dialogData.bot && userIsAdmin
        },

        isAiBot({dialogData}) {
            if (!dialogData.bot || dialogData.type !== 'user') {
                return false
            }
            return /^ai-(.*?)@bot\.system/.test(dialogData.email)
        },

        isMute() {
            if (this.dialogData.dialog_mute === 'close') {
                return !this.userIsAdmin
            }
            return false
        },

        isDisable() {
            return this.dialogData.is_disable ?? false
        },

        quoteData() {
            return this.getDialogQuote(this.dialogId)?.content || null
        },

        quoteUpdate() {
            return this.getDialogQuote(this.dialogId)?.type === 'update'
        },

        quoteId() {
            if (this.msgId > 0) {
                return this.msgId
            }
            return this.quoteData?.id || 0
        },

        todoViewMsg() {
            if (this.todoViewMid) {
                const msg = this.allMsgs.find(item => item.id == this.todoViewMid)
                if (msg) {
                    return msg
                }
                if (this.todoViewData.id === this.todoViewMid) {
                    return this.todoViewData
                }
            }
            return null
        },

        positionMsg({msgNew, dialogData, allMsgs, startMsgId}) {
            const {unread, unread_one, mention, mention_ids} = dialogData
            const not = unread - msgNew
            const array = []
            if (unread_one && unread_one < startMsgId) {
                array.push({
                    type: 'unread',
                    label: this.$L(`未读消息${not}条`),
                    msg_id: unread_one
                })
            }
            if (mention_ids && mention_ids.length > 0) {
                array.push(...mention_ids.map(msg_id => {
                    return {
                        type: 'mention',
                        label: this.$L(`@我的消息`),
                        msg_id
                    }
                }))
            }
            if (not <= 0 || array.length === 0 || allMsgs.length === 0) {
                return null
            }
            return array.find(item => item.type === (mention === 0 ? 'unread' : 'mention')) || array[0]
        },

        operateEmojis({cacheEmojis}) {
            const list = cacheEmojis.slice(0, 3)
            Object.values(['👌', '👍', '😂', '🎉', '❤️', '🥳️', '🥰', '😥', '😭']).some(item => {
                if (!list.includes(item)) {
                    list.push(item)
                }
            })
            return list
        },

        maxSize({systemConfig}) {
            if(systemConfig?.file_upload_limit){
                return systemConfig.file_upload_limit * 1024
            }
            return 1024000
        },

        readEnabled({msgActivity, msgPrepared}) {
            return msgActivity === 0 && msgPrepared
        },

        stickToBottom({windowActive, scrollTail, preventToBottom}) {
            return windowActive && scrollTail <= 0 && !preventToBottom
        }
    },

    watch: {
        dialogId: {
            handler(dialog_id, old_id) {
                this.getDialogBase(dialog_id)
                this.generateUnreadData(old_id)
                //
                this.$store.dispatch('openDialogEvent', dialog_id)
                this.$store.dispatch('closeDialog', {id: old_id})
                //
                window.localStorage.removeItem('__cache:vote__')
                window.localStorage.removeItem('__cache:unfoldWordChain__')
                //
                this.handlerMsgTransfer()
            },
            immediate: true
        },

        loadMsg: {
            handler(load) {
                if (load) {
                    this.loadIng++
                } else {
                    setTimeout(_ => {
                        this.loadIng--
                    }, 300)
                }
            },
            immediate: true
        },

        isReady: {
            handler(ready) {
                if (!ready) {
                    return
                }
                this.$nextTick(_ => {
                    if (this.$refs.msgs) {
                        if (!this.observers.find(({key}) => key === 'scroller')) {
                            const scrollerObserver = new ResizeObserver(this.onResizeEvent)
                            scrollerObserver.observe(this.$refs.msgs);
                            this.observers.push({key: 'scroller', observer: scrollerObserver})
                        }
                    }
                    if (this.$refs.scroller) {
                        this.scrollGroup = this.$refs.scroller.$el.querySelector('[role="group"]')
                        if (this.scrollGroup) {
                            if (!this.observers.find(({key}) => key === 'scrollGroup')) {
                                const groupObserver = new ResizeObserver(this.onResizeEvent)
                                groupObserver.observe(this.scrollGroup);
                                this.observers.push({key: 'scrollGroup', observer: groupObserver})
                            }
                        }
                    }
                })
            },
            immediate: true
        },

        msgType() {
            this.onGetMsgClear()
            this.$emit('on-type-change', this.msgType)
        },

        searchKey(key) {
            if (!key) {
                return
            }
            this.searchLoad++
            setTimeout(_ => {
                if (this.searchKey === key) {
                    this.searchLoad++
                    this.searchResult = []
                    this.searchLocation = 0
                    this.$store.dispatch("call", {
                        url: 'search/message',
                        data: {
                            dialog_id: this.dialogId,
                            key,
                            mode: 'position',
                            search_type: 'text',
                        },
                    }).then(({data}) => {
                        if (this.searchKey !== key) {
                            return
                        }
                        if (data.data.length === 0) {
                            $A.messageWarning('没有找到相关消息')
                        }
                        this.searchResult = data.data
                        this.searchLocation = this.searchResult.length
                    }).finally(_ => {
                        this.searchLoad--
                    });
                }
                this.searchLoad--
            }, 600)
        },

        searchLocation(position) {
            if (position === 0) {
                return
            }
            const id = this.searchResult[position - 1]
            if (id) {
                this.onPositionId(id)
            }
        },

        dialogSearchMsgId() {
            this.onSearchMsgId();
        },

        dialogMsgTransfer: {
            handler() {
                this.handlerMsgTransfer();
            },
            immediate: true
        },

        wsOpenNum(num) {
            if (num <= 1) {
                return
            }
            // 判断是否最后一条消息可见才重新获取消息
            const lastMsg = this.allMsgs[this.allMsgs.length - 1]
            if (!lastMsg) {
                return;
            }
            const lastEl = $A(this.$refs.scroller.$el).find(`[data-id="${lastMsg.id}"]`)
            if (lastEl.length === 0) {
                this.scrollToBottomRefresh = true
                return;
            }
            // 开始请求重新获取消息
            if (this.errorId === this.dialogId) {
                this.getDialogBase(this.dialogId)
            } else {
                this.onReGetMsg()
            }
        },

        allMsgList(list) {
            if (JSON.stringify(list) == JSON.stringify(this.allMsgs)) {
                return;
            }
            const historyLength = this.allMsgs.length
            const historyLastId = historyLength > 0 ? this.allMsgs[historyLength - 1].id : 0
            if ($A.isIos() && list.length !== historyLength && this.$refs.scroller) {
                // 隐藏区域，让iOS断触
                const scrollEl = this.$refs.scroller.$el
                scrollEl.style.overflowY = 'hidden'
                scrollEl.style.webkitOverflowScrolling = 'auto'
                this.allMsgs = list;
                requestAnimationFrame(_ => {
                    scrollEl.style.overflowY = 'auto'
                    scrollEl.style.webkitOverflowScrolling = 'touch'
                })
            } else {
                this.allMsgs = list;
            }
            //
            if (!this.stickToBottom) {
                this.msgNew += list.filter(item => item.id && item.id > historyLastId && item.userid != this.userId && !item.read_at).length
            }
        },

        'allMsgs.length' () {
            if (this.stickToBottom) {
                this.onToBottom()
            }
        },

        windowScrollY(val) {
            if ($A.isIos() && !this.$slots.head) {
                this.$refs.nav.style.marginTop = `${Math.max(0, val)}px`
            }
        },

        windowActive(active) {
            if (active && this.autoFocus) {
                const lastDialog = $A.last(this.dialogIns)
                if (lastDialog && lastDialog.uid === this._uid) {
                    this.inputFocus()
                }
            }
        },

        windowHeight() {
            requestAnimationFrame(_ => {
                this.$refs.input?.updateTools()
            })
        },

        dialogDrag(val) {
            if (val) {
                this.operateVisible = false;
            }
        },

        msgActiveId(val) {
            if (val > 0) {
                this.msgActiveId = 0
                this.shakeToMsgId(val)
            }
        },

        readLoadNum() {
            this.positionShow = true
        },

        operateVisible(val) {
            if (val || this.pointerMouse || this.focusLazy) {
                return
            }
            document.getSelection().removeAllRanges();
        },

        keyboardShow(visible) {
            if (!visible && this.operateVisible) {
                // 防止键盘关闭时操作菜单因为滚动而关闭
                this.operatePreventScroll++
                setTimeout(() => {
                    this.operatePreventScroll--
                    this.handleOperateResize()
                }, 300)
            }
        },
    },

    methods: {
        transformEmojiToHtml,
        normalizeWebhookEvents(events = [], useFallback = false) {
            if (!Array.isArray(events)) {
                events = events ? [events] : [];
            }
            const allowed = this.webhookEventOptions.map(item => item.value);
            const result = events.filter(item => allowed.includes(item));
            if (result.length) {
                return Array.from(new Set(result));
            }
            return [];
        },
        prepareWebhookEvents(events, useFallback = false) {
            let value = events;
            if (typeof value === 'undefined' || value === null) {
                value = [];
            }
            value = this.normalizeWebhookEvents(value, false);
            if (!value.length && useFallback) {
                return [];
            }
            return value;
        },
        /**
         * 获取会话基本信息
         * @param dialog_id
         */
        getDialogBase(dialog_id) {
            if (!dialog_id) {
                return
            }

            this.msgNew = 0
            this.msgType = ''
            this.searchKey = ''
            this.unreadOne = 0
            this.startMsgId = 0
            this.scrollTail = 0
            this.scrollOffset = 0
            this.searchShow = false
            this.positionShow = false
            this.msgPrepared = false
            this.scrollToBottomRefresh = false
            this.replyMsgAutoMention = false
            this.allMsgs = this.allMsgList
            this.errorId = 0
            //
            this.waitUnreadData.delete(dialog_id)
            this.getMsgs({
                dialog_id,
                msg_id: this.msgId,
                msg_type: this.msgType,
            }).then(({data}) => {
                this.openId = dialog_id
                this.msgPrepared = true
                //
                const unreadIds = this.waitUnreadData.get(dialog_id) || []
                if (unreadIds.length > 0) {
                    const ids = [...data.list.map(item => item.id)].reverse();
                    $A.getLastSameElements(unreadIds, ids).forEach(id => {
                        this.$store.dispatch("dialogMsgRead", {id, dialog_id})
                    })
                }
                //
                setTimeout(_ => {
                    this.onSearchMsgId()
                    this.positionShow = this.readTimeout === null
                    if (this.startMsgId === 0 && data.list.length > 0) {
                        this.startMsgId = data.list[data.list.length - 1].id
                    }
                }, 100)
            }).catch(_ => {
                this.errorId = dialog_id
            });
            //
            this.$store.dispatch('saveInDialog', {
                uid: this._uid,
                dialog_id,
            })
            //
            if (this.autoFocus) {
                this.inputFocus()
            }
            //
            this.getUserApproveStatus()
        },

        /**
         * 关闭会话前记录未读数据
         * @param dialog_id
         */
        generateUnreadData(dialog_id) {
            if (!dialog_id) {
                return
            }
            const ens = []
            const ids = this.allMsgs.filter(item => item.read_at === null && item.userid != this.userId).map(item => item.id)
            const enters = this.$refs.scroller?.$el.querySelectorAll('.item-enter') || []
            for (const enter of enters) {
                const id = $A.runNum(enter.querySelector(".dialog-view")?.getAttribute('data-id'));
                if (id && !ids.includes(id)) {
                    ids.push(id)
                }
            }
            this.waitUnreadData.set(dialog_id, $A.getLastSameElements(ids, ens))
        },

        /**
         * 发送数据处理
         * @param data
         * @returns {*}
         */
        sendDataHandle(data) {
            if (this.isAiBot) {
                data.model_name = this.aiModelValue()
            }
            return data
        },

        /**
         * 发送消息
         * @param text
         * @param type
         */
        sendMsg(text, type) {
            let textBody,
                textType = "text",
                silence = "no",
                emptied = false;
            if (typeof text === "string" && text) {
                textBody = text;
            } else {
                textBody = type === "md" ? this.$refs.input.getText() : this.msgText;
                emptied = true;
            }
            if (type === "md") {
                textType = "md"
            } else if (type === "silence") {
                silence = "yes"
            }
            if (textBody == '') {
                this.inputFocus();
                return;
            }
            if (textType === "text") {
                textBody = textBody
                    .replace(/<\/span> <\/p>$/, "</span></p>")
                    .replace(/(<span\s+class="mention"(.*?)>.*?<\/span>.*?<\/span>.*?<\/span>)(\x20)?/, "$1 ")
            }
            //
            if (this.quoteUpdate) {
                // 修改
                if (textType === "text") {
                    textBody = textBody.replace(new RegExp(`src=(["'])${$A.mainUrl()}`, "g"), "src=$1{{RemoteURL}}")
                }
                const update_id = this.quoteId
                this.$store.dispatch("setLoad", {
                    key: `msg-${update_id}`,
                    delay: 600
                })
                this.cancelQuote()
                this.onActive()
                //
                this.$store.dispatch("call", {
                    url: 'dialog/msg/sendtext',
                    data: this.sendDataHandle({
                        dialog_id: this.dialogId,
                        update_id,
                        text: textBody,
                        text_type: textType,
                        silence,
                    }),
                    method: 'post',
                    complete: _ => this.$store.dispatch("cancelLoad", `msg-${update_id}`)
                }).then(({data}) => {
                    this.sendSuccess(data, 0, true)
                    this.onPositionId(update_id)
                }).catch(({msg}) => {
                    $A.modalError(msg)
                });
            } else {
                // 发送
                const typeLoad = $A.stringLength(textBody.replace(/<img[^>]*?>/g, '')) > 5000
                const tempMsg = {
                    id: this.getTempId(),
                    dialog_id: this.dialogData.id,
                    reply_id: this.quoteId,
                    type: typeLoad ? 'loading' : 'text',
                    userid: this.userId,
                    msg: {
                        type: textType,
                        text: typeLoad ? '' : textBody,
                        reply_data: this.quoteData,
                    },
                }
                this.tempMsgs.push(tempMsg)
                this.msgType = ''
                this.cancelQuote()
                this.onActive()
                this.$nextTick(this.onToBottom)
                //
                this.$store.dispatch("call", {
                    requestId: tempMsg.id,
                    url: 'dialog/msg/sendtext',
                    data: this.sendDataHandle({
                        dialog_id: tempMsg.dialog_id,
                        reply_id: tempMsg.reply_id,
                        text: textBody,
                        text_type: textType,
                        silence,
                    }),
                    method: 'post',
                }).then(({data}) => {
                    this.sendSuccess(data, tempMsg.id)
                }).catch(error => {
                    this.$set(tempMsg, 'error', true)
                    this.$set(tempMsg, 'errorData', {type: 'text', mType: type, content: error.msg, msg: textBody})
                });
            }
            if (emptied) {
                requestAnimationFrame(_ => this.msgText = '')
            }
        },

        /**
         * 发送录音
         * @param msg {base64, duration}
         */
        sendRecord(msg) {
            const tempMsg = {
                id: this.getTempId(),
                dialog_id: this.dialogData.id,
                reply_id: this.quoteId,
                type: 'record',
                userid: this.userId,
                msg: Object.assign(msg, {
                    reply_data: this.quoteData,
                }),
            }
            this.tempMsgs.push(tempMsg)
            this.msgType = ''
            this.cancelQuote()
            this.onActive()
            this.$nextTick(this.onToBottom)
            //
            this.$store.dispatch("call", {
                requestId: tempMsg.id,
                url: 'dialog/msg/sendrecord',
                data: Object.assign(msg, {
                    dialog_id: this.dialogId,
                    reply_id: this.quoteId,
                }),
                method: 'post',
            }).then(({data}) => {
                this.sendSuccess(data, tempMsg.id);
            }).catch(error => {
                this.$set(tempMsg, 'error', true)
                this.$set(tempMsg, 'errorData', {type: 'record', mType: 'record', content: error.msg, msg})
            });
        },

        /**
         * 发送文件
         * @param row
         */
        sendFileMsg(row) {
            const files = $A.isArray(row) ? row : [row];
            if (files.length > 0) {
                this.pasteFile = [];
                this.pasteItem = [];
                files.some(file => {
                    if (file.type === 'photo') {
                        this.sendPhoto(file.msg)
                        return false;
                    }
                    const item = {
                        type: $A.getMiddle(file.type, null, '/'),
                        name: file.name,
                        size: file.size,
                        result: null
                    }
                    if (item.type === 'image') {
                        const reader = new FileReader();
                        reader.readAsDataURL(file);
                        reader.onload = ({target}) => {
                            item.result = target.result
                            this.pasteFile.push(file)
                            this.pasteItem.push(item)
                            this.pasteShow = true
                        }
                    } else {
                        this.pasteFile.push(file)
                        this.pasteItem.push(item)
                        this.pasteShow = true
                    }
                });
            }
        },

        /**
         * 发送照片
         * @param msg
         */
        sendPhoto(msg) {
            const tempMsg = {
                id: $A.randNum(1000000000, 9999999999),
                file_uid: 0,
                file_method: 'photo',
                dialog_id: this.dialogData.id,
                reply_id: this.quoteId,
                type: 'file',
                userid: this.userId,
                msg,
            }
            this.tempMsgs.push(tempMsg)
            //
            $A.eeuiAppUploadPhoto({
                url: $A.apiUrl('dialog/msg/sendfile'),
                data: {
                    dialog_id: tempMsg.dialog_id,
                    filename: msg.filename
                },
                headers: {
                    token: this.userToken,
                },
                path: msg.path,
                fieldName: "files",
                onReady: (id) => {
                    this.$set(tempMsg, 'file_uid', id)
                },
            }).then(data => {
                this.sendSuccess(data, tempMsg.id)
            }).catch(({msg}) => {
                this.forgetTempMsg(tempMsg.id)
                $A.messageError(msg || "上传失败")
            })
        },

        /**
         * 发送位置消息
         * @param data
         */
        sendLocationMsg(data) {
            this.$store.dispatch("call", {
                url: 'dialog/msg/sendlocation',
                data: Object.assign(data, {
                    dialog_id: this.dialogId,
                }),
                spinner: true,
                method: 'post',
            }).then(({data}) => {
                this.sendSuccess(data)
            }).catch(({msg}) => {
                $A.modalConfirm({
                    icon: 'error',
                    title: '发送失败',
                    content: msg,
                    cancelText: '取消发送',
                    okText: '重新发送',
                    onOk: _ => {
                        this.sendLocationMsg(data)
                    },
                })
            });
        },

        /**
         * Ai模型值
         * @returns {*}
         */
        aiModelValue() {
            const item = this.dialogAiModel.find(({dialog_id}) => dialog_id == this.dialogId)
            return item?.model
        },

        /**
         * 快捷菜单标签
         * @param key
         * @param label
         * @param config
         * @returns {*}
         */
        quickLabel({key, label, config}) {
            if (key === '~ai-model-select') {
                const model = this.aiModelValue()
                if (model) {
                    label = model
                } else if (config?.model) {
                    label = config.model
                }
                if (config?.models) {
                    config.models.forEach(({value, label: text}) => {
                        if (value === label) {
                            label = text
                        }
                    })
                }
            }
            return label
        },

        /**
         * 发送快捷消息
         * @param item
         * @param event
         */
        sendQuick(item, event = undefined) {
            switch (item.key) {
                // 位置签到
                case "locat-checkin":
                    this.$store.dispatch('openAppMapPage', {
                        type: item.config.type,
                        key: item.config.key,
                        point: `${item.config.lng},${item.config.lat}`,
                        radius: item.config.radius,
                    }).then(data => {
                        if (!$A.isJson(data)) {
                            return
                        }
                        this.sendLocationMsg({
                            type: item.config.type,
                            lng: data.point.lng,
                            lat: data.point.lat,
                            title: data.title,
                            distance: data.distance,
                            address: data.address || '',
                            thumb: data.thumb,
                        })
                    })
                    break;

                // 创建会议
                case "meeting-create":
                    emitter.emit('addMeeting', {
                        type: 'create',
                        userids: [this.userId],
                    });
                    break;

                // 加入会议
                case "meeting-join":
                    emitter.emit('addMeeting', {
                        type: 'join',
                    });
                    break;

                // 选择模型
                case "~ai-model-select":
                    if (!this.isAiBot) {
                        return
                    }
                    const models = item.config?.models
                    const list = $A.isArray(models) ? models : []
                    let active = this.aiModelValue()
                    if (!active && item.config?.model) {
                        active = item.config.model
                    }
                    this.$store.commit('menu/operation', {
                        event,
                        list,
                        active,
                        language: false,
                        onUpdate: async model => {
                            this.dialogAiModel = [
                                ...this.dialogAiModel.filter(({dialog_id}) => dialog_id !== this.dialogId),
                                {dialog_id: this.dialogId, model}
                            ]
                            await $A.IDBSet('dialogAiModel', this.dialogAiModel)
                        }
                    })
                    break;

                // 开启新会话
                case "~ai-session-create":
                    // 创建新会话
                    this.$store.dispatch("call", {
                        url: 'dialog/session/create',
                        data: {
                            dialog_id: this.dialogId,
                        },
                        spinner: 300
                    }).then(() => {
                        this.onGetMsgClear()
                    }).catch(({msg}) => {
                        $A.modalError(msg)
                    });
                    break;

                // 历史会话
                case "~ai-session-history":
                    this.sessionHistoryData = {
                        dialog_id: this.dialogId,
                        name: this.dialogData.name,
                    }
                    this.sessionHistoryShow = true
                    break;

                // 发送快捷指令
                default:
                    if (/^~/.test(item.key)) {
                        $A.modalWarning("当前客户端不支持该指令");
                        break;
                    }
                    this.sendMsg(`<p><span data-quick-key="${item.key}">${item.label}</span></p>`)
                    break;
            }
        },

        /**
         * 收到websocket消息
         * @param msgDetail
         */
        onWebsocketMsg(msgDetail) {
            if (!$A.isSubElectron) {
                return
            }
            const {type, mode, data} = msgDetail;
            if (type === 'dialog' && mode === 'add') {
                this.tempMsgs.push(data)
            }
        },

        /**
         * 消息变化处理
         * @param data
         */
        onMsgChange(data) {
            const item = this.allMsgs.find(({type, id}) => type == "text" && id == data.id)
            if (!item) {
                return
            }
            if (typeof this.msgChangeCache[data.id] === "undefined") {
                this.msgChangeCache[data.id] = []
                this.msgChangeCache[`${data.id}_load`] = false
            }
            switch (data.type) {
                case 'append':
                    data.text && this.msgChangeCache[data.id].push(...`${data.text}`.split("").map(text => {
                        return {
                            type: 'append',
                            text
                        }
                    }))
                    break;
                case 'replace':
                    this.msgChangeCache[data.id] = [{
                        type: 'replace',
                        text: data.text
                    }]
                    break;
            }
            this.onMsgOutput(data.id, item.msg)
        },

        /**
         * 追加或替换消息
         * @param id
         * @param msg
         */
        onMsgOutput(id, msg) {
            const load = `${id}_load`
            const arr = this.msgChangeCache[id]
            if (!arr || arr.length === 0) return

            if (this.msgChangeCache[load] === true) return
            this.msgChangeCache[load] = true

            try {
                if (arr.length === 0) {
                    this.msgChangeCache[load] = false
                    return
                }

                const {tail} = this.scrollInfo()

                const getBatchSize = (length) => {
                    if (length <= 5) return 1;       // 少量消息时逐个处理
                    else if (length <= 20) return 2; // 适中数量时一次处理2个
                    else if (length <= 50) return 5; // 较多消息时一次处理5个
                    else return 10;                  // 大量消息时一次处理10个
                }
                const batch = arr.splice(0, getBatchSize(arr.length));

                let finalText = msg.text;
                for (const data of batch) {
                    const {type, text} = data;
                    if (type === 'append') {
                        finalText += text;
                    } else if (type === 'replace') {
                        finalText = text;
                    }
                }
                msg.text = finalText;

                this.$nextTick(_ => {
                    if (tail <= 10 && tail != this.scrollInfo().tail) {
                        this.operatePreventScroll++
                        this.$refs.scroller.scrollToBottom()
                        setTimeout(_ => this.operatePreventScroll--, 50)
                    }

                    if (arr.length === 0) {
                        this.msgChangeCache[load] = false
                        return
                    }
                    setTimeout(_ => {
                        this.msgChangeCache[load] = false
                        finalText === msg.text && this.onMsgOutput(id, msg)
                    }, 5)
                })
            } catch (e) {
                this.msgChangeCache[load] = false
            }
        },

        getTempId() {
            return this.tempId++
        },

        getMsgs(data) {
            return new Promise((resolve, reject) => {
                setTimeout(_ => this.msgLoadIng++, 2000)
                $A.isSubElectron && $A.syncPauses.set("saveDialogMsg", true)
                this.$store.dispatch("getDialogMsgs", data)
                    .then(resolve)
                    .catch(reject)
                    .finally(_ => {
                        this.msgLoadIng--
                        $A.isSubElectron && $A.syncPauses.delete("saveDialogMsg")
                    })
            })
        },

        msgFilter(item) {
            if (this.msgType) {
                if (this.msgType === 'tag') {
                    if (!item.tag) {
                        return false
                    }
                } else if (this.msgType === 'todo') {
                    if (!item.todo) {
                        return false
                    }
                } else if (this.msgType === 'link') {
                    if (!item.link) {
                        return false
                    }
                } else if (this.msgType !== item.mtype) {
                    return false
                }
            }
            if (this.msgId) {
                if (item.reply_id != this.msgId) {
                    return false
                }
            }
            return true
        },

        onSearchMsgId() {
            if (this.dialogSearchMsgId > 0 && this.openId === this.dialogId) {
                this.onPositionId(this.dialogSearchMsgId)
                this.$store.state.dialogSearchMsgId = 0
            }
        },

        onPositionId(position_id, msg_id = 0, loop_num = 0) {
            return new Promise((resolve, reject) => {
                if (position_id === 0) {
                    $A.modalError("查看失败：参数错误")
                    reject()
                    return
                }
                //
                if (this.loadMsg || this.msgType !== '') {
                    this.msgType = ''
                    if (loop_num === 0) {
                        this.$store.dispatch("showSpinner", 600)
                    } else if (loop_num > 20) {
                        this.$store.dispatch("hiddenSpinner")
                        $A.modalError("查看失败：请求超时")
                        reject()
                        return;
                    }
                    loop_num++
                    setTimeout(_ => {
                        this.onPositionId(position_id, msg_id, loop_num).then(resolve).catch(reject)
                    }, Math.min(800, 200 * loop_num))
                    return;
                }
                if (loop_num > 0) {
                    this.$store.dispatch("hiddenSpinner")
                }
                //
                const index = this.allMsgs.findIndex(item => item.id === position_id)
                const gtpos = this.prevId > 0 ? 0 : -1  // 如果还有更多消息时定位的消息必须不是第一条是为了避免定位后又有新加载
                if (index > gtpos) {
                    setTimeout(_ => {
                        this.onToIndex(index, position_id)
                        resolve()
                    }, 200)
                } else {
                    if (msg_id > 0) {
                        this.$store.dispatch("setLoad", {
                            key: `msg-${msg_id}`,
                            delay: 600
                        })
                    }
                    this.getMsgs({
                        dialog_id: this.dialogId,
                        msg_id: this.msgId,
                        msg_type: this.msgType,
                        position_id,
                        spinner: 2000,
                        save_before: _ => {
                            this.preventToBottom = true
                        },
                        save_after: _ => {
                            this.$nextTick(_ => {
                                this.preventToBottom = false
                            })
                        }
                    }).finally(_ => {
                        const index = this.allMsgs.findIndex(item => item.id === position_id)
                        if (index > -1) {
                            this.onToIndex(index, position_id)
                            resolve()
                        }
                        if (msg_id > 0) {
                            this.$store.dispatch("cancelLoad", `msg-${msg_id}`)
                        }
                    })
                }
            })
        },

        onViewTodo(item) {
            if (this.operateVisible) {
                return
            }
            this.todoViewId = item.id
            this.todoViewMid = item.msg_id
            this.todoViewShow = true
            //
            const index = this.allMsgs.findIndex(item => item.id === this.todoViewMid)
            if (index === -1) {
                this.$store.dispatch("call", {
                    url: 'dialog/msg/one',
                    data: {
                        msg_id: this.todoViewMid
                    },
                }).then(({data}) => {
                    this.todoViewData = data
                })
            }
        },

        onCloseTodo() {
            this.todoViewLoad = false
            this.todoViewShow = false
            this.todoViewData = {}
            this.todoViewMid = 0
            this.todoViewId = 0
        },

        onPosTodo() {
            if (!this.todoViewMid) {
                return
            }
            this.todoViewPosLoad = true
            this.onPositionId(this.todoViewMid).then(this.onCloseTodo).finally(_ => {
                this.todoViewPosLoad = false
            })
        },

        onDoneTodo() {
            if (!this.todoViewId || this.todoViewLoad) {
                return
            }
            this.todoViewLoad = true
            //
            this.$store.dispatch("call", {
                url: 'dialog/msg/done',
                data: {
                    id: this.todoViewId,
                },
            }).then(({data}) => {
                this.$store.dispatch("saveDialogTodo", {
                    id: this.todoViewId,
                    done_at: $A.daytz().format("YYYY-MM-DD HH:mm:ss")
                })
                this.$store.dispatch("saveDialog", {
                    id: this.dialogId,
                    todo_num: this.todoList.length
                })
                if (data.add) {
                    this.sendSuccess(data.add)
                }
                if (data.update) {
                    this.sendSuccess(data.update, 0, true)
                }
                if (this.todoList.length === 0) {
                    this.$store.dispatch("getDialogTodo", this.dialogId)
                }
                this.onCloseTodo()
            }).catch(({msg}) => {
                $A.modalError(msg)
            }).finally(_ => {
                this.todoViewLoad = false
            });
        },

        inputFocus() {
            this.$nextTick(_ => {
                this.$refs.input?.focus()
            })
        },

        onRecordState(state) {
            this.recordState = state;
        },

        chatPasteDrag(e, type) {
            this.dialogDrag = false;
            if ($A.dataHasFolder(type === 'drag' ? e.dataTransfer : e.clipboardData)) {
                e.preventDefault();
                $A.modalWarning(`暂不支持${type === 'drag' ? '拖拽' : '粘贴'}文件夹。`)
                return;
            }
            const files = type === 'drag' ? e.dataTransfer.files : e.clipboardData.files;
            const postFiles = Array.prototype.slice.call(files);
            if (postFiles.length > 0) {
                e.preventDefault();
                this.sendFileMsg(postFiles);
            }
        },

        chatDragOver(show, e) {
            let random = (this.__dialog_drag = $A.randomString(8));
            if (!show) {
                setTimeout(() => {
                    if (random === this.__dialog_drag) {
                        this.dialogDrag = show;
                    }
                }, 150);
            } else {
                if (e.dataTransfer.effectAllowed === 'move') {
                    return;
                }
                this.dialogDrag = true;
            }
        },

        onTouchStart() {
            // Android 阻止长按反馈导致失去焦点页面抖动
            if (this.keyboardShow) {
                $A.eeuiAppSetDisabledUserLongClickSelect(500);
            }
        },

        onPointerover({pointerType}) {
            this.pointerMouse = pointerType === 'mouse';
        },

        pasteSend() {
            if (this.__paste_send_index) {
                return;
            }
            this.__paste_send_index = 1;
            setTimeout(() => {
                this.__paste_send_index = 0;
            }, 300)
            this.pasteFile.some(file => {
                this.$refs.chatUpload.upload(file)
            });
        },

        chatFile(type, file) {
            switch (type) {
                case 'progress':
                    const percentage = file.showProgress ? Math.max(file.percentage, 0.01) : false
                    const temp = this.tempMsgs.find(({id}) => id == file.tempId);
                    if (temp) {
                        temp.msg.percentage = percentage
                        return;
                    }
                    const tempMsg = {
                        id: file.tempId,
                        file_uid: file.uid,
                        file_method: 'uplaod',
                        dialog_id: this.dialogData.id,
                        reply_id: this.quoteId,
                        type: 'file',
                        userid: this.userId,
                        msg: Object.assign(file.msg || {}, {percentage}),
                    }
                    this.tempMsgs.push(tempMsg)
                    this.msgType = ''
                    this.cancelQuote()
                    this.onActive()
                    this.$nextTick(this.onToBottom)
                    break;

                case 'error':
                    this.forgetTempMsg(file.tempId)
                    break;

                case 'success':
                    this.sendSuccess(file.data, file.tempId)
                    break;
            }
        },

        sendSuccess(data, tempId = 0, isUpdate = false) {
            if ($A.isArray(data)) {
                data.some(item => {
                    this.sendSuccess(item, tempId)
                })
                return;
            }
            if (tempId > 0) {
                const index = this.tempMsgs.findIndex(({id}) => id == tempId)
                if (index > -1) {
                    if (data.type === 'text') {
                        const tempMsg = this.tempMsgs[index]
                        if (tempMsg) {
                            data.msg.text = this.replaceImgSrcAndKeepOriginal(data.msg.text, tempMsg.msg.text)
                        }
                    }
                    this.tempMsgs.splice(index, 1, data)
                }
                setTimeout(_ => {
                    this.forgetTempMsg(tempId)
                    this.forgetTempMsg(data.id)
                }, 1000)
            }
            this.$store.dispatch("saveDialog", {
                id: this.dialogId,
                hide: 0,
            })
            this.$store.dispatch("saveDialogMsg", data);
            if (!isUpdate) {
                this.$store.dispatch("increaseTaskMsgNum", {id: data.dialog_id});
                this.$store.dispatch("increaseMsgReplyNum", {id: data.reply_id});
                this.$store.dispatch("updateDialogLastMsg", data);
            }
            this.cancelQuote();
            this.onActive();
        },

        replaceImgSrcAndKeepOriginal(dataHtml, tempHtml) {
            const tempImgs = [];
            const dataImgs = [];
            tempHtml = tempHtml || '';
            dataHtml = dataHtml || '';
            tempHtml.replace(/<img [^>]*src=["']([^"']+)["'][^>]*>/g, (m, src) => { tempImgs.push(src); return m; });
            dataHtml.replace(/<img [^>]*src=["']([^"']+)["'][^>]*>/g, (m, src) => { dataImgs.push(src); return m; });
            if (tempImgs.length !== dataImgs.length || dataImgs.length === 0) {
                return dataHtml;
            }
            let imgIndex = 0;
            return dataHtml.replace(/<img ([^>]*?)src=("|')([^"']+)\2([^>]*)>/g, (match, before, quote, src, after) => {
                const newSrc = tempImgs[imgIndex] || src;
                const originalSrc = src;
                imgIndex++;
                let originalSrcAttr = '';
                if (!/original-src=/.test(match)) {
                    originalSrcAttr = ` original-src=\"${originalSrc}\"`;
                }
                return `<img ${before}src=${quote}${newSrc}${quote}${originalSrcAttr}${after}>`;
            });
        },

        forgetTempMsg(tempId) {
            this.tempMsgs = this.tempMsgs.filter(({id}) => id != tempId)
        },

        setQuote(id, type) {
            this.$refs.input?.setQuote(id, type)
        },

        cancelQuote() {
            this.$refs.input?.cancelQuote()
        },

        onEventFocus() {
            this.focusTimer && clearTimeout(this.focusTimer)
            this.focusLazy = true
            this.$emit("on-focus")
        },

        onEventBlur() {
            this.focusTimer = setTimeout(_ => this.focusLazy = false, 10)
            this.$emit("on-blur")
        },

        onEventMore(e) {
            switch (e) {
                case 'image':
                case 'file':
                    this.$refs.chatUpload.handleClick()
                    break;

                case 'call':
                    this.onCallTel()
                    break;
                case 'anon':
                    this.onAnon()
                    break;
            }
        },

        onCallTel() {
            $A.modalConfirm({
                content: `是否拨打电话给 ${this.dialogData.name}？`,
                onOk: () => {
                    this.$store.dispatch("call", {
                        url: 'dialog/tel',
                        data: {
                            dialog_id: this.dialogId,
                        },
                        spinner: 600,
                    }).then(({data}) => {
                        if (data.tel) {
                            $A.eeuiAppSendMessage({
                                action: 'callTel',
                                tel: data.tel
                            });
                        }
                        if (data.add) {
                            this.$store.dispatch("saveDialogMsg", data.add);
                            this.$store.dispatch("updateDialogLastMsg", data.add);
                            this.onActive();
                        }
                    }).catch(({msg}) => {
                        $A.modalError(msg);
                    });
                }
            });
        },

        onAnon() {
            if (this.dialogData.type !== 'user' || this.dialogData.bot) {
                $A.modalWarning("匿名消息仅允许发送给个人");
                return
            }
            $A.modalInput({
                title: `发送匿名消息`,
                placeholder: `匿名消息将通过匿名消息（机器人）发送给对方，不会记录你的任何身份信息`,
                inputProps: {
                    type: 'textarea',
                    rows: 3,
                    autosize: { minRows: 3, maxRows: 6 },
                    maxlength: 2000,
                },
                okText: "匿名发送",
                onOk: (value) => {
                    if (!value) {
                        return `请输入消息内容`
                    }
                    return new Promise((resolve, reject) => {
                        this.$store.dispatch("call", {
                            url: 'dialog/msg/sendanon',
                            data: {
                                userid: this.dialogData.dialog_user.userid,
                                text: value,
                            },
                            method: 'post',
                        }).then(({msg}) => {
                            resolve(msg)
                        }).catch(({msg}) => {
                            reject(msg)
                        });
                    })
                }
            });
        },

        onResizeEvent(entries) {
            entries.some(({target, contentRect}) => {
                if (target === this.$refs.msgs) {
                    this.onMsgsResize(contentRect)
                } else if (target === this.scrollGroup) {
                    this.onScrollGroupResize(contentRect)
                }
            })
        },

        onMsgsResize({height}) {
            this.$refs.scroller.$el.style.height = `${height}px`
            //
            if (typeof this.__msgs_height !== "undefined") {
                const size = this.__msgs_height - height;
                if (size !== 0) {
                    const {offset, tail} = this.scrollInfo()
                    if (tail > 0) {
                        this.onToOffset(offset + size)
                    }
                }
            }
            this.__msgs_height = height;
        },

        onScrollGroupResize() {
            if (this.stickToBottom) {
                this.onToBottom()
            }
        },

        onActive() {
            this.$emit("on-active");
        },

        onClickFooter() {
            this.$refs.input?.focus();
            this.onActive();
        },

        onToBottom() {
            this.msgNew = 0;
            const scroller = this.$refs.scroller;
            if (scroller) {
                this.preventLoad().then(_ => {
                    scroller.scrollToBottom();
                })
            }
        },

        onToIndex(index, id) {
            const scroller = this.$refs.scroller;
            if (scroller) {
                scroller.stopToBottom();
                const element = scroller.$el.querySelector(`[data-id="${id}"]`)
                if (!element?.parentNode.parentNode.classList.contains('item-enter')) {
                    this.preventLoad().then(_ => {
                        scroller.scrollToIndex(index, -80);
                    })
                }
            }
            requestAnimationFrame(_ => this.msgActiveId = id)
        },

        onToOffset(offset, forceFront = false) {
            const scroller = this.$refs.scroller;
            if (scroller) {
                const front = scroller.getOffset() > offset
                scroller.stopToBottom();
                scroller.scrollToOffset(offset);
                setTimeout(_ => {
                    if (front || forceFront) {
                        scroller.virtual.handleFront()
                    } else {
                        scroller.virtual.handleBehind()
                    }
                }, 10)
            }
        },

        preventLoad() {
            return new Promise(resolve => {
                this.preventPrevLoad++
                this.preventRangeLoad++
                resolve()
                requestAnimationFrame(_ => {
                    this.preventPrevLoad--
                    this.preventRangeLoad--
                })
            })
        },

        scrollInfo() {
            const scroller = this.$refs.scroller;
            if (scroller) {
                return scroller.scrollInfo();
            } else {
                return {
                    offset: 0,
                    scale: 0,
                    tail: 0
                }
            }
        },

        openProject() {
            if (!this.dialogData.group_info) {
                return;
            }
            if (!this.isMessengerPage || this.windowPortrait) {
                // 如果 当前不是消息页面 或 是竖屏 则关闭对话窗口
                this.$store.dispatch("openDialog", 0);
            }
            this.goForward({name: 'manage-project', params: {projectId:this.dialogData.group_info.id}});
        },

        openTask() {
            if (!this.dialogData.group_info) {
                return;
            }
            this.$store.dispatch("openTask", {
                id: this.dialogData.group_info.id,
                deleted_at: this.dialogData.group_info.deleted_at,
                archived_at: this.dialogData.group_info.archived_at,
            });
        },

        openOkrDetails(id) {
            if (!id) {
                return;
            }
            this.$store.dispatch("openMicroApp", {
                id: 'okr',
                name: 'okr_details',
                url: 'apps/okr/#details',
                props: {open_type: 'details', id},
                keep_alive: false,
                transparent: true,
            });
        },

        onSessionSubmit() {
            this.sessionHistoryShow = false;
            this.onGetMsgClear();
        },

        onGetMsgClear() {
            this.getMsgs({
                dialog_id: this.dialogId,
                msg_id: this.msgId,
                msg_type: this.msgType,
                clear_before: true
            }).then(_ => {
                this.onToBottom()
            }).catch(_ => {})
        },

        onReGetMsg() {
            this.scrollToBottomRefresh = false
            this.getMsgs({
                dialog_id: this.dialogId,
                msg_id: this.msgId,
                msg_type: this.msgType,
            }).catch(_ => {});
        },

        onPrevPage() {
            if (this.prevId === 0 || this.preventPrevLoad > 0) {
                return
            }
            this.getMsgs({
                dialog_id: this.dialogId,
                msg_id: this.msgId,
                msg_type: this.msgType,
                prev_id: this.prevId,
                save_before: _ => this.scrollDisabled = true,
                save_after: _ => this.scrollDisabled = false
            }).then(({data}) => {
                const ids = data.list.map(item => item.id)
                this.$nextTick(() => {
                    const scroller = this.$refs.scroller
                    const reducer = ids.reduce((previousValue, currentId) => {
                        const previousSize = typeof previousValue === "object" ? previousValue.size : scroller.getSize(previousValue)
                        return {size: previousSize + scroller.getSize(currentId)}
                    })
                    this.onToOffset(scroller.getOffset() + reducer.size, true)
                });
            }).catch(() => {})
        },

        onDialogMenu(cmd) {
            switch (cmd) {
                case "single":
                    this.$store.dispatch('openDialog', {dialog_id: this.dialogData.id, single: true});
                    !this.isMessengerPage && this.$store.dispatch('openDialog', 0);
                    break;

                case "searchMsg":
                    this.searchShow = true
                    this.$nextTick(_ => {
                        this.$refs.searchInput.focus()
                    })
                    break;

                case "openCreate":
                    const userids = [this.userId]
                    if (this.dialogData.dialog_user && this.userId != this.dialogData.dialog_user.userid) {
                        userids.push(this.dialogData.dialog_user.userid)
                    }
                    emitter.emit('createGroup', userids);
                    break;

                case "modifyNormal":
                    this.modifyData = {
                        dialog_id: this.dialogData.id,
                        avatar: this.dialogData.avatar,
                        name: this.dialogData.name
                    }
                    if (this.dialogData.type === 'user') {
                        // 机器人
                        this.modifyData = Object.assign(this.modifyData, {
                            userid: this.dialogData.dialog_user.userid,
                            avatar: this.cacheUserBasic.find(item => item.userid === this.dialogData.dialog_user.userid)?.userimg,
                            clear_day: 0,
                            webhook_url: '',
                            system_name: '',
                            webhook_events: [],
                        })
                        this.webhookEvents = this.prepareWebhookEvents([], true)
                        this.modifyLoad++;
                        this.$store.dispatch("call", {
                            url: 'users/bot/info',
                            data: {
                                id: this.dialogData.dialog_user.userid
                            },
                        }).then(({data}) => {
                            this.modifyData.clear_day = data.clear_day
                            this.modifyData.webhook_url = data.webhook_url
                            this.modifyData.system_name = data.system_name
                            this.webhookEvents = this.prepareWebhookEvents(data.webhook_events, true)
                        }).finally(() => {
                            this.modifyLoad--;
                        })
                    }
                    this.modifyShow = true
                    break;

                case "modifyAi":
                    this.modifyData = {
                        dialog_id: this.dialogData.id,
                        type: 'ai_prompt'
                    }
                    this.modifyLoad++;
                    this.$store.dispatch("call", {
                        url: 'dialog/config',
                        data: this.modifyData,
                    }).then(({data}) => {
                        this.modifyData.value = data.value
                    }).finally(() => {
                        this.modifyLoad--;
                    })
                    this.modifyAiShow = true
                    break;

                case "modifyAdmin":
                    this.modifyData = {
                        dialog_id: this.dialogData.id,
                        avatar: this.dialogData.avatar,
                        admin: 1
                    }
                    this.modifyShow = true
                    break;

                case "previewDetail":
                    emitter.emit("openUser", this.dialogData.dialog_user?.userid)
                    break;

                case "previewAvatar":
                    if (this.dialogData.type === 'user') {
                        this.$store.dispatch("previewImage", this.dialogData.userimg)
                    } else {
                        this.$store.dispatch("previewImage", this.dialogData.avatar)
                    }
                    break;

                case "groupInfo":
                    this.groupInfoShow = true
                    break;

                case "transfer":
                    this.groupTransferData = {
                        dialog_id: this.dialogId,
                        userid: [],
                        disabledChoice: [this.userId]
                    }
                    this.groupTransferShow = true
                    break;

                case "transferConfirm":
                    this.onTransferGroup()
                    break;

                case "disband":
                    this.onDisbandGroup()
                    break;

                case "exit":
                    this.onExitGroup()
                    break;

                case "report":
                    this.reportShow = true
                    break;
            }
        },

        onTransferGroup() {
            if (this.groupTransferData.userid.length === 0) {
                $A.messageError("请选择新的群主");
                return
            }
            this.groupTransferLoad++;
            this.$store.dispatch("call", {
                url: 'dialog/group/transfer',
                data: {
                    dialog_id: this.dialogId,
                    userid: this.groupTransferData.userid[0]
                }
            }).then(({data, msg}) => {
                $A.messageSuccess(msg);
                this.$store.dispatch("saveDialog", data);
            }).catch(({msg}) => {
                $A.modalError(msg);
            }).finally(_ => {
                this.groupTransferLoad--;
                this.groupTransferShow = false;
            });
        },

        onDisbandGroup() {
            $A.modalConfirm({
                content: `你确定要解散【${this.dialogData.name}】群组吗？`,
                loading: true,
                okText: '解散',
                onOk: () => {
                    return new Promise((resolve, reject) => {
                        this.$store.dispatch("call", {
                            url: 'dialog/group/disband',
                            data: {
                                dialog_id: this.dialogId,
                            }
                        }).then(({msg}) => {
                            resolve(msg);
                            this.$store.dispatch("forgetDialog", {id: this.dialogId});
                        }).catch(({msg}) => {
                            reject(msg);
                        });
                    })
                },
            });
        },

        onExitGroup() {
            $A.modalConfirm({
                content: "你确定要退出群组吗？",
                loading: true,
                onOk: () => {
                    return new Promise((resolve, reject) => {
                        this.$store.dispatch("call", {
                            url: 'dialog/group/deluser',
                            data: {
                                dialog_id: this.dialogId,
                            }
                        }).then(({msg}) => {
                            resolve(msg);
                            this.$store.dispatch("forgetDialog", {id: this.dialogId});
                        }).catch(({msg}) => {
                            reject(msg);
                        });
                    })
                },
            });
        },

        onModify() {
            if (this.modifyData.userid) {
                // 个人头像（机器人）
                this.modifyLoad++;
                this.$store.dispatch("editUserBot", {
                    id: this.modifyData.userid,
                    avatar: this.modifyData.avatar,
                    name: this.modifyData.name,
                    clear_day: this.modifyData.clear_day,
                    webhook_url: this.modifyData.webhook_url,
                    webhook_events: this.normalizeWebhookEvents(this.webhookEvents),
                    dialog_id: this.modifyData.dialog_id
                }).then(({msg}) => {
                    $A.messageSuccess(msg);
                    this.modifyShow = false;
                    this.modifyData = {};
                }).catch(({msg}) => {
                    $A.modalError(msg);
                }).finally(_ => {
                    this.modifyLoad--;
                });
            } else {
                // 群组头像
                this.modifyLoad++;
                this.$store.dispatch("call", {
                    url: 'dialog/group/edit',
                    data: this.modifyData
                }).then(({data, msg}) => {
                    $A.messageSuccess(msg);
                    this.$store.dispatch("saveDialog", data);
                    this.modifyShow = false;
                    this.modifyData = {};
                }).catch(({msg}) => {
                    $A.modalError(msg);
                }).finally(_ => {
                    this.modifyLoad--;
                });
            }
        },

        onAiModify() {
            this.modifyLoad++;
            this.$store.dispatch("call", {
                url: 'dialog/config/save',
                data: this.modifyData,
                method: 'post'
            }).then(({data, msg}) => {
                $A.messageSuccess(msg);
                this.$store.dispatch("saveDialog", data);
                this.modifyAiShow = false;
                this.modifyData = {};
            }).catch(({msg}) => {
                $A.modalError(msg);
            }).finally(_ => {
                this.modifyLoad--;
            });
        },

        onForward(forwardData) {
            return new Promise((resolve, reject) => {
                this.$store.dispatch("call", {
                    url: 'dialog/msg/forward',
                    data: {
                        dialogids: forwardData.dialogids,
                        userids: forwardData.userids,
                        msg_id: forwardData.msg_id,
                        show_source: forwardData.sender ? 1 : 0,
                        leave_message: forwardData.message
                    }
                }).then(({data, msg}) => {
                    this.$store.dispatch("saveDialogMsg", data.msgs);
                    this.$store.dispatch("updateDialogLastMsg", data.msgs);
                    $A.messageSuccess(msg);
                    resolve()
                }).catch(({msg}) => {
                    $A.modalError(msg);
                    reject()
                });
            });
        },

        onActivity(activity) {
            if (this.msgActivity === false) {
                if (activity) {
                    this.msgActivity = 1
                }
                return
            }
            if (activity) {
                this.msgActivity++
            } else {
                this.msgActivity--
            }
        },

        onScroll({target}) {
            this.onThrottleScroll(target)
            if (this.operateVisible) {
                this.onUpdateOperate(target.querySelector(`[data-id="${this.operateItem.id}"]`)?.querySelector(".dialog-head"))
            }
            //
            const {offset, tail} = this.scrollInfo();
            this.scrollOffset = offset;
            this.scrollTail = tail;
            if (tail <= 10) {
                this.msgNew = 0;
                this.scrollToBottomRefresh && this.onReGetMsg()
            }
            //
            this.scrollAction = target.scrollTop;
            this.scrollDirection = this.scrollTmp <= this.scrollAction ? 'down' : 'up';
            setTimeout(_ => this.scrollTmp = this.scrollAction, 0);
            //
            this.scrollIng++;
            setTimeout(_=> this.scrollIng--, 100);
        },

        onThrottleScroll: throttle(function (target) {
            if (this.operatePreventScroll === 0 && this.operateVisible) {
                this.operateVisible = !!this.getSelectedTextInElement(target) && !target?.querySelector(`[unique="${this.operateItem.id}"]`)?.classList.contains('item-leave')
            }
        }, 100),

        onRange(range) {
            if (this.preventRangeLoad > 0) {
                return
            }
            const key = this.scrollDirection === 'down' ? 'next_id' : 'prev_id';
            for (let i = range.start; i <= range.end; i++) {
                if (!this.allMsgs[i]) {
                    continue
                }
                const rangeValue = this.allMsgs[i][key]
                if (!rangeValue) {
                    continue
                }
                const nearMsg = this.allMsgs[i + (key === 'next_id' ? 1 : -1)]
                if (nearMsg && nearMsg.id != rangeValue) {
                    this.preventRangeLoad++
                    this.getMsgs({
                        dialog_id: this.dialogId,
                        msg_id: this.msgId,
                        msg_type: this.msgType,
                        [key]: rangeValue,
                    }).finally(_ => {
                        this.preventRangeLoad--
                    })
                }
            }
        },

        onVisible(v) {
            this.startMsgId = $A.runNum(v.length ? v[Math.min(1, v.length - 1)] : 0)
        },

        onBack() {
            if (!this.beforeBack) {
                return this.handleBack();
            }
            const before = this.beforeBack();
            if (before && before.then) {
                before.then(() => {
                    this.handleBack();
                });
            } else {
                this.handleBack();
            }
        },

        handleBack() {
            if ($A.isSubElectron) {
                window.close()
                return
            }
            const {name, params} = this.$store.state.routeHistoryLast;
            if (name === this.routeName && /^\d+$/.test(params.dialogId)) {
                this.goForward({name: this.routeName});
            } else {
                this.goBack();
            }
        },

        handleLongpress(event) {
            const {type, data, element} = this.longpressData;
            this.$store.commit("longpress/clear")
            //
            switch (type) {
                // 长按触发提及
                case "mention":
                    const user = this.cacheUserBasic.find(({userid}) => userid == data.userid);
                    if (user) {
                        this.$refs.input?.addMention({
                            denotationChar: "@",
                            id: user.userid,
                            value: user.nickname,
                        })
                    }
                    break;

                // 长按触发消息操作
                case "operateMsg":
                    this.operateVisible = $A.isJson(data) && this.operateItem.id === data.id;
                    this.operateItem = $A.isJson(data) ? data : {};
                    this.operateCopys = []
                    if (event.target.nodeName === 'IMG') {
                        if (this.$Electron) {
                            this.operateCopys.push({
                                type: 'image',
                                icon: '&#xe7cd;',
                                label: '复制图片',
                                value: $A.thumbRestore(event.target.currentSrc),
                            })
                        }
                        if (data.type !== 'file' && !isLocalHost(event.target.currentSrc)) {
                            this.operateCopys.push({
                                type: 'imagedown',
                                icon: '&#xe7a8;',
                                label: '下载图片',
                                value: $A.thumbRestore(event.target.currentSrc),
                            })
                        }
                    } else if (event.target.nodeName === 'A') {
                        if (event.target.classList.contains("mention") && event.target.classList.contains("file")) {
                            this.findOperateFile(this.operateItem.id, event.target.href)
                        }
                        this.operateCopys.push({
                            type: 'link',
                            icon: '&#xe7cb;',
                            label: '复制链接',
                            value: event.target.href,
                        })
                    }
                    this.operateCopys.push({
                        type: 'selected',
                        icon: '&#xe7df;',
                        label: '复制选择',
                        value: '',
                        visible: false,
                    })
                    if (data.type === 'text') {
                        if (data.msg.text.replace(/<[^>]+>/g,"").length > 0) {
                            this.operateCopys.push({
                                type: 'text',
                                icon: '&#xe77f;',
                                label: null,
                                title: this.operateCopys.length > 1 ? '复制文本' : '复制',
                                value: '',
                            })
                        }
                        if (data.msg.type === 'md') {
                            this.operateCopys.push({
                                type: 'md',
                                icon: '&#xe77f;',
                                label: '复制原文',
                                value: '',
                            })
                        }
                    }
                    if (this.operateVisible) {
                        this.checkMessageFavoriteStatus(this.operateItem);
                    }
                    requestAnimationFrame(() => {
                        this.operateItem.clientX = event.clientX
                        this.operateItem.clientY = event.clientY
                        this.onSelectionchange()
                        this.onUpdateOperate(element)
                    })
                    break;
            }
        },

        onMsgType(type) {
            switch (type) {
                case 'project':
                    this.openProject()
                    break;

                case 'task':
                    this.openTask()
                    break;

                case 'okr':
                    this.openOkrDetails(this.dialogData.link_id)
                    break;

                default:
                    if (this.loadMsg) {
                        $A.messageWarning("正在加载，请稍后再试...")
                    } else {
                        this.msgType = type
                    }
                    break;
            }
        },

        onSelectionchange() {
            if (!this.operateVisible) {
                return
            }
            const selectedItem = this.operateCopys.find(({type}) => type === 'selected')
            if (!selectedItem) {
                return;
            }
            const selectText = this.getSelectedTextInElement(this.$refs.scroller.$el.querySelector(`[data-id="${this.operateItem.id}"]`))
            selectedItem.value = selectText
            selectedItem.visible = selectText.length > 0
            //
            const textItem = this.operateCopys.find(({type}) => type === 'text');
            if (!textItem) {
                return;
            }
            textItem.label = selectText.length > 0 ? '复制全部' : null
        },

        onUpdateOperate(el) {
            if (!el) {
                return
            }
            //
            const rect = el.getBoundingClientRect(),
                scrollerRect = this.$refs.scroller.$el.getBoundingClientRect();
            const operatePosition = {
                left: this.operateItem.clientX,
                top: rect.top,
                height: rect.height
            }
            if (rect.top < scrollerRect.top) {
                operatePosition.top = scrollerRect.top
                operatePosition.height -= scrollerRect.top - rect.top
            }
            if (rect.bottom > scrollerRect.bottom) {
                operatePosition.height -= rect.bottom - scrollerRect.bottom
            }
            if (this.windowWidth < 500) {
                if (this.operateItem.created_at) {
                    operatePosition.left = this.windowWidth / 2
                } else {
                    operatePosition.left = rect.left + (rect.width / 2)
                }
            }
            this.operateStyles = {
                left: `${operatePosition.left}px`,
                top: `${operatePosition.top}px`,
                height: `${operatePosition.height}px`,
            }
            this.operateClient = {
                x: operatePosition.left,
                y: this.operateItem.clientY
            };
            if (this.operateVisible) {
                this.handleOperateResize()
            } else {
                this.operateVisible = true;
            }
        },

        handleOperateResize() {
            if (this.operateVisible) {
                try {
                    this.$refs.operate.$refs.drop.popper.update()
                } catch (e) {}
            }
        },

        onOperate(action, value = null) {
            this.operateVisible = false;
            this.$nextTick(_ => {
                switch (action) {
                    case "cancel":
                        this.onCancelSend()
                        break;

                    case "reply":
                        this.onReply()
                        break;

                    case "update":
                        this.onUpdate()
                        break;

                    case "voice2text":
                        this.onVoice2text()
                        break;

                    case "translation":
                        this.onTranslation()
                        break;

                    case "copy":
                        this.onCopy(value)
                        break;

                    case "forward":
                        this.$refs.forwarder.onSelection()
                        break;

                    case "withdraw":
                        this.onWithdraw()
                        break;

                    case "view":
                        this.onViewFile()
                        break;

                    case "down":
                        this.onDownFile()
                        break;

                    case "tag":
                        this.onTag()
                        break;

                    case "favorite":
                        this.onFavorite()
                        break;

                    case "newTask":
                        let content = $A.formatMsgBasic(this.operateItem.msg.text)
                        content = content.replace(/<img[^>]*?src=(["'])([^"']+?)(_thumb\.(png|jpg|jpeg))?\1[^>]*?>/g, `<img src="$2">`)
                        content = content.replace(/<li\s+data-list="checked">/g, `<li class="tox-checklist--checked">`)
                        content = content.replace(/<li\s+data-list="unchecked">/g, `<li>`)
                        content = content.replace(/<ol[^>]*>([\s\S]*?)<\/ol>/g, `<ul class="tox-checklist">$1</ul>`)
                        emitter.emit('addTask', {owner: [this.userId], content});
                        break;

                    case "todo":
                        this.onTodo()
                        break;

                    case "pos":
                        this.onPositionId(this.operateItem.id)
                        break;

                    case "emoji":
                        if (value === 'more') {
                            choiceEmojiOne().then(this.onEmoji)
                        } else {
                            this.onEmoji(value)
                        }
                        break;

                    case "top":
                        this.onTopOperate()
                        break;
                }
            })
        },

        onCancelSend() {
            $A.modalConfirm({
                title: '取消发送',
                content: '你确定要取消发送吗？',
                loading: true,
                onOk: () => {
                    return new Promise(async (resolve, reject) => {
                        if (this.operateItem.created_at) {
                            reject("消息已发送，不可取消");
                            return
                        }
                        if (this.operateItem.type === "file") {
                            // 取消文件上传
                            const {file_uid, file_method} = this.operateItem
                            if (file_method === "photo") {
                                try {
                                    await $A.eeuiAppCancelUploadPhoto(file_uid)
                                } catch (e) {
                                    // 取消失败
                                }
                                this.forgetTempMsg(this.operateItem.id)
                                return resolve();
                            }
                            if (this.$refs.chatUpload.cancel(file_uid)) {
                                this.forgetTempMsg(this.operateItem.id)
                                return resolve();
                            }
                            reject("取消发送失败");
                        } else {
                            // 取消消息发送
                            this.$store.dispatch('callCancel', this.operateItem.id).finally(() => {
                                this.forgetTempMsg(this.operateItem.id)
                                resolve();
                            });
                        }
                    })
                }
            });
        },

        onReply(type) {
            this.replyMsgAutoMention = true
            this.setQuote(this.operateItem.id, type)
            this.inputFocus()
        },

        onUpdate() {
            const {type} = this.operateItem
            this.onReply(type === 'text' ? 'update' : 'reply')
            if (type === 'text') {
                let {text, type} = this.operateItem.msg
                this.$refs.input.setPasteMode(false)
                if (type === 'md') {
                    this.$refs.input.setText(text)
                } else {
                    if (text.indexOf("mention") > -1) {
                        text = text.replace(
                            /<a class="mention ([^'"]*)" href="([^'"]*)"[^>]*>([~%])([^>]*)<\/a>/g,
                            '<span class="mention" data-denotation-char="$3" data-id="$2" data-value="$4">&#xFEFF;<span contenteditable="false"><span class="ql-mention-denotation-char">$3</span>$4</span>&#xFEFF;</span>'
                        )
                        text = text.replace(
                            /<span class="mention ([^'"]*)" data-id="(\d+)">([@#])([^>]*)<\/span>/g,
                            '<span class="mention" data-denotation-char="$3" data-id="$2" data-value="$4">&#xFEFF;<span contenteditable="false"><span class="ql-mention-denotation-char">$3</span>$4</span>&#xFEFF;</span>'
                        )
                    }
                    text = text.replace(/<img[^>]*>/gi, match => {
                        return match.replace(/(width|height)="\d+"\s*/ig, "");
                    })
                    text = text.replace(/<p><\/p>/g, '<p><br/></p>')
                    this.msgText = $A.formatMsgBasic(text)
                }
                this.$nextTick(_ => this.$refs.input.setPasteMode(true))
            }
        },

        onVoice2text() {
            if (!this.actionPermission(this.operateItem, 'voice2text')) {
                return;
            }
            const {id: msg_id} = this.operateItem
            if (this.isLoad(`msg-${msg_id}`)) {
                return;
            }
            this.$store.dispatch("setLoad", `msg-${msg_id}`)
            this.$store.dispatch("call", {
                url: 'dialog/msg/voice2text',
                data: {
                    msg_id
                },
            }).then(({data}) => {
                this.$store.dispatch("saveDialogMsg", data);
            }).catch(({msg}) => {
                $A.messageError(msg);
            }).finally(_ => {
                this.$store.dispatch("cancelLoad", `msg-${msg_id}`)
            });
        },

        onTranslation(language = undefined) {
            if (!this.actionPermission(this.operateItem, 'translation')) {
                return;
            }
            const {id: msg_id} = this.operateItem
            const key = `msg-${msg_id}`
            if (this.isLoad(key)) {
                return;
            }
            let force = 0;
            if (language === 'hidden') {
                this.$store.dispatch("removeTranslation", key);
                return;
            } else if (language === 'retranslation') {
                this.$store.dispatch("removeTranslation", key);
                language = undefined;
                force = 1;
            }
            this.$store.dispatch("setLoad", key)
            this.$store.dispatch("call", {
                url: 'dialog/msg/translation',
                data: {
                    msg_id,
                    force,
                    language: language || this.cacheTranslationLanguage
                },
            }).then(({data}) => {
                this.$store.dispatch("saveTranslation", Object.assign(data, {key}));
            }).catch(({msg}) => {
                $A.messageError(msg);
            }).finally(_ => {
                this.$store.dispatch("cancelLoad", key)
            });
        },

        openTranslationMenu(event) {
            const list = Object.keys(languageList).map(item => ({
                label: languageList[item],
                value: item
            }))
            list.push(...[
                {label: this.$L('重新翻译'), value: 'retranslation', divided: true},
                {label: this.$L('隐藏翻译'), value: 'hidden'},
            ])
            this.$store.commit('menu/operation', {
                event,
                list,
                active: this.cacheTranslationLanguage,
                language: false,
                onUpdate: async (language) => {
                    if (languageList[language]) {
                        await this.$store.dispatch("setTranslationLanguage", language);
                    }
                    this.onTranslation(language);
                }
            })
        },

        onCopy(data) {
            if (!$A.isJson(data)) {
                return
            }
            const {type, value} = data
            switch (type) {
                case 'image':
                    if (this.$Electron) {
                        $A.generateBase64Image(value).then(base64 => {
                            this.$Electron.sendMessage('copyBase64Image', {base64});
                        })
                    }
                    break;

                case 'imagedown':
                    if (this.$Electron) {
                        this.$Electron.sendMessage('saveImageAt', {
                            params: { },
                            url: value,
                        })
                    } else {
                        this.$store.dispatch('downUrl', {
                            url: value,
                            token: false
                        })
                    }
                    break;

                case 'filepos':
                    this.$store.dispatch("filePos", value);
                    break;

                case 'filedown':
                    this.$store.dispatch('downUrl', $A.apiUrl(`file/content?id=${value.shakeId}&down=yes`))
                    break;

                case 'link':
                    this.copyText(value);
                    break;

                case 'selected':
                    this.copyText(value);
                    break;

                case 'text':
                    const copyEl = this.$refs.scroller.$el.querySelector(`[data-id="${this.operateItem.id}"]`)?.querySelector(".dialog-content")
                    if (copyEl) {
                        let copyText = copyEl.innerText;
                        if ($A.getObject(this.operateItem.msg, 'type') !== 'md') {
                            copyText = copyText.replace(/\n\n/g, "\n").replace(/(^\s*)|(\s*$)/g, "")
                        }
                        this.copyText(copyText)
                    } else {
                        $A.messageWarning('不可复制的内容');
                    }
                    break;

                case 'md':
                    this.copyText(this.operateItem.msg.text)
                    break;
            }
        },

        onWithdraw() {
            $A.modalConfirm({
                content: `确定撤回此信息吗？`,
                okText: '撤回',
                loading: true,
                onOk: () => {
                    return new Promise((resolve, reject) => {
                        this.$store.dispatch("call", {
                            url: 'dialog/msg/withdraw',
                            data: {
                                msg_id: this.operateItem.id
                            },
                        }).then(() => {
                            resolve("消息已撤回");
                            this.$store.dispatch("forgetDialogMsg", this.operateItem);
                        }).catch(({msg}) => {
                            reject(msg);
                        });
                    })
                }
            });
        },

        onViewReply(data) {
            if (this.operateVisible) {
                return
            }
            this.onPositionId(data.reply_id, data.msg_id)
        },

        onViewText(event, el) {
            if (this.operateVisible) {
                return
            }
            const {target, clientX} = event

            // 快速输入
            if (target.classList.contains('mark-set')) {
                !this.windowTouch && this.$refs.input.focus()
                this.$refs.input.setText(target.innerText)
                return
            }
            if (target.classList.contains('mark-insert')) {
                this.$refs.input.insertText(target.innerText)
                return
            }

            // 点击切换翻译
            if (target.classList.contains('translation-label')) {
                this.operateItem = this.findMsgByElement(el)
                this.openTranslationMenu(event)
                return
            }

            // 打开微应用 / 审批详情
            let clickElement = target;
            while (clickElement) {
                if (!clickElement.classList) {
                    break;
                }
                if (clickElement.classList.contains('dialog-head')) {
                    break;
                }
                if (clickElement.classList.contains('open-micro-app')) {
                    this.handleOpenMicroApp(clickElement);
                    return;
                }
                if (clickElement.classList.contains('open-approve-details')) {
                    emitter.emit('approveDetails', clickElement.getAttribute("data-id"));
                    return;
                }
                clickElement = clickElement.parentElement;
            }

            switch (target.nodeName) {
                // 打开图片
                case "IMG":
                    if (!(target.classList.contains('browse') && this.onViewPicture(target.currentSrc))) {
                        const list = $A.getTextImagesInfo(el.outerHTML)
                        this.$store.dispatch("previewImage", {index: target.currentSrc, list})
                    }
                    break;

                // 打开任务、打开OKR
                case "SPAN":
                    if (target.classList.contains('mention') && target.classList.contains('task')) {
                        this.$store.dispatch("openTask", $A.runNum(target.getAttribute("data-id")));
                    }
                    if (target.classList.contains('mention') && target.classList.contains('okr')) {
                        this.openOkrDetails($A.runNum(target.getAttribute("data-id")));
                    }
                    break;

                // 更新待办列表
                case "LI":
                    const dataClass = target.getAttribute('data-list')
                    if (['checked', 'unchecked'].includes(dataClass)) {
                        if (clientX - target.getBoundingClientRect().x > 18) {
                            return;
                        }
                        const dataMsg = this.findMsgByElement(el)
                        if (dataMsg.userid != this.userId) {
                            return;
                        }
                        const dataIndex = [].indexOf.call(el.querySelectorAll(target.tagName), target);
                        if (dataClass === 'checked') {
                            target.setAttribute('data-list', 'unchecked')
                        } else {
                            target.setAttribute('data-list', 'checked')
                        }
                        this.$store.dispatch("setLoad", {
                            key: `msg-${dataMsg.id}`,
                            delay: 600
                        })
                        this.$store.dispatch("call", {
                            url: 'dialog/msg/checked',
                            data: {
                                dialog_id: this.dialogId,
                                msg_id: dataMsg.id,
                                index: dataIndex,
                                checked: dataClass === 'checked' ? 0 : 1
                            },
                        }).then(({data}) => {
                            this.$store.dispatch("saveDialogMsg", data);
                        }).catch(({msg}) => {
                            if (dataClass === 'checked') {
                                target.setAttribute('data-list', 'checked')
                            } else {
                                target.setAttribute('data-list', 'unchecked')
                            }
                            $A.modalError(msg)
                        }).finally(_ => {
                            this.$store.dispatch("cancelLoad", `msg-${dataMsg.id}`)
                        });
                    }
                    break;
            }
        },

        findMsgByElement(el) {
            let element = el.parentElement;
            while (element) {
                if (element.classList.contains('dialog-scroller')) {
                    break;
                }
                if (element.classList.contains('dialog-view')) {
                    const dataId = element.getAttribute("data-id")
                    return this.allMsgs.find(item => item.id == dataId) || {}
                }
                element = element.parentElement;
            }
            return {};
        },

        onViewFile(data) {
            if (this.operateVisible) {
                return
            }
            if (!$A.isJson(data)) {
                data = this.operateItem
            }
            const {msg} = data;
            if (msg.ext === 'mp4') {
                this.$store.dispatch("previewImage", {
                    index: 0,
                    list: [{
                        src: msg.path,
                        width: msg.width,
                        height: msg.height,
                    }]
                })
                return
            }
            if (['jpg', 'jpeg', 'webp', 'gif', 'png'].includes(msg.ext)) {
                this.onViewPicture(msg.path);
                return
            }
            const path = `/single/file/msg/${data.id}`;
            const title = data.type === 'longtext' ? this.$L('消息详情') : (`${msg.name} (${$A.bytesToSize(msg.size)})`);
            if (this.$Electron) {
                this.$store.dispatch('openWindow', {
                    name: `file-msg-${data.id}`,
                    path: path,
                    title,
                    titleFixed: true,
                });
            } else if (this.$isEEUIApp) {
                this.$store.dispatch('openAppChildPage', {
                    pageType: 'app',
                    pageTitle: title,
                    url: 'web.js',
                    params: {
                        titleFixed: true,
                        url: $A.urlReplaceHash(path)
                    },
                })
            } else {
                window.open($A.mainUrl(path.substring(1)))
            }
        },

        onViewPicture(currentUrl) {
            const data = this.allMsgs.filter(item => {
                if (item.type === 'file') {
                    return ['jpg', 'jpeg', 'webp', 'gif', 'png'].includes(item.msg.ext);
                } else if (item.type === 'text') {
                    return item.msg.text.match(/<img\s+class="browse"[^>]*?>/);
                }
                return false;
            });
            //
            const list = [];
            data.some(({type, msg}) => {
                if (type === 'file') {
                    list.push({
                        src: msg.path,
                        width: msg.width,
                        height: msg.height,
                    })
                } else if (type === 'text') {
                    list.push(...$A.getTextImagesInfo(msg.text))
                }
            })
            //
            const current = $A.thumbRestore(currentUrl)
            if (!list.find(item => $A.thumbRestore(item.src) == current)) {
                list.unshift({
                    src: currentUrl,
                    width: 0,
                    height: 0,
                })
            }
            //
            this.$store.dispatch("previewImage", {index: currentUrl, list})
            return true
        },

        onDownFile(data) {
            if (this.operateVisible) {
                return
            }
            if (!$A.isJson(data)) {
                data = this.operateItem
            }
            if (data.type === 'longtext') {
                this.onViewFile(data)
                return;
            }
            $A.modalConfirm({
                language: false,
                title: this.$L('下载文件'),
                okText: this.$L('立即下载'),
                content: `${data.msg.name} (${$A.bytesToSize(data.msg.size)})`,
                onOk: () => {
                    this.$store.dispatch('downUrl', $A.apiUrl(`dialog/msg/download?msg_id=${data.id}`))
                }
            });
        },

        onReplyList(data) {
            if (this.operateVisible) {
                return
            }
            this.replyListId = data.msg_id
            this.replyListShow = true
        },

        onError(data) {
            if (data.error !== true) {
                return
            }
            const {type, mType, content, msg} = data.errorData
            const config = {
                icon: 'error',
                title: '发送失败',
                content,
                cancelText: '取消发送',
                onCancel: _ => {
                    this.forgetTempMsg(data.id)
                }
            }
            if (type === 'text') {
                config.okText = '重新发送'
                config.onOk = () => {
                    this.forgetTempMsg(data.id)
                    this.sendMsg(msg, mType)
                }
            } else if (type === 'record') {
                config.okText = '重新发送'
                config.onOk = () => {
                    this.forgetTempMsg(data.id)
                    this.sendRecord(msg)
                }
            } else {
                return
            }
            $A.modalConfirm(config)
        },

        onEmoji(data) {
            if (!$A.isJson(data)) {
                data = {
                    msg_id: this.operateItem.id,
                    symbol: data,
                }
            }
            //
            const cacheEmojis = this.cacheEmojis.filter(item => item !== data.symbol);
            cacheEmojis.unshift(data.symbol)
            $A.IDBSave("cacheEmojis", this.$store.state.cacheEmojis = cacheEmojis.slice(0, 3))
            //
            if (this.replyEmojiIngs[data.msg_id]) {
                $A.messageWarning("正在处理，请稍后再试...");
                return
            }
            this.replyEmojiIngs[data.msg_id] = true
            //
            this.$store.dispatch("setLoad", {
                key: `msg-${data.msg_id}`,
                delay: 600
            })
            this.$store.dispatch("call", {
                url: 'dialog/msg/emoji',
                data,
            }).then(({data: resData}) => {
                const index = this.dialogMsgs.findIndex(item => item.id == resData.id)
                if (index > -1) {
                    this.$store.dispatch("saveDialogMsg", resData);
                } else if (this.todoViewData.id === resData.id) {
                    this.todoViewData = Object.assign(this.todoViewData, resData)
                }
            }).catch(({msg}) => {
                $A.messageError(msg);
            }).finally(_ => {
                this.replyEmojiIngs[data.msg_id] = false
                this.$store.dispatch("cancelLoad", `msg-${data.msg_id}`)
            });
        },

        onShowEmojiUser(data) {
            if (this.operateVisible) {
                return
            }
            this.respondData = data
            this.respondShow = true
        },

        onOther({event, data}) {
            if (this.operateVisible) {
                return
            }
            if (event === 'todoAdd') {
                this.todoSpecifyData = Object.assign(this.todoSpecifyData, data)
                this.todoSpecifyShow = true
                this.$nextTick(_ => {
                    this.$refs.todoSpecifySelect.onSelection()
                })
            }
        },

        onTag() {
            if (this.operateVisible) {
                return
            }
            const data = {
                msg_id: this.operateItem.id,
            }
            //
            this.$store.dispatch("setLoad", {
                key: `msg-${data.msg_id}`,
                delay: 600
            })
            this.$store.dispatch("call", {
                url: 'dialog/msg/tag',
                data,
            }).then(({data}) => {
                this.tagOrTodoSuccess(data)
            }).catch(({msg}) => {
                $A.messageError(msg);
            }).finally(_ => {
                this.$store.dispatch("cancelLoad", `msg-${data.msg_id}`)
            });
        },

        onFavorite() {
            if (this.operateVisible) {
                return
            }

            this.$store.dispatch("toggleFavorite", {
                type: 'message',
                id: this.operateItem.id
            }).then(({data}) => {
                this.$set(this.operateItem, 'favorited', data.favorited);
                const message = this.dialogMsgs.find(msg => msg.id === this.operateItem.id);
                if (message) {
                    this.$set(message, 'favorited', data.favorited);
                }
            });
        },

        checkMessageFavoriteStatus(message) {
            if (!message.id) return;

            this.$store.dispatch("checkFavoriteStatus", {
                type: 'message',
                id: message.id
            }).then(({data}) => {
                this.$set(this.operateItem, 'favorited', data.favorited || false);
                const msgInList = this.dialogMsgs.find(msg => msg.id === message.id);
                if (msgInList) {
                    this.$set(msgInList, 'favorited', data.favorited || false);
                }
            }).catch(() => {
                this.$set(this.operateItem, 'favorited', false);
                const msgInList = this.dialogMsgs.find(msg => msg.id === message.id);
                if (msgInList) {
                    this.$set(msgInList, 'favorited', false);
                }
            });
        },

        onTypeChange(val) {
            if (val === 'user') {
                if (this.todoSettingData.userids.length === 0 && this.todoSettingData.quick_value.length > 0) {
                    this.todoSettingData.userids = this.todoSettingData.quick_value
                }
                this.$nextTick(_ => {
                    this.$refs.userSelect.onSelection()
                })
            }
            if (val !== 'quick_select') {
                this.todoSettingData.quick_value = []
            }
        },

        onQuickChange(val) {
            this.todoSettingData.type = val.length === 0 ? 'all' : 'quick_select';
        },

        onTodo(type) {
            if (this.operateVisible) {
                return
            }
            if (type === 'submit') {
                const todoData = $A.cloneJSON(this.todoSettingData)
                if (todoData.type === 'quick_select') {
                    todoData.type = 'user'
                    todoData.userids = todoData.quick_value
                } else if (todoData.type === 'user' && $A.arrayLength(todoData.userids) === 0) {
                    $A.messageWarning("选择指定成员");
                    return
                }
                this.todoSettingLoad++
                this.onTodoSubmit(todoData).then(msg => {
                    $A.messageSuccess(msg)
                    this.todoSettingShow = false
                }).catch(e => {
                    $A.messageError(e)
                }).finally(_ => {
                    this.todoSettingLoad--
                })
            } else {
                if (this.operateItem.todo) {
                    $A.modalConfirm({
                        content: "你确定取消待办吗？",
                        cancelText: '取消',
                        okText: '确定',
                        loading: true,
                        onOk: () => this.onTodoSubmit({
                            type: 'user',
                            userids: [],
                            msg_id: this.operateItem.id,
                        })
                    });
                } else {
                    const quickList = {}
                    quickList[this.userId] = this.userId
                    const userid = this.dialogData.dialog_user?.userid
                    if (userid && userid != this.userId && !this.dialogData.bot) {
                        quickList[userid] = userid
                    }
                    if (this.operateItem.type === 'text') {
                        const atReg = /<span class="mention user" data-id="(\d+)">([^<]+)<\/span>/g
                        const atList = this.operateItem.msg.text.match(atReg)
                        if (atList) {
                            atList.forEach(item => {
                                const userid = parseInt(item.replace(atReg, '$1'))
                                if (userid && userid != this.userId) {
                                    quickList[userid] = userid
                                }
                            })
                        }
                    }
                    this.todoSettingData = {
                        type: 'all',
                        userids: [],
                        msg_id: this.operateItem.id,
                        quick_value: [],
                        quick_list: Object.values(quickList),
                    }
                    this.todoSettingShow = true
                }
            }
        },

        onTodoSpecify() {
            return new Promise((resolve, reject) => {
                this.onTodoSubmit(this.todoSpecifyData).then(msg => {
                    $A.messageSuccess(msg)
                    resolve()
                }).catch(e => {
                    $A.messageError(e)
                    reject()
                })
            });
        },

        onTodoSubmit(data) {
            return new Promise((resolve, reject) => {
                this.$store.dispatch("setLoad", {
                    key: `msg-${data.msg_id}`,
                    delay: 600
                })
                this.$store.dispatch("call", {
                    method: 'post',
                    url: 'dialog/msg/todo',
                    data,
                }).then(({data, msg}) => {
                    resolve(msg)
                    this.tagOrTodoSuccess(data)
                    this.onActive()
                }).catch(({msg}) => {
                    reject(msg);
                }).finally(_ => {
                    this.$store.dispatch("cancelLoad", `msg-${data.msg_id}`)
                });
            })
        },

        tagOrTodoSuccess(data) {
            this.$store.dispatch("saveDialogMsg", data.update);
            if (data.add) {
                this.$store.dispatch("saveDialogMsg", data.add);
                this.$store.dispatch("updateDialogLastMsg", data.add);
            }
        },

        onSearchSwitch(type) {
            if (this.searchResult.length === 0) {
                return
            }
            if (this.searchLocation === 1 && this.searchResult.length === 1) {
                this.onPositionId(this.searchResult[0])
                return
            }
            if (type === 'prev') {
                if (this.searchLocation <= 1) {
                    this.searchLocation = this.searchResult.length
                } else {
                    this.searchLocation--
                }
            } else {
                if (this.searchLocation >= this.searchResult.length) {
                    this.searchLocation = 1
                } else {
                    this.searchLocation++
                }
            }
        },

        onSearchKeyup(e) {
            if (e === null || e.keyCode === 27) {
                this.searchShow = false
                this.searchKey = ''
                this.searchResult = []
            }
        },

        onPositionMark(id) {
            if (this.positionLoad > 0) {
                return;
            }
            this.positionLoad++
            //
            this.onPositionId(id).finally(_ => {
                this.positionLoad--
            })
        },

        actionPermission(item, permission) {
            switch (permission) {
                case 'forward':
                    if (['word-chain', 'vote', 'template'].includes(item.type)) {
                        return false    // 投票、接龙、模板消息 不支持转发
                    }
                    break;

                case 'newTask':
                    return item.type === 'text' // 只有 文本消息 才支持新建任务

                case 'voice2text':
                    if (item.type !== 'record') {
                        return false;
                    }
                    if (item.msg.text) {
                        return false;
                    }
                    break;

                case 'translation':
                    return ['text', 'record'].includes(item.type) && item.msg.text // 文本、语音消息 支持翻译
            }
            return true // 返回 true 允许操作
        },

        findOperateFile(msgId, link) {
            const file = this.fileLinks.find(item => item.link === link)
            if (file) {
                this.addFileMenu(msgId, file)
                return
            }
            this.$store.dispatch("searchFiles", {
                link
            }).then(({data}) => {
                if (data.length === 1) {
                    const file = {
                        link,
                        id: data[0].id,
                        pid: data[0].pid,
                    }
                    this.fileLinks.push(file)
                    this.addFileMenu(msgId, file)
                }
            }).catch(_ => {})
        },

        addFileMenu(msgId, data) {
            if (this.operateItem.id != msgId) {
                return
            }
            if (this.operateCopys.findIndex(item => item.type === 'filepos') !== -1) {
                return
            }
            const index = Math.max(0, this.operateCopys.findIndex(item => item.type === 'link') - 1)
            this.operateCopys.splice(index, 0, {
                type: 'filepos',
                icon: '&#xe6f3;',
                label: '显示文件',
                value: {
                    folderId: data.pid,
                    fileId: null,
                    shakeId: data.id
                },
            }, {
                type: 'filedown',
                icon: '&#xe7a8;',
                label: '下载',
                value: {
                    folderId: data.pid,
                    fileId: null,
                    shakeId: data.id
                },
            })
        },

        getSelectedTextInElement(element) {
            const selection = document.getSelection();
            if (selection.rangeCount > 0) {
                const range = selection.getRangeAt(0);
                if (element.contains(range.commonAncestorContainer)) {
                    return range.toString();
                }
            }
            return "";
        },

        onViewDetail(e) {
            if (this.dialogData.type == 'group') {
                let src = null
                if (e.target.tagName === "IMG") {
                    src = e.target.src
                } else {
                    src = $A(e.target).find("img").attr("src")
                }
                if (src) {
                    this.$store.dispatch("previewImage", src)
                }
                return;
            }
            emitter.emit("openUser", this.dialogData.dialog_user?.userid)
        },

        onTopOperate() {
            if (this.operateVisible) {
                return
            }
            if (this.operateItem.top_at) {
                this.onCancelTop(this.operateItem)
            } else {
                this.onTopSubmit(this.operateItem)
            }
        },

        onTopSubmit(data) {
            return new Promise((resolve, reject) => {
                this.$store.dispatch("setLoad", {
                    key: `msg-${data.msg_id}`,
                    delay: 600
                })
                this.$store.dispatch("call", {
                    url: 'dialog/msg/top',
                    data: {
                        msg_id: data.id
                    },
                }).then(({ data, msg }) => {
                    resolve(msg)
                    // 取消置顶
                    this.$store.dispatch("saveDialog", {
                        'id' : this.dialogId,
                        'top_msg_id' : data.update?.top_msg_id || 0,
                        'top_userid' : data.update?.top_userid || 0
                    });
                    // 置顶
                    if (data.update?.top_msg_id) {
                        const index = this.dialogMsgs.findIndex(({ id }) => id == data.update.top_msg_id);
                        if (index > -1) {
                            this.$store.dispatch("saveDialogMsgTop", Object.assign({}, this.dialogMsgs[index]))
                        }
                    }
                    // 添加消息
                    if (data.add) {
                        this.$store.dispatch("saveDialogMsg", data.add);
                        this.$store.dispatch("updateDialogLastMsg", data.add);
                        this.onActive();
                    }
                }).catch(({ msg }) => {
                    reject(msg);
                }).finally(_ => {
                    this.$store.dispatch("cancelLoad", `msg-${data.msg_id}`)
                });
            })
        },

        onPosTop() {
            if (!this.topMsg) {
                return
            }
            this.topPosLoad++
            this.onPositionId(this.topMsg.id).finally(_ => {
                this.topPosLoad--
            })
        },

        onCancelTop(info) {
            $A.modalConfirm({
                content: "你确定取消置顶吗？",
                cancelText: '取消',
                okText: '确定',
                loading: true,
                onOk: () => this.onTopSubmit(info)
            });
        },

        async getUserApproveStatus() {
            this.approvaUserStatus = ''
            if (this.dialogData.type !== 'user' || this.dialogData.bot) {
                return
            }
            const isInstalled = await  this.$store.dispatch("isMicroAppInstalled", 'approve');
            if (!isInstalled) {
                return
            }
            this.$store.dispatch("call", {
                url: 'approve/user/status',
                data: {
                    userid: this.dialogData.dialog_user.userid,
                }
            }).then(({data}) => {
                this.approvaUserStatus = data;
            }).catch(({msg}) => {
                $A.messageError(msg);
            });
        },

        async shakeToMsgId(id) {
            try {
                const element = await $A.findElementWithRetry(() => this.$refs.scroller.$el.querySelector(`[data-id="${id}"]`)?.querySelector(".dialog-head"));
                $A.scrollIntoAndShake(element, false)
            } catch (e) {
                // console.log(e)
            }
        },

        autoScrollInto() {
            return this.location === "modal"
                && this.$isEEUIApp
                && this.windowPortrait
                && this.$refs.input?.isFocus
        },

        keepIntoInput() {
            if (!this.autoScrollInto()) {
                return;
            }
            this.keepIntoTimer && clearTimeout(this.keepIntoTimer)
            this.keepIntoTimer = setTimeout(_ => {
                if (!this.autoScrollInto()) {
                    return;
                }
                this.$store.dispatch("scrollBottom", this.$refs.footer)
            }, 500)
        },

        handlerMsgTransfer() {
            const {time, msgFile, msgRecord, msgText, sendType, dialogId} = this.dialogMsgTransfer || {}
            if (!/^\d+$/.test(time) || !/^\d+$/.test(dialogId)) {
                return
            }
            if (time < $A.dayjs().unix()) {
                return
            }
            if (dialogId != this.dialogId) {
                return
            }
            this.$store.state.dialogMsgTransfer.time = 0;
            this.$nextTick(() => {
                if ($A.isArray(msgFile) && msgFile.length > 0) {
                    this.sendFileMsg(msgFile);
                } else if ($A.isJson(msgRecord) && msgRecord.duration > 0) {
                    this.sendRecord(msgRecord);
                } else if (msgText) {
                    this.sendMsg(msgText, sendType);
                }
            });
        },

        handleOpenMicroApp(element) {
            const dataset = element && element.dataset ? element.dataset : {};
            const normalizeKey = key => {
                const name = key.replace(/^app/, '');
                return name.replace(/^[A-Z]/, m => m.toLowerCase()).replace(/([A-Z])/g, '_$1').toLowerCase();
            };
            let config = $A.jsonParse(dataset.appConfig);
            Object.entries(dataset).forEach(([key, value]) => {
                if (!key.startsWith('app') || key === 'appConfig') {
                    return;
                }
                if (value === '' || typeof value === 'undefined') {
                    return;
                }
                const normalizedKey = normalizeKey(key);
                if (normalizedKey === 'props') {
                    config.props = Object.assign(config.props || {}, $A.jsonParse(value));
                    return;
                }
                if (value === 'true' || value === 'false') {
                    config[normalizedKey] = /true/i.test(value);
                } else {
                    config[normalizedKey] = value;
                }
            });
            this.$store.dispatch("openMicroApp", config);
        },
    }
}
</script>
