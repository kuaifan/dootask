# API 路由对照表

> 此文件由 `php artisan doc:api-map` 生成，勿手改。

接口总数：312

## 路由规则

API 使用动态路由（见 `routes/web.php`），URL 段映射为控制器方法名：

- `api/{controller}/{method}` → `{method}()`，如 `api/project/lists` → `ProjectController::lists()`
- `api/{controller}/{method}/{action}` → `{method}__{action}()`（双下划线连接），如 `api/project/invite/join` → `ProjectController::invite__join()`
- 路由最多两段，方法名最多一个双下划线

## users（UsersController）

| URL | 方法名 | HTTP | 说明 |
| --- | --- | --- | --- |
| api/users/login | login() | get | 登录、注册 |
| api/users/login/qrcode | login__qrcode() | get | 二维码登录 |
| api/users/login/needcode | login__needcode() | get | 是否需要验证码 |
| api/users/login/codeimg | login__codeimg() | get | 验证码图片 |
| api/users/login/codejson | login__codejson() | get | 验证码json |
| api/users/logout | logout() | get | 退出登录 |
| api/users/token/expire | token__expire() | get | 查询 token 过期时间 |
| api/users/reg/needinvite | reg__needinvite() | get | 是否需要邀请码 |
| api/users/info | info() | get | 获取我的信息 |
| api/users/info/managed_departments | info__managed_departments() | get | 获取我可切换负责人视角的部门列表 |
| api/users/info/departments | info__departments() | get | 获取我的部门列表 |
| api/users/editdata | editdata() | get | 修改自己的资料 |
| api/users/editpass | editpass() | get | 修改自己的密码 |
| api/users/search | search() | get | 搜索会员列表 |
| api/users/search/ai | search__ai() | get | 获取AI机器人 |
| api/users/basic | basic() | get | 获取指定会员基础信息 |
| api/users/extra | extra() | get | 获取会员扩展信息 |
| api/users/lists | lists() | get | 会员列表（限管理员） |
| api/users/operation | operation() | get | 操作会员（限管理员） |
| api/users/createuser | createuser() | post | 创建用户（管理员） |
| api/users/import/preview | import__preview() | post | 批量导入预览（管理员） |
| api/users/import | import() | post | 批量导入用户（管理员） |
| api/users/import/template | import__template() | get | 下载批量导入模板（管理员） |
| api/users/email/verification | email__verification() | get | 邮箱验证 |
| api/users/umeng/alias | umeng__alias() | get | 设置友盟别名 |
| api/users/meeting/open | meeting__open() | get | 【会议】创建会议、加入会议 |
| api/users/tags/lists | tags__lists() | get | 获取个性标签列表 |
| api/users/tags/add | tags__add() | post | 新增个性标签 |
| api/users/tags/update | tags__update() | post | 修改个性标签 |
| api/users/tags/delete | tags__delete() | post | 删除个性标签 |
| api/users/tags/recognize | tags__recognize() | post | 认可个性标签 |
| api/users/meeting/link | meeting__link() | get | 【会议】获取分享链接 |
| api/users/meeting/tourist | meeting__tourist() | get | 【会议】游客信息 |
| api/users/meeting/invitation | meeting__invitation() | get | 【会议】发送邀请 |
| api/users/email/send | email__send() | get | 发送邮箱验证码 |
| api/users/email/edit | email__edit() | get | 修改邮箱 |
| api/users/delete/account | delete__account() | get | 删除帐号 |
| api/users/department/list | department__list() | get | 部门列表（限管理员） |
| api/users/department/add | department__add() | get | 新建、修改部门（限管理员） |
| api/users/department/adddeputy | department__adddeputy() | post | 任命部门管理员（限管理员） |
| api/users/department/deldeputy | department__deldeputy() | post | 罢免部门管理员（限管理员） |
| api/users/department/del | department__del() | get | 删除部门（限管理员） |
| api/users/department/sync | department__sync() | get | 同步部门成员（限管理员） |
| api/users/checkin/get | checkin__get() | get | 获取签到设置 |
| api/users/checkin/save | checkin__save() | post | 保存签到设置 |
| api/users/checkin/list | checkin__list() | get | 获取签到数据 |
| api/users/socket/status | socket__status() | get | 获取socket状态 |
| api/users/key/client | key__client() | get | 客户端KEY |
| api/users/bot/list | bot__list() | get | 机器人列表 |
| api/users/bot/info | bot__info() | get | 机器人信息 |
| api/users/bot/edit | bot__edit() | post | 添加、编辑机器人 |
| api/users/bot/delete | bot__delete() | get | 删除机器人 |
| api/users/share/list | share__list() | get | 获取分享列表 |
| api/users/annual/report | annual__report() | get | 年度报告 |
| api/users/device/list | device__list() | get | 获取设备列表 |
| api/users/device/logout | device__logout() | get | 登出设备（删除设备） |
| api/users/device/edit | device__edit() | get | 编辑设备 |
| api/users/task/browse | task__browse() | get | 获取任务浏览历史 |
| api/users/task/browse_save | task__browse_save() | get | 记录任务浏览历史 |
| api/users/task/browse_clean | task__browse_clean() | post | 清理任务浏览历史 |
| api/users/recent/browse | recent__browse() | get | 获取最近访问记录 |
| api/users/recent/delete | recent__delete() | post | 删除最近访问记录 |
| api/users/appsort | appsort() | get | 获取个人应用排序 |
| api/users/appsort/save | appsort__save() | post | 保存个人应用排序 |
| api/users/favorites | favorites() | get | 获取用户收藏列表 |
| api/users/favorite/toggle | favorite__toggle() | post | 切换收藏状态 |
| api/users/favorite/remark | favorite__remark() | post | 修改收藏备注 |
| api/users/favorites/clean | favorites__clean() | post | 清理用户收藏 |
| api/users/favorite/check | favorite__check() | get | 检查收藏状态 |

