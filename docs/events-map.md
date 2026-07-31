# 前端事件总线注册表

> **本文件由脚本自动生成，请勿手改。**
>
> - 生成命令: `node scripts/gen-events-map.mjs`
> - 扫描范围: `resources/assets/js` 下所有 `.js` / `.vue` 文件（共 272 个）
> - 事件总线: `resources/assets/js/store/events.js`（mitt 实例）
> - 仅匹配裸 `emitter.emit/on/off(` 调用；`xxx.emitter.emit(`（如 Quill 内部 emitter）不属于本总线，已排除

共 **30** 个静态可解析事件，**124** 处 `emitter.emit/on/off` 调用。

## 事件清单

### `addMeeting`

- **emit（10）**
  - `resources/assets/js/App.vue:420`
  - `resources/assets/js/pages/manage.vue:1206`
  - `resources/assets/js/pages/manage.vue:1213`
  - `resources/assets/js/pages/manage/application.vue:1195`
  - `resources/assets/js/pages/manage/application.vue:1201`
  - `resources/assets/js/pages/manage/components/ChatInput/index.vue:1882`
  - `resources/assets/js/pages/manage/components/DialogView/index.vue:621`
  - `resources/assets/js/pages/manage/components/DialogWrapper.vue:2040`
  - `resources/assets/js/pages/manage/components/DialogWrapper.vue:2048`
  - `resources/assets/js/pages/manage/messenger.vue:1219`
- **on（1）**
  - `resources/assets/js/pages/manage/components/MeetingManager/index.vue:187`
- **off（1）**
  - `resources/assets/js/pages/manage/components/MeetingManager/index.vue:191`

### `addTask`

- **emit（3）**
  - `resources/assets/js/pages/manage/calendar.vue:247`
  - `resources/assets/js/pages/manage/components/DialogWrapper.vue:3536`
  - `resources/assets/js/pages/manage/components/ProjectPanel.vue:1357`
- **on（1）**
  - `resources/assets/js/pages/manage.vue:595`
- **off（1）**
  - `resources/assets/js/pages/manage.vue:613`

### `aiAssistantClosed`

- **emit（1）**
  - `resources/assets/js/components/AIAssistant/index.vue:447`
- **on（1）**
  - `resources/assets/js/components/AIAssistant/float-button.vue:159`
- **off（1）**
  - `resources/assets/js/components/AIAssistant/float-button.vue:168`

### `aiAssistantFloatButtonVisibilityChanged`

- **emit（1）**
  - `resources/assets/js/pages/manage/setting/assistant.vue:59`
- **on（1）**
  - `resources/assets/js/components/AIAssistant/float-button.vue:161`
- **off（1）**
  - `resources/assets/js/components/AIAssistant/float-button.vue:170`

### `aiOperationRequest`

- **emit（1）**
  - `resources/assets/js/store/actions.js:4890`
- **on（1）**
  - `resources/assets/js/components/AIAssistant/float-button.vue:160`
- **off（1）**
  - `resources/assets/js/components/AIAssistant/float-button.vue:169`

### `clickAgainDialog`

- **emit（1）**
  - `resources/assets/js/components/Mobile/Tabbar.vue:189`
- **on（1）**
  - `resources/assets/js/pages/manage/messenger.vue:344`
- **off（1）**
  - `resources/assets/js/pages/manage/messenger.vue:348`

### `createGroup`

- **emit（3）**
  - `resources/assets/js/pages/manage/components/DialogWrapper.vue:2894`
  - `resources/assets/js/pages/manage/components/UserDetail.vue:288`
  - `resources/assets/js/pages/manage/messenger.vue:1224`
- **on（1）**
  - `resources/assets/js/pages/manage.vue:596`
- **off（1）**
  - `resources/assets/js/pages/manage.vue:614`

### `dialogMsgPush`

- **emit（1）**
  - `resources/assets/js/store/actions.js:4961`
- **on（2）**
  - `resources/assets/js/components/Mobile/Tabbar.vue:50`
  - `resources/assets/js/pages/manage.vue:597`
- **off（2）**
  - `resources/assets/js/components/Mobile/Tabbar.vue:54`
  - `resources/assets/js/pages/manage.vue:615`

### `handleMoveTop`

- **emit（2）**
  - `resources/assets/js/store/actions.js:2810`
  - `resources/assets/js/store/actions.js:3803`
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
  - `resources/assets/js/components/MicroApps/index.vue:203`
- **off（1）**
  - `resources/assets/js/components/MicroApps/index.vue:209`

### `observeMicroApp:open`

- **emit（1）**
  - `resources/assets/js/store/actions.js:5468`
- **on（1）**
  - `resources/assets/js/components/MicroApps/index.vue:202`
- **off（1）**
  - `resources/assets/js/components/MicroApps/index.vue:208`

### `observeMicroApp:updatedOrUninstalled`

- **emit（1）**
  - `resources/assets/js/store/mutations.js:452`
- **on（1）**
  - `resources/assets/js/components/MicroApps/index.vue:204`
- **off（1）**
  - `resources/assets/js/components/MicroApps/index.vue:210`

### `openAIAssistant`

