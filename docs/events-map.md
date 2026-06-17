# 前端事件总线注册表

> **本文件由脚本自动生成，请勿手改。**
>
> - 生成命令: `node scripts/gen-events-map.mjs`
> - 扫描范围: `resources/assets/js` 下所有 `.js` / `.vue` 文件（共 267 个）
> - 事件总线: `resources/assets/js/store/events.js`（mitt 实例）
> - 仅匹配裸 `emitter.emit/on/off(` 调用；`xxx.emitter.emit(`（如 Quill 内部 emitter）不属于本总线，已排除

共 **29** 个静态可解析事件，**121** 处 `emitter.emit/on/off` 调用。

## 事件清单

### `addMeeting`

- **emit（10）**
  - `resources/assets/js/App.vue:420`
  - `resources/assets/js/pages/manage.vue:1192`
  - `resources/assets/js/pages/manage.vue:1199`
  - `resources/assets/js/pages/manage/application.vue:1181`
  - `resources/assets/js/pages/manage/application.vue:1187`
  - `resources/assets/js/pages/manage/components/ChatInput/index.vue:1882`
  - `resources/assets/js/pages/manage/components/DialogView/index.vue:621`
  - `resources/assets/js/pages/manage/components/DialogWrapper.vue:2012`
  - `resources/assets/js/pages/manage/components/DialogWrapper.vue:2020`
  - `resources/assets/js/pages/manage/messenger.vue:1219`
- **on（1）**
  - `resources/assets/js/pages/manage/components/MeetingManager/index.vue:187`
- **off（1）**
  - `resources/assets/js/pages/manage/components/MeetingManager/index.vue:191`

### `addTask`

- **emit（3）**
  - `resources/assets/js/pages/manage/calendar.vue:255`
  - `resources/assets/js/pages/manage/components/DialogWrapper.vue:3508`
  - `resources/assets/js/pages/manage/components/ProjectPanel.vue:1357`
- **on（1）**
  - `resources/assets/js/pages/manage.vue:592`
- **off（1）**
  - `resources/assets/js/pages/manage.vue:610`

### `aiAssistantClosed`

- **emit（1）**
  - `resources/assets/js/components/AIAssistant/index.vue:442`
- **on（1）**
  - `resources/assets/js/components/AIAssistant/float-button.vue:154`
- **off（1）**
  - `resources/assets/js/components/AIAssistant/float-button.vue:162`

### `aiOperationRequest`

- **emit（1）**
  - `resources/assets/js/store/actions.js:4752`
- **on（1）**
  - `resources/assets/js/components/AIAssistant/float-button.vue:155`
- **off（1）**
  - `resources/assets/js/components/AIAssistant/float-button.vue:163`

### `clickAgainDialog`

- **emit（1）**
  - `resources/assets/js/components/Mobile/Tabbar.vue:182`
- **on（1）**
  - `resources/assets/js/pages/manage/messenger.vue:344`
- **off（1）**
  - `resources/assets/js/pages/manage/messenger.vue:348`

### `createGroup`

- **emit（3）**
  - `resources/assets/js/pages/manage/components/DialogWrapper.vue:2866`
  - `resources/assets/js/pages/manage/components/UserDetail.vue:288`
  - `resources/assets/js/pages/manage/messenger.vue:1224`
- **on（1）**
  - `resources/assets/js/pages/manage.vue:593`
- **off（1）**
  - `resources/assets/js/pages/manage.vue:611`

### `dialogMsgPush`

- **emit（1）**
  - `resources/assets/js/store/actions.js:4823`
- **on（2）**
  - `resources/assets/js/components/Mobile/Tabbar.vue:49`
  - `resources/assets/js/pages/manage.vue:594`
- **off（2）**
  - `resources/assets/js/components/Mobile/Tabbar.vue:53`
  - `resources/assets/js/pages/manage.vue:612`

### `handleMoveTop`

- **emit（2）**
  - `resources/assets/js/store/actions.js:2698`
  - `resources/assets/js/store/actions.js:3691`
- **on（2）**
  - `resources/assets/js/pages/manage/components/DialogModal.vue:41`
  - `resources/assets/js/pages/manage/components/TaskModal.vue:49`
- **off（2）**
  - `resources/assets/js/pages/manage/components/DialogModal.vue:45`
  - `resources/assets/js/pages/manage/components/TaskModal.vue:53`

### `observeMicroApp:close`

- **emit（1）**
  - `resources/assets/js/components/AIAssistant/action-executor.js:234`
- **on（1）**
  - `resources/assets/js/components/MicroApps/index.vue:145`
- **off（1）**
  - `resources/assets/js/components/MicroApps/index.vue:151`

### `observeMicroApp:open`

- **emit（1）**
  - `resources/assets/js/store/actions.js:5321`
- **on（1）**
  - `resources/assets/js/components/MicroApps/index.vue:144`
- **off（1）**
  - `resources/assets/js/components/MicroApps/index.vue:150`

### `observeMicroApp:updatedOrUninstalled`

- **emit（1）**
  - `resources/assets/js/store/mutations.js:429`
- **on（1）**
  - `resources/assets/js/components/MicroApps/index.vue:146`
- **off（1）**
  - `resources/assets/js/components/MicroApps/index.vue:152`

### `openAIAssistant`

- **emit（7）**
  - `resources/assets/js/components/AIAssistant/float-button.vue:476`
  - `resources/assets/js/components/SearchBox.vue:582`
  - `resources/assets/js/pages/manage.vue:1223`
  - `resources/assets/js/pages/manage/components/ChatInput/index.vue:1925`
  - `resources/assets/js/pages/manage/components/ReportDetail.vue:176`
  - `resources/assets/js/pages/manage/components/ReportEdit.vue:267`
  - `resources/assets/js/pages/manage/components/TaskAdd.vue:710`