## project（ProjectController）

| URL | 方法名 | HTTP | 说明 |
| --- | --- | --- | --- |
| api/project/lists | lists() | get | 获取项目列表 |
| api/project/one | one() | get | 获取一个项目信息 |
| api/project/add | add() | get | 添加项目 |
| api/project/update | update() | get | 修改项目 |
| api/project/user | user() | post | 修改项目成员 |
| api/project/invite | invite() | get | 获取邀请链接 |
| api/project/invite/info | invite__info() | get | 通过邀请链接code获取项目信息 |
| api/project/invite/join | invite__join() | get | 通过邀请链接code加入项目 |
| api/project/transfer | transfer() | get | 移交项目 |
| api/project/adddeputy | adddeputy() | post | 任命项目管理员（仅负责人可操作） |
| api/project/deldeputy | deldeputy() | post | 罢免项目管理员（仅负责人可操作） |
| api/project/sort | sort() | post | 排序任务 |
| api/project/user/sort | user__sort() | post | 项目列表排序 |
| api/project/exit | exit() | get | 退出项目 |
| api/project/archived | archived() | get | 归档项目 |
| api/project/remove | remove() | get | 删除项目 |
| api/project/column/lists | column__lists() | get | 获取任务列表 |
| api/project/column/add | column__add() | get | 添加任务列表 |
| api/project/column/update | column__update() | get | 修改任务列表 |
| api/project/column/remove | column__remove() | get | 删除任务列表 |
| api/project/column/one | column__one() | get | 获取任务列详细 |
| api/project/task/lists | task__lists() | get | 任务列表 |
| api/project/user/projects | user__projects() | get | 会员参与的项目列表 |
| api/project/user/tasks | user__tasks() | get | 会员参与的任务列表 |
| api/project/user/counts | user__counts() | get | 会员参与的项目/任务数量 |
| api/project/task/easylists | task__easylists() | get | 任务列表-简单的 |
| api/project/task/export | task__export() | get | 导出任务（限管理员） |
| api/project/task/exportoverdue | task__exportoverdue() | get | 导出超期任务（限管理员） |
| api/project/task/down | task__down() | get | 下载导出的任务 |
| api/project/task/one | task__one() | get | 获取单个任务信息 |
| api/project/task/subdata | task__subdata() | get | 获取子任务数据 |
| api/project/task/related | task__related() | get | 获取任务关联任务列表 |
| api/project/task/related/delete | task__related__delete() | post | 删除任务关联 |
| api/project/task/content | task__content() | get | 获取任务详细描述 |
| api/project/task/content_history | task__content_history() | get | 获取任务详细历史描述 |
| api/project/task/files | task__files() | get | 获取任务文件列表 |
| api/project/task/filedelete | task__filedelete() | get | 删除任务文件 |
| api/project/task/filedetail | task__filedetail() | get | 获取任务文件详情 |
| api/project/task/filedown | task__filedown() | get | 下载任务文件 |
| api/project/task/add | task__add() | post | 添加任务 |
| api/project/task/addsub | task__addsub() | get | 添加子任务 |
| api/project/task/upgrade | task__upgrade() | get | 子任务升级为主任务 |
| api/project/task/update | task__update() | post | 修改任务、子任务 |
| api/project/task/dialog | task__dialog() | get | 创建/获取聊天室 |
| api/project/task/archived | task__archived() | get | 归档任务 |
| api/project/task/remove | task__remove() | get | 删除任务 |
| api/project/task/resetfromlog | task__resetfromlog() | get | 根据日志重置任务 |
| api/project/task/flow | task__flow() | get | 任务工作流信息 |
| api/project/task/move | task__move() | get | 任务移动 |
| api/project/task/copy | task__copy() | post | 复制任务 |
| api/project/task/ai_generate | task__ai_generate() | any |  |
| api/project/ai/generate | ai__generate() | any |  |
| api/project/flow/list | flow__list() | get | 工作流列表 |
| api/project/flow/save | flow__save() | post | 保存工作流 |
| api/project/flow/delete | flow__delete() | get | 删除工作流 |
| api/project/log/lists | log__lists() | get | 获取项目、任务日志 |
| api/project/top | top() | get | 项目置顶 |
| api/project/permission | permission() | get | 获取项目权限设置 |
| api/project/permission/update | permission__update() | get | 项目权限设置 |
| api/project/task/template_list | task__template_list() | get | 任务模板列表 |
| api/project/task/template_visible | task__template_visible() | get | 当前用户跨项目可见的全部任务模板 |
| api/project/task/template_search | task__template_search() | get | 跨项目模板搜索分页 |
| api/project/task/template_save | task__template_save() | post | 保存任务模板 |
| api/project/task/template_sort | task__template_sort() | post | 排序任务模板 |
| api/project/task/template_delete | task__template_delete() | get | 删除任务模板 |
| api/project/task/template_default | task__template_default() | get | 设置(取消)任务模板为默认 |
| api/project/tag/save | tag__save() | post | 保存标签 |
| api/project/tag/sort | tag__sort() | post | 标签排序 |
| api/project/tag/delete | tag__delete() | get | 删除标签 |
| api/project/tag/list | tag__list() | get | 标签列表 |
| api/project/task/ai_apply | task__ai_apply() | post | 采纳AI建议 |
| api/project/task/ai_dismiss | task__ai_dismiss() | post | 忽略AI建议 |

