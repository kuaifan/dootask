# 前端事件总线注册表

> **本文件由脚本自动生成，请勿手改。**
>
> - 生成命令: `node scripts/gen-events-map.mjs`
> - 扫描范围: `resources/assets/js` 下所有 `.js` / `.vue` 文件（共 268 个）
> - 事件总线: `resources/assets/js/store/events.js`（mitt 实例）
> - 仅匹配裸 `emitter.emit/on/off(` 调用；`xxx.emitter.emit(`（如 Quill 内部 emitter）不属于本总线，已排除

共 **29** 个静态可解析事件，**125** 处 `emitter.emit/on/off` 调用。

## 事件清单

### `addMeeting`

- **emit（10）**
  - `resources/assets/js/App.vue:420`
  - `resources/assets/js/pages/manage.vue:1236`
  - `resources/assets/js/pages/manage.vue:1243`
  - `resources/assets/js/pages/manage/application.vue:1188`
  - `resources/assets/js/pages/manage/application.vue:1194`
  - `resources/assets/js/pages/manage/components/ChatInput/index.vue:1882`
  - `resources/assets/js/pages/manage/components/DialogView/index.vue:621`
  - `resources/assets/js/pages/manage/components/DialogWrapper.vue:2017`
  - `resources/assets/js/pages/manage/components/DialogWrapper.vue:2025`
  - `resources/assets/js/pages/manage/messenger.vue:1219`
- **on（1）**
  - `resources/assets/js/pages/manage/components/MeetingManager/index.vue:187`
- **off（1）**
  - `resources/assets/js/pages/manage/components/MeetingManager/index.vue:191`

### `addTask`

- **emit（3）**
  - `resources/assets/js/pages/manage/calendar.vue:255`
  - `resources/assets/js/pages/manage/components/DialogWrapper.vue:3513`
  - `resources/assets/js/pages/manage/components/ProjectPanel.vue:1357`
- **on（1）**
  - `resources/assets/js/pages/manage.vue:621`
- **off（1）**
  - `resources/assets/js/pages/manage.vue:641`

### `aiAssistantClosed`

- **emit（1）**
  - `resources/assets/js/components/AIAssistant/index.vue:420`
- **on（1）**
  - `resources/assets/js/components/AIAssistant/float-button.vue:154`
- **off（1）**
  - `resources/assets/js/components/AIAssistant/float-button.vue:162`

### `aiOperationRequest`

- **emit（1）**
  - `resources/assets/js/store/actions.js:4781`
- **on（1）**
  - `resources/assets/js/components/AIAssistant/float-button.vue:155`
- **off（1）**
  - `resources/assets/js/components/AIAssistant/float-button.vue:163`

### `approveDetails`

- **emit（2）**
  - `resources/assets/js/pages/manage/approve/index.vue:497`
  - `resources/assets/js/pages/manage/components/DialogWrapper.vue:3826`
- **on（1）**
  - `resources/assets/js/pages/manage.vue:624`
- **off（1）**
  - `resources/assets/js/pages/manage.vue:644`

### `clickAgainDialog`

- **emit（1）**
  - `resources/assets/js/components/Mobile/Tabbar.vue:182`
- **on（1）**
  - `resources/assets/js/pages/manage/messenger.vue:344`
- **off（1）**
  - `resources/assets/js/pages/manage/messenger.vue:348`

### `createGroup`

- **emit（3）**
  - `resources/assets/js/pages/manage/components/DialogWrapper.vue:2871`
  - `resources/assets/js/pages/manage/components/UserDetail.vue:288`
  - `resources/assets/js/pages/manage/messenger.vue:1224`
- **on（1）**
  - `resources/assets/js/pages/manage.vue:622`
- **off（1）**
  - `resources/assets/js/pages/manage.vue:642`

### `dialogMsgPush`

- **emit（1）**
  - `resources/assets/js/store/actions.js:4852`
- **on（2）**
  - `resources/assets/js/components/Mobile/Tabbar.vue:49`
  - `resources/assets/js/pages/manage.vue:623`