- **on（1）**
  - `resources/assets/js/components/AIAssistant/index.vue:382`
- **off（1）**
  - `resources/assets/js/components/AIAssistant/index.vue:389`

### `openAIAssistantGlobal`

- **emit（1）**
  - `resources/assets/js/pages/manage.vue:1211`
- **on（1）**
  - `resources/assets/js/components/AIAssistant/float-button.vue:153`
- **off（1）**
  - `resources/assets/js/components/AIAssistant/float-button.vue:161`

### `openDownloadClient`

- **emit（1）**
  - `resources/assets/js/pages/manage.vue:1092`
- **on（1）**
  - `resources/assets/js/components/RightBottom.vue:73`
- **off（1）**
  - `resources/assets/js/components/RightBottom.vue:78`

### `openFavorite`

- **emit（1）**
  - `resources/assets/js/pages/manage/application.vue:1054`
- **on（1）**
  - `resources/assets/js/pages/manage.vue:596`
- **off（1）**
  - `resources/assets/js/pages/manage.vue:614`

### `openManageExport`

- **emit（1）**
  - `resources/assets/js/pages/manage/application.vue:1101`
- **on（1）**
  - `resources/assets/js/pages/manage.vue:598`
- **off（1）**
  - `resources/assets/js/pages/manage.vue:616`

### `openMobileNotification`

- **emit（1）**
  - `resources/assets/js/pages/manage.vue:1597`
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
  - `resources/assets/js/pages/manage/application.vue:1057`
- **on（1）**
  - `resources/assets/js/pages/manage.vue:597`
- **off（1）**
  - `resources/assets/js/pages/manage.vue:615`

### `openReport`

- **emit（1）**
  - `resources/assets/js/pages/manage/application.vue:1051`
- **on（1）**
  - `resources/assets/js/pages/manage.vue:595`
- **off（1）**
  - `resources/assets/js/pages/manage.vue:613`

### `openSearch`

- **emit（1）**
  - `resources/assets/js/pages/manage/dashboard.vue:256`
- **on（1）**
  - `resources/assets/js/components/SearchBox.vue:128`
- **off（1）**
  - `resources/assets/js/components/SearchBox.vue:132`

### `openUser`

- **emit（4）**
  - `resources/assets/js/components/UserAvatar/index.vue:184`
  - `resources/assets/js/pages/manage/components/DialogWrapper.vue:2935`
  - `resources/assets/js/pages/manage/components/DialogWrapper.vue:4485`
  - `resources/assets/js/pages/manage/messenger.vue:1229`
- **on（1）**
  - `resources/assets/js/pages/manage/components/UserDetail.vue:166`
- **off（1）**
  - `resources/assets/js/pages/manage/components/UserDetail.vue:170`

### `receiveTask`

- **emit（2）**
  - `resources/assets/js/pages/manage/components/ProjectPanel.vue:1775`
  - `resources/assets/js/pages/manage/components/TaskRow.vue:280`
- **on（1）**
  - `resources/assets/js/pages/manage/components/TaskDetail.vue:738`
- **off（1）**
  - `resources/assets/js/pages/manage/components/TaskDetail.vue:745`

### `streamMsgData`

- **emit（1）**
  - `resources/assets/js/store/actions.js:4440`
- **on（1）**
  - `resources/assets/js/pages/manage/components/DialogWrapper.vue:943`
- **off（1）**
  - `resources/assets/js/pages/manage/components/DialogWrapper.vue:953`

### `taskRelationUpdate`

- **emit（1）**
  - `resources/assets/js/store/actions.js:4963`
- **on（1）**
  - `resources/assets/js/pages/manage/components/TaskDetail.vue:739`
- **off（1）**
  - `resources/assets/js/pages/manage/components/TaskDetail.vue:746`

### `updateNotification`

- **emit（2）**
  - `resources/assets/js/pages/manage.vue:1089`
  - `resources/assets/js/pages/manage/setting/index.vue:191`
- **on（1）**
  - `resources/assets/js/components/RightBottom.vue:65`
- **off（1）**
  - `resources/assets/js/components/RightBottom.vue:77`

### `useSSOLogin`

- **emit（1）**
  - `resources/assets/js/components/RightBottom.vue:231`
- **on（1）**
  - `resources/assets/js/pages/login.vue:219`
- **off（1）**
  - `resources/assets/js/pages/login.vue:224`

### `userActive`

- **emit（3）**
  - `resources/assets/js/store/actions.js:841`
  - `resources/assets/js/store/actions.js:923`
  - `resources/assets/js/store/actions.js:4740`
- **on（1）**
  - `resources/assets/js/components/UserAvatar/index.vue:43`
- **off（1）**
  - `resources/assets/js/components/UserAvatar/index.vue:47`

### `websocketMsg`

- **emit（1）**
  - `resources/assets/js/store/actions.js:4757`
- **on（2）**
  - `resources/assets/js/pages/manage/components/DialogWrapper.vue:942`
  - `resources/assets/js/pages/manage/components/FileContent.vue:202`
- **off（2）**
  - `resources/assets/js/pages/manage/components/DialogWrapper.vue:954`
  - `resources/assets/js/pages/manage/components/FileContent.vue:225`

## 动态事件名（无法静态解析）

以下调用的第一参数不是字符串字面量，无法静态解析事件名：

- `resources/assets/js/components/MicroApps/index.vue:375` — `emitter.emit(actionName...)`

## 统计

- 事件总数（静态可解析）: **29**
- 只 emit 无 on（疑似死事件）: **0**
- 只 on 无 emit（无人发射）: **0**
- 动态事件名调用: **1**