- **emit（7）**
  - `resources/assets/js/components/AIAssistant/float-button.vue:508`
  - `resources/assets/js/components/SearchBox.vue:582`
  - `resources/assets/js/pages/manage.vue:1237`
  - `resources/assets/js/pages/manage/components/ChatInput/index.vue:1925`
  - `resources/assets/js/pages/manage/components/ReportDetail.vue:176`
  - `resources/assets/js/pages/manage/components/ReportEdit.vue:267`
  - `resources/assets/js/pages/manage/components/TaskAdd.vue:710`
- **on（1）**
  - `resources/assets/js/components/AIAssistant/index.vue:387`
- **off（1）**
  - `resources/assets/js/components/AIAssistant/index.vue:394`

### `openAIAssistantGlobal`

- **emit（1）**
  - `resources/assets/js/pages/manage.vue:1225`
- **on（1）**
  - `resources/assets/js/components/AIAssistant/float-button.vue:158`
- **off（1）**
  - `resources/assets/js/components/AIAssistant/float-button.vue:167`

### `openDownloadClient`

- **emit（1）**
  - `resources/assets/js/pages/manage.vue:1106`
- **on（1）**
  - `resources/assets/js/components/RightBottom.vue:73`
- **off（1）**
  - `resources/assets/js/components/RightBottom.vue:78`

### `openFavorite`

- **emit（1）**
  - `resources/assets/js/pages/manage/application.vue:1068`
- **on（1）**
  - `resources/assets/js/pages/manage.vue:599`
- **off（1）**
  - `resources/assets/js/pages/manage.vue:617`

### `openManageExport`

- **emit（1）**
  - `resources/assets/js/pages/manage/application.vue:1115`
- **on（1）**
  - `resources/assets/js/pages/manage.vue:601`
- **off（1）**
  - `resources/assets/js/pages/manage.vue:619`

### `openMobileNotification`

- **emit（1）**
  - `resources/assets/js/pages/manage.vue:1611`
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
  - `resources/assets/js/pages/manage/application.vue:1071`
- **on（1）**
  - `resources/assets/js/pages/manage.vue:600`
- **off（1）**
  - `resources/assets/js/pages/manage.vue:618`

### `openReport`

- **emit（1）**
  - `resources/assets/js/pages/manage/application.vue:1065`
- **on（1）**
  - `resources/assets/js/pages/manage.vue:598`
- **off（1）**
  - `resources/assets/js/pages/manage.vue:616`

### `openSearch`

- **emit（1）**
  - `resources/assets/js/pages/manage/dashboard.vue:784`
- **on（1）**
  - `resources/assets/js/components/SearchBox.vue:128`
- **off（1）**
  - `resources/assets/js/components/SearchBox.vue:132`

### `openUser`

- **emit（4）**
  - `resources/assets/js/components/UserAvatar/index.vue:184`
  - `resources/assets/js/pages/manage/components/DialogWrapper.vue:2963`
  - `resources/assets/js/pages/manage/components/DialogWrapper.vue:4546`
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
  - `resources/assets/js/pages/manage/components/TaskDetail.vue:744`
- **off（1）**
  - `resources/assets/js/pages/manage/components/TaskDetail.vue:751`

### `streamMsgData`

- **emit（1）**
  - `resources/assets/js/store/actions.js:4578`
- **on（1）**
  - `resources/assets/js/pages/manage/components/DialogWrapper.vue:944`
- **off（1）**
  - `resources/assets/js/pages/manage/components/DialogWrapper.vue:954`

### `taskRelationUpdate`

- **emit（1）**
  - `resources/assets/js/store/actions.js:5101`
- **on（1）**
  - `resources/assets/js/pages/manage/components/TaskDetail.vue:745`
- **off（1）**
  - `resources/assets/js/pages/manage/components/TaskDetail.vue:752`

### `updateNotification`

- **emit（2）**
  - `resources/assets/js/pages/manage.vue:1103`
  - `resources/assets/js/pages/manage/setting/index.vue:206`
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
  - `resources/assets/js/store/actions.js:857`
  - `resources/assets/js/store/actions.js:939`
  - `resources/assets/js/store/actions.js:4878`
- **on（1）**
  - `resources/assets/js/components/UserAvatar/index.vue:43`
- **off（1）**
  - `resources/assets/js/components/UserAvatar/index.vue:47`

### `websocketMsg`

- **emit（1）**
  - `resources/assets/js/store/actions.js:4895`
- **on（2）**
  - `resources/assets/js/pages/manage/components/DialogWrapper.vue:943`
  - `resources/assets/js/pages/manage/components/FileContent.vue:202`
- **off（2）**
  - `resources/assets/js/pages/manage/components/DialogWrapper.vue:955`
  - `resources/assets/js/pages/manage/components/FileContent.vue:225`

## 动态事件名（无法静态解析）

以下调用的第一参数不是字符串字面量，无法静态解析事件名：

- `resources/assets/js/components/MicroApps/index.vue:448` — `emitter.emit(actionName...)`

## 统计

- 事件总数（静态可解析）: **30**
- 只 emit 无 on（疑似死事件）: **0**
- 只 on 无 emit（无人发射）: **0**
- 动态事件名调用: **1**