- **off（2）**
  - `resources/assets/js/components/Mobile/Tabbar.vue:53`
  - `resources/assets/js/pages/manage.vue:643`

### `handleMoveTop`

- **emit（2）**
  - `resources/assets/js/store/actions.js:2727`
  - `resources/assets/js/store/actions.js:3720`
- **on（2）**
  - `resources/assets/js/pages/manage/components/DialogModal.vue:41`
  - `resources/assets/js/pages/manage/components/TaskModal.vue:49`
- **off（2）**
  - `resources/assets/js/pages/manage/components/DialogModal.vue:45`
  - `resources/assets/js/pages/manage/components/TaskModal.vue:53`

### `observeMicroApp:open`

- **emit（1）**
  - `resources/assets/js/store/actions.js:5361`
- **on（1）**
  - `resources/assets/js/components/MicroApps/index.vue:144`
- **off（1）**
  - `resources/assets/js/components/MicroApps/index.vue:149`

### `observeMicroApp:updatedOrUninstalled`

- **emit（1）**
  - `resources/assets/js/store/mutations.js:429`
- **on（1）**
  - `resources/assets/js/components/MicroApps/index.vue:145`
- **off（1）**
  - `resources/assets/js/components/MicroApps/index.vue:150`

### `openAIAssistant`

- **emit（7）**
  - `resources/assets/js/components/AIAssistant/float-button.vue:476`
  - `resources/assets/js/components/SearchBox.vue:582`
  - `resources/assets/js/pages/manage.vue:1267`
  - `resources/assets/js/pages/manage/components/ChatInput/index.vue:1925`
  - `resources/assets/js/pages/manage/components/ReportDetail.vue:176`
  - `resources/assets/js/pages/manage/components/ReportEdit.vue:267`
  - `resources/assets/js/pages/manage/components/TaskAdd.vue:703`
- **on（1）**
  - `resources/assets/js/components/AIAssistant/index.vue:361`
- **off（1）**
  - `resources/assets/js/components/AIAssistant/index.vue:368`

### `openAIAssistantGlobal`

- **emit（1）**
  - `resources/assets/js/pages/manage.vue:1255`
- **on（1）**
  - `resources/assets/js/components/AIAssistant/float-button.vue:153`
- **off（1）**
  - `resources/assets/js/components/AIAssistant/float-button.vue:161`

### `openDownloadClient`

- **emit（1）**
  - `resources/assets/js/pages/manage.vue:1128`
- **on（1）**
  - `resources/assets/js/components/RightBottom.vue:73`
- **off（1）**
  - `resources/assets/js/components/RightBottom.vue:78`

### `openFavorite`

- **emit（1）**
  - `resources/assets/js/pages/manage/application.vue:1061`
- **on（1）**
  - `resources/assets/js/pages/manage.vue:626`
- **off（1）**
  - `resources/assets/js/pages/manage.vue:646`

### `openManageExport`

- **emit（1）**
  - `resources/assets/js/pages/manage/application.vue:1108`
- **on（1）**
  - `resources/assets/js/pages/manage.vue:628`
- **off（1）**
  - `resources/assets/js/pages/manage.vue:648`

### `openMobileNotification`

- **emit（1）**
  - `resources/assets/js/pages/manage.vue:1641`
- **on（1）**
  - `resources/assets/js/components/Mobile/Notification.vue:38`
- **off（1）**
  - `resources/assets/js/components/Mobile/Notification.vue:42`

### `openProjectInvite`

- **emit（1）**
  - `resources/assets/js/App.vue:432`
- **on（1）**
  - `resources/assets/js/pages/manage/components/ProjectInvite.vue:83`
- **off（1）**
  - `resources/assets/js/pages/manage/components/ProjectInvite.vue:87`

### `openRecent`

- **emit（1）**
  - `resources/assets/js/pages/manage/application.vue:1064`
- **on（1）**
  - `resources/assets/js/pages/manage.vue:627`
- **off（1）**
  - `resources/assets/js/pages/manage.vue:647`

### `openReport`

- **emit（1）**
  - `resources/assets/js/pages/manage/application.vue:1058`