## system（SystemController）

| URL | 方法名 | HTTP | 说明 |
| --- | --- | --- | --- |
| api/system/setting | setting() | get | 获取设置、保存设置 |
| api/system/setting/email | setting__email() | get | 获取邮箱设置、保存邮箱设置（限管理员） |
| api/system/setting/meeting | setting__meeting() | get | 获取会议设置、保存会议设置（限管理员） |
| api/system/setting/ai | setting__ai() | any |  |
| api/system/setting/aibot | setting__aibot() | get | 获取AI设置、保存AI机器人设置（限管理员） |
| api/system/setting/aibot_models | setting__aibot_models() | any |  |
| api/system/setting/aibot_defmodels | setting__aibot_defmodels() | any |  |
| api/system/setting/checkin | setting__checkin() | get | 获取签到设置、保存签到设置（限管理员） |
| api/system/setting/apppush | setting__apppush() | get | 获取APP推送设置、保存APP推送设置（限管理员） |
| api/system/setting/thirdaccess | setting__thirdaccess() | get | 第三方帐号（限管理员） |
| api/system/setting/file | setting__file() | get | 文件设置（限管理员） |
| api/system/demo | demo() | get | 获取演示帐号 |
| api/system/priority | priority() | post | 任务优先级 |
| api/system/microapp_menu | microapp_menu() | post | 自定义应用菜单 |
| api/system/column/template | column__template() | post | 创建项目模板 |
| api/system/license | license() | post | License |
| api/system/get/info | get__info() | get | 获取终端详细信息 |
| api/system/get/ip | get__ip() | get | 获取IP地址 |
| api/system/get/cnip | get__cnip() | get | 是否中国IP地址 |
| api/system/imgupload | imgupload() | post | 上传图片 |
| api/system/imgview | imgview() | get | 浏览图片空间 |
| api/system/fileupload | fileupload() | post | 上传文件 |
| api/system/get/updatelog | get__updatelog() | get | 获取更新日志 |
| api/system/email/check | email__check() | get | 邮件发送测试（限管理员） |
| api/system/checkin/export | checkin__export() | get | 导出签到数据（限管理员） |
| api/system/checkin/down | checkin__down() | get | 下载导出的签到数据 |
| api/system/version | version() | get | 获取版本号 |
| api/system/prefetch | prefetch() | get | 预加载的资源 |