- **on（1）**
  - `resources/assets/js/pages/manage.vue:625`
- **off（1）**
  - `resources/assets/js/pages/manage.vue:645`

### `openSearch`

- **emit（1）**
  - `resources/assets/js/pages/manage/dashboard.vue:256`
- **on（1）**
  - `resources/assets/js/components/SearchBox.vue:128`
- **off（1）**
  - `resources/assets/js/components/SearchBox.vue:132`

### `openUser`

- **emit（5）**
  - `resources/assets/js/components/UserAvatar/index.vue:184`
  - `resources/assets/js/pages/manage/approve/details.vue:546`
  - `resources/assets/js/pages/manage/components/DialogWrapper.vue:2940`
  - `resources/assets/js/pages/manage/components/DialogWrapper.vue:4494`
  - `resources/assets/js/pages/manage/messenger.vue:1229`
- **on（1）**
  - `resources/assets/js/pages/manage/components/UserDetail.vue:166`
- **off（1）**
  - `resources/assets/js/pages/manage/components/UserDetail.vue:170`

### `receiveTask`

- **emit（2）**
  - `resources/assets/js/pages/manage/components/ProjectPanel.vue:1768`
  - `resources/assets/js/pages/manage/components/TaskRow.vue:280`
- **on（1）**
  - `resources/assets/js/pages/manage/components/TaskDetail.vue:738`
- **off（1）**
  - `resources/assets/js/pages/manage/components/TaskDetail.vue:745`

### `streamMsgData`

- **emit（1）**
  - `resources/assets/js/store/actions.js:4469`
- **on（1）**
  - `resources/assets/js/pages/manage/components/DialogWrapper.vue:946`
- **off（1）**
  - `resources/assets/js/pages/manage/components/DialogWrapper.vue:956`

### `taskRelationUpdate`

- **emit（1）**
  - `resources/assets/js/store/actions.js:4992`
- **on（1）**
  - `resources/assets/js/pages/manage/components/TaskDetail.vue:739`
- **off（1）**
  - `resources/assets/js/pages/manage/components/TaskDetail.vue:746`

### `updateNotification`

- **emit（2）**
  - `resources/assets/js/pages/manage.vue:1125`
  - `resources/assets/js/pages/manage/setting/index.vue:191`
- **on（1）**
  - `resources/assets/js/components/RightBottom.vue:65`
- **off（1）**
  - `resources/assets/js/components/RightBottom.vue:77`

### `useSSOLogin`

- **emit（1）**
  - `resources/assets/js/components/RightBottom.vue:231`
- **on（1）**
  - `resources/assets/js/pages/login.vue:217`
- **off（1）**
  - `resources/assets/js/pages/login.vue:222`

### `userActive`

- **emit（3）**
  - `resources/assets/js/store/actions.js:870`
  - `resources/assets/js/store/actions.js:952`
  - `resources/assets/js/store/actions.js:4769`
- **on（1）**
  - `resources/assets/js/components/UserAvatar/index.vue:43`
- **off（1）**
  - `resources/assets/js/components/UserAvatar/index.vue:47`

### `websocketMsg`

- **emit（1）**
  - `resources/assets/js/store/actions.js:4786`
- **on（3）**
  - `resources/assets/js/pages/manage/approve/index.vue:380`
  - `resources/assets/js/pages/manage/components/DialogWrapper.vue:945`
  - `resources/assets/js/pages/manage/components/FileContent.vue:202`
- **off（3）**
  - `resources/assets/js/pages/manage/approve/index.vue:383`
  - `resources/assets/js/pages/manage/components/DialogWrapper.vue:957`
  - `resources/assets/js/pages/manage/components/FileContent.vue:225`

## 动态事件名（无法静态解析）

以下调用的第一参数不是字符串字面量，无法静态解析事件名：

- `resources/assets/js/components/MicroApps/index.vue:365` — `emitter.emit(actionName...)`

## 统计

- 事件总数（静态可解析）: **29**
- 只 emit 无 on（疑似死事件）: **0**
- 只 on 无 emit（无人发射）: **0**
- 动态事件名调用: **1**