## license（LicenseController）

| URL | 方法名 | HTTP | 说明 |
| --- | --- | --- | --- |
| api/license/email/send | email__send() | any |  |
| api/license/login | login() | any |  |
| api/license/login/confirm | login__confirm() | any |  |
| api/license/trial | trial() | any |  |
| api/license/status | status() | any |  |
| api/license/refresh | refresh() | any |  |
| api/license/logout | logout() | any |  |

## dialog（DialogController）

| URL | 方法名 | HTTP | 说明 |
| --- | --- | --- | --- |
| api/dialog/lists | lists() | get | 对话列表 |
| api/dialog/beyond | beyond() | get | 列表外对话 |
| api/dialog/search | search() | get | 搜索会话 |
| api/dialog/search/tag | search__tag() | get | 搜索标注会话 |
| api/dialog/one | one() | get | 获取单个会话信息 |
| api/dialog/user | user() | get | 获取会话成员 |
| api/dialog/todo | todo() | get | 获取会话待办 |
| api/dialog/top | top() | get | 会话置顶 |
| api/dialog/hide | hide() | get | 会话隐藏 |
| api/dialog/tel | tel() | get | 获取对方联系电话 |
| api/dialog/open/user | open__user() | get | 打开会话 |
| api/dialog/open/event | open__event() | get | 打开会话事件 |
| api/dialog/msg/list | msg__list() | get | 获取消息列表 |
| api/dialog/msg/latest | msg__latest() | get | 获取最新消息列表 |
| api/dialog/msg/one | msg__one() | get | 获取单条消息 |
| api/dialog/msg/dot | msg__dot() | get | 聊天消息去除点 |
| api/dialog/msg/read | msg__read() | get | 已读聊天消息 |
| api/dialog/msg/unread | msg__unread() | get | 获取未读消息数据 |
| api/dialog/msg/checked | msg__checked() | get | 设置消息checked |
| api/dialog/msg/stream | msg__stream() | post | 通知成员监听消息 |
| api/dialog/msg/ai_generate | msg__ai_generate() | any |  |
| api/dialog/msg/sendtext | msg__sendtext() | post | 发送消息 |
| api/dialog/msg/sendnotice | msg__sendnotice() | post | 发送通知 |
| api/dialog/msg/sendtemplate | msg__sendtemplate() | post | 发送模板消息 |
| api/dialog/msg/sendapprove | msg__sendapprove() | post | 发送审批通知卡片 |
| api/dialog/msg/sendrecord | msg__sendrecord() | post | 发送语音 |
| api/dialog/msg/convertrecord | msg__convertrecord() | post | 录音转文字 |
| api/dialog/msg/sendfile | msg__sendfile() | post | 文件上传 |
| api/dialog/msg/sendfiles | msg__sendfiles() | post | 群发文件上传 |
| api/dialog/msg/sendfileid | msg__sendfileid() | get | 通过文件ID发送文件 |
| api/dialog/msg/sendtaskid | msg__sendtaskid() | get | 通过任务ID发送任务 |
| api/dialog/msg/sendanon | msg__sendanon() | post | 发送匿名消息 |
| api/dialog/msg/sendbot | msg__sendbot() | post | 发送机器人消息 |
| api/dialog/msg/send_ai_assistant | msg__send_ai_assistant() | post | 以AI助手身份发送消息到对话 |
| api/dialog/msg/sendlocation | msg__sendlocation() | post | 发送位置消息 |
| api/dialog/msg/readlist | msg__readlist() | get | 获取消息阅读情况 |
| api/dialog/msg/detail | msg__detail() | get | 消息详情 |
| api/dialog/msg/download | msg__download() | get | 文件下载 |
| api/dialog/msg/withdraw | msg__withdraw() | get | 聊天消息撤回 |
| api/dialog/msg/voice2text | msg__voice2text() | get | 语音消息转文字 |
| api/dialog/msg/translation | msg__translation() | get | 翻译消息 |
| api/dialog/msg/mark | msg__mark() | get | 消息标记操作 |
| api/dialog/msg/silence | msg__silence() | get | 消息免打扰 |
| api/dialog/msg/forward | msg__forward() | get | 转发消息给 |
| api/dialog/msg/mergeforward | msg__mergeforward() | get | 合并转发消息 |
| api/dialog/msg/mergedetail | msg__mergedetail() | get | 合并转发消息详情 |
| api/dialog/msg/emoji | msg__emoji() | get | emoji回复 |
| api/dialog/msg/tag | msg__tag() | get | 标注/取消标注 |
| api/dialog/msg/todo | msg__todo() | get | 设待办/取消待办 |
| api/dialog/msg/todolist | msg__todolist() | get | 获取消息待办情况 |
| api/dialog/msg/todoremind | msg__todoremind() | post | 设置/修改/取消待办提醒时间 |
| api/dialog/msg/done | msg__done() | get | 完成待办 |
| api/dialog/msg/color | msg__color() | get | 设置颜色 |
| api/dialog/msg/webhookmsg2ai | msg__webhookmsg2ai() | any |  |
| api/dialog/group/add | group__add() | get | 新增群组 |
| api/dialog/group/edit | group__edit() | get | 修改群组 |
| api/dialog/group/adduser | group__adduser() | get | 添加群成员 |
| api/dialog/group/deluser | group__deluser() | get | 移出（退出）群成员 |
| api/dialog/group/transfer | group__transfer() | get | 转让群组 |
| api/dialog/group/adddeputy | group__adddeputy() | any |  |
| api/dialog/group/deldeputy | group__deldeputy() | any |  |
| api/dialog/group/disband | group__disband() | get | 解散群组 |
| api/dialog/group/searchuser | group__searchuser() | get | 搜索个人群（仅限管理员） |
| api/dialog/common/list | common__list() | get | 共同群组群聊 |
| api/dialog/okr/add | okr__add() | post | 创建OKR评论会话 |
| api/dialog/okr/push | okr__push() | post | 推送OKR相关信息 |
| api/dialog/msg/wordchain | msg__wordchain() | post | 发送接龙消息 |
| api/dialog/msg/vote | msg__vote() | post | 发起投票 |
| api/dialog/msg/top | msg__top() | get | 置顶/取消置顶 |
| api/dialog/msg/topinfo | msg__topinfo() | get | 获取置顶消息 |
| api/dialog/msg/applied | msg__applied() | any |  |
| api/dialog/sticker/search | sticker__search() | get | 搜索在线表情 |
| api/dialog/config | config() | get | 获取会话配置 |
| api/dialog/config/save | config__save() | post | 保存会话配置 |
| api/dialog/session/create | session__create() | get | AI-开启新会话 |
| api/dialog/session/list | session__list() | get | AI-获取会话列表 |
| api/dialog/session/open | session__open() | get | AI-打开会话 |
| api/dialog/session/rename | session__rename() | post | AI-重命名会话 |

## file（FileController）

| URL | 方法名 | HTTP | 说明 |
| --- | --- | --- | --- |
| api/file/lists | lists() | get | 获取文件列表 |
| api/file/one | one() | get | 获取单条数据 |
| api/file/fetch | fetch() | get | 通过路径获取文件文本内容 |
| api/file/search | search() | get | 搜索文件列表 |
| api/file/add | add() | get | 添加、修改文件(夹) |
| api/file/copy | copy() | get | 复制文件(夹) |
| api/file/move | move() | get | 移动文件(夹) |
| api/file/remove | remove() | get | 删除文件(夹) |
| api/file/content | content() | get | 获取文件内容 |
| api/file/content/save | content__save() | get | 保存文件内容 |
| api/file/office/token | office__token() | get | 获取token |
| api/file/content/office | content__office() | get | 保存文件内容（office） |
| api/file/content/upload | content__upload() | get | 保存文件内容（上传文件） |
| api/file/content/history | content__history() | get | 获取内容历史 |
| api/file/content/restore | content__restore() | get | 恢复文件历史 |
| api/file/share | share() | get | 获取共享信息 |
| api/file/share/update | share__update() | get | 设置共享 |
| api/file/share/out | share__out() | get | 退出共享 |
| api/file/link | link() | get | 获取链接 |
| api/file/download/pack | download__pack() | get | 打包文件 |

## upload（UploadController）

| URL | 方法名 | HTTP | 说明 |
| --- | --- | --- | --- |
| api/upload/init | init() | post | 启动上传会话 |
| api/upload/chunk | chunk() | post | 上传一个分片 |
| api/upload/merge | merge() | post | 合并分片并入库 |
| api/upload/cancel | cancel() | post | 取消上传会话 |

## report（ReportController）

| URL | 方法名 | HTTP | 说明 |
| --- | --- | --- | --- |
| api/report/my | my() | get | 我发送的汇报 |
| api/report/receive | receive() | get | 我接收的汇报 |
| api/report/store | store() | get | 保存并发送工作汇报 |
| api/report/template | template() | get | 生成汇报模板 |
| api/report/detail | detail() | get | 报告详情 |
| api/report/analysave | analysave() | post | 保存工作汇报 AI 分析 |
| api/report/mark | mark() | get | 标记已读/未读 |
| api/report/share | share() | get | 分享报告到消息 |
| api/report/last_submitter | last_submitter() | get | 获取最后一次提交的接收人 |
| api/report/unread | unread() | get | 获取未读 |
| api/report/read | read() | get | 标记汇报已读，可批量 |

## public（PublicController）

| URL | 方法名 | HTTP | 说明 |
| --- | --- | --- | --- |
| api/public/checkin/install | checkin__install() | any |  |
| api/public/checkin/report | checkin__report() | any |  |

## assistant（AssistantController）

| URL | 方法名 | HTTP | 说明 |
| --- | --- | --- | --- |
| api/assistant/auth | auth() | post | 生成授权码 |
| api/assistant/models | models() | get | 获取AI模型 |
| api/assistant/match_elements | match_elements() | post | 元素向量匹配 |
| api/assistant/log/search | log__search() | post | 记录帮助知识库检索日志 |
| api/assistant/feedback/save | feedback__save() | post | 保存回复反馈 |
| api/assistant/operation/dispatch | operation__dispatch() | post | 派发页面操作 |
| api/assistant/operation/result | operation__result() | get | 取页面操作结果 |
| api/assistant/session/list | session__list() | any |  |
| api/assistant/session/save | session__save() | any |  |
| api/assistant/session/delete | session__delete() | any |  |

## complaint（ComplaintController）

| URL | 方法名 | HTTP | 说明 |
| --- | --- | --- | --- |
| api/complaint/lists | lists() | get | 获取举报投诉列表 |
| api/complaint/submit | submit() | post | 举报投诉 |
| api/complaint/action | action() | post | 举报投诉 - 操作 |

## search（SearchController）

| URL | 方法名 | HTTP | 说明 |
| --- | --- | --- | --- |
| api/search/contact | contact() | get | 搜索联系人 |
| api/search/project | project() | get | 搜索项目 |
| api/search/task | task() | get | 搜索任务 |
| api/search/file | file() | get | 搜索文件 |
| api/search/message | message() | get | 搜索消息 |

## apps（AppsController）

| URL | 方法名 | HTTP | 说明 |
| --- | --- | --- | --- |
| api/apps/badge/set | badge__set() | post | 设置角标（应用密钥鉴权） |
| api/apps/badge/clear | badge__clear() | post | 清除角标（当前用户 token 鉴权） |
| api/apps/badge/list | badge__list() | get | 拉取自己全部角标 |

## test（TestController）

| URL | 方法名 | HTTP | 说明 |
| --- | --- | --- | --- |
