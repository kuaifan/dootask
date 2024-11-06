const fs = require('fs');
const path = require("path");
const exec = require('child_process').exec;
const packageFile = path.resolve(process.cwd(), "package.json");
const changeFile = path.resolve(process.cwd(), "CHANGELOG.md");
const changeCross = "## [0.25.39]\n\n### Bug Fixes\n\n- 无法浏览表情图片\n\n### Features\n\n- 更多emoji表情回复\n\n### Performance\n\n- 优化录音效果\n- 优化缓存\n- 优化语音播放\n\n## [0.25.11]\n\n### Performance\n\n- 优化输入草稿\n- 搜索会员默认机器人排在最后\n\n## [0.25.7]\n\n### Bug Fixes\n\n- 操作菜单导致的页面错位\n\n## [0.25.0]\n\n### Bug Fixes\n\n- 列表模式下重命名文件名称导致其他文件重命名的情况\n- 桌面端开启子窗口消息数倍数增长的问题\n\n### Performance\n\n- 优化emoji表情分类\n- Android表情输入框跟键盘同时出现的情况\n- 检测文件名称包含特殊字符\n- 通过#发送任务显示项目内已完成任务\n- 优化通讯录数量\n- 优化token到期时间\n- 管理员通讯录显示新帐号\n- 管理员可以移除全员群人员\n- 优化图片浏览\n\n## [0.24.85]\n\n### Bug Fixes\n\n- 安装时机器人也进入全员群\n- 移出任务后还在项目里但看不到任务\n\n### Performance\n\n- 网络不好时发送消息顺序问题\n- 移动端搜索消息支持滑动取消搜索\n- 优化 doo 模块\n- 新增doo模块\n- 新增doo模块\n- 新增doo模块\n- 优化数据删除\n\n## [0.24.58]\n\n### Bug Fixes\n\n- 客户端无法关闭窗口的情况\n- 仅显示我的文件时无法创建文件\n- 重命名别人共享的文件后不见了\n\n### Features\n\n- 搜索会话消息\n\n### Performance\n\n- 新增管理机器人菜单\n- 整理请求外部接口\n- 可以通过ID搜索任务\n- 会员选择框支持搜索拼音\n- 文件消息新增显示文件菜单\n- 优化链接识别\n- 隐藏共享文件改为仅显示我的\n- 文件名称显示两行\n- 子任务允许多个负责人\n- 优化搜索\n- 搜索消息禁止右键\n- 重写项目和会话接口数据\n- 重写更新和删除方法\n- 优化数据同步\n\n## [0.24.30]\n\n### Bug Fixes\n\n- 思维导图快捷键保存\n\n### Features\n\n- 支持发送匿名消息\n\n### Performance\n\n- 优化表情回复\n- 消息快捷发送菜单\n- 优化消息类型分类\n- 文件列表支持隐藏共享文件\n- 群组支持修改头像\n- 优化阅读消息\n- 点击会话消息头像@\n- 通讯录显示部门负责人\n- 加载更多消息safari兼容性\n- 优化签到数据\n- 点击头像进入对话\n- 优化开发执行脚本\n- 网络恢复后重新标记已读失败的信息\n- Dialog loading\n- 优化再次点击抖动\n- 优化ipad表单显示\n- 工作包括周报模板添加下周拟定计划项\n\n## [0.23.86]\n\n### Bug Fixes\n\n- App cross domain\n- 桌面端已知bug\n- 从任务窗口发送聊天输入缓存的问题\n- 桌面端新窗口打开任务无法发起聊天的问题\n- Ldap一处报错\n\n### Performance\n\n- 优化文件分享链接\n- 非工作日不推送签到提醒\n- 设置免打扰后被@也推送通知\n- 任务完成通知流程状态\n- 优化会话列表数据加载\n\n## [0.23.62]\n\n### Bug Fixes\n\n- 没有后缀名无法下载文件的问题\n- 打卡提醒失效\n- 移动端在任务提醒打开任务无法聊天的问题\n\n### Performance\n\n- 优化ws连接机制\n- Office、图表、文本国际化\n- 优化移动端设置\n- 再次点击消息图标闪动未读对话\n- 优化已读标记\n- 移动端优化\n- 消息接口支持@邮箱\n- 支持gitpod\n\n## [0.23.46]\n\n### Bug Fixes\n\n- 编辑消息@丢失的问题\n- 修复已知bug\n\n### Features\n\n- 新增临时帐号功能\n\n### Performance\n\n- 兼容ipad app样式\n- 添加上班签到提醒消息\n- 完善临时帐号权限\n- 设待办快速选择人员\n- 优化消息数量显示\n- 优化阅读消息\n- 对话顶部提示\n- 样式兼容\n- 优化api国际化\n- 优化国际化\n- 优化ws重连规则\n- 优化翻译\n- 优化更新日志生成\n- 优化首页\n\n## [0.22.99]\n\n### Bug Fixes\n\n- 移动端应用内通知标题溢出的问题\n\n### Performance\n\n- 机器人支持webhook\n- 优化输入框功能提示\n- 优化任务修改时间通知\n- 导出所有超期任务\n\n## [0.22.88]\n\n### Bug Fixes\n\n- LDAP Exception\n\n### Features\n\n- 二维码登录\n\n### Performance\n\n- 优化删除数据\n\n## [0.22.84]\n\n### Bug Fixes\n\n- 栏目内添加任务应该直接归属此栏目\n\n### Features\n\n- 新增ldap帐号\n\n### Performance\n\n- 优化会话删除\n- 优化表情输入\n- 优化根据会员筛选任务\n- Drawio文件支持导出pdf文件\n- 优化任务提醒\n- 优化state数据结构\n- 聊天设置待办时可快速选择\n- 完善LDAP\n- 优化移动端（pad）\n- 优化消息列表数据\n\n## [0.22.66]\n\n### Bug Fixes\n\n- 任务首次发消息消失的情况\n\n### Features\n\n- 项目面板支持根据成员筛选任务\n\n### Performance\n\n- 优化消息对话框loading\n- 优化未读消息提示\n- 优化移动端打开会话\n- 回复/引用机器人消息图标移位的问题\n- 会话顶部提示剩余未读消息\n- 角标最大显示999\n\n## [0.22.56]\n\n### Bug Fixes\n\n- 导出签到最多只导出20个的问题\n- 截图快捷键的报错\n- 跨月签到记录不显示的问题\n\n### Features\n\n- 上班打开每日开心/下班打卡心灵鸡汤\n- 上班打卡新增每日开心\n\n### Performance\n\n- 导出签到/任务统计名字新增序号\n- 解决桌面端跨域cookie无法携带的问题\n\n## [0.22.46]\n\n### Bug Fixes\n\n- 跨日/周写工作报告导致的覆盖问题\n\n### Performance\n\n- 优化查看汇报详情loading\n- 我的工作汇报列表显示汇报对象\n- 工作汇报可留空汇报对象\n- 优化签到打卡提醒\n- 升级office套件\n\n## [0.22.40]\n\n### Bug Fixes\n\n- 时间快选\n\n### Performance\n\n- 优化签到通知\n- 任务时间修改提醒\n- 优化导出快速选择\n- 优化修改员工mac地址备注\n- 工作报告模板新增项目名称\n- 优化修改文件名称相同的情况\n- 优化任务APP/邮件提醒\n- 优化主题跟随系统\n- 优化对话列表加载速度\n\n## [0.22.22]\n\n### Bug Fixes\n\n- 聊天页面出现滚动溢出的问题\n- 会话置顶失效\n- 标记已读失败\n- 因机器人首次安装失败\n\n### Features\n\n- 免打扰会话取消邮件通知\n- 免打扰导致推送角标数量不对\n- 消息会话支持免打扰\n\n### Performance\n\n- 签到成功通知\n- 优化引用机器人消息看不到机器人图标的问题\n- 导出签到/任务统计会员数增加到最多可选100个\n- 会员选择支持全选列表\n- 个人签到设置显示最近签到数据\n- 定时清理异步任务记录\n- 聊天消息长大超过5000转文件发送\n\n## [0.22.0]\n\n### Bug Fixes\n\n- 下载文件出现文件损坏的情况\n- 清空已完成上传列表\n\n### Features\n\n- 新增机器人\n\n### Performance\n\n- 机器人支持静默推送\n- 优化签到数据\n\n## [0.21.96]\n\n### Performance\n\n- 优化签到数据结构\n\n## [0.21.90]\n\n### Features\n\n- 签到功能\n\n### Performance\n\n- Mac地址已存在检查\n- 限制截图快捷键\n- 查看我自己的签到数据\n- 优化导出签到\n- Update office manifest\n- 优化打开个人会话速度\n- 优化任务超时提醒文案\n- 优化导出统计\n- 完善签到功能\n- 完善签到功能\n- 优化缓存\n- 优化缓存\n- 缓存迁移\n- 优化本地数据\n\n## [0.21.68]\n\n### Bug Fixes\n\n- 客户端打开出现报错\n- 客户端提交截图空格报错的问题\n- 上传文件没有读取权限\n\n### Features\n\n- 添加考勤接口\n\n### Performance\n\n- 文件右键菜单直接发送至会话\n- 优化删除或归档项目后数量更新\n- 消息搜索支持会员结果\n- 优化设置菜单\n- 取消universal版本编译\n- 优化客户端通知，Mac支持快速回复\n- 群聊天点击头像进入个人对话\n- 优化会话保留\n- 修改搜索成员文案\n- 聊天和文件模块不限制上传类型\n- 消息列表进行搜索时，条件过长，显示的无结果文案无法完全显示\n- 优化全局表格滚动条\n\n## [0.21.42]\n\n### Performance\n\n- 优化滚动条\n- 优化消息数量\n- 优化网络错误提示框\n- 网络错误不清空仪表盘数据\n\n## [0.21.32]\n\n### Performance\n\n- 优化消息&amp;符号\n- 优化移动端网络错误提示\n\n## [0.21.26]\n\n### Bug Fixes\n\n- 回复数量增长错误的问题\n\n### Performance\n\n- 客户端新增截图快捷键\n- 截图dev\n- 优化国际化提升访问速度\n\n## [0.21.15]\n\n### Bug Fixes\n\n- Safari浏览器兼容性\n- 对话窗口js报错\n\n### Performance\n\n- 添加小兔子工作中表情\n\n## [0.21.7]\n\n### Bug Fixes\n\n- 链接消息处理问题\n\n### Performance\n\n- 头像标签部门过长显示优化\n- 聊天使用~符号分享文件\n- 修改任务时间添加备注\n\n## [0.21.0]\n\n### Bug Fixes\n\n- 转让群主后窗口不关闭的问题\n- 通知消息显示UserAvatar\n\n### Performance\n\n- @结果相同时避免刷新\n- 离职移交部门\n- 离职后退出所有群\n- 升级onlyoffice\n\n## [0.20.90]\n\n### Features\n\n- 新增部门功能\n\n### Performance\n\n- 个人群支持转让群主\n- 头像显示部门\n- 支持选择已有群为创建部门群\n- 优化表情发送后搜索关键词逻辑\n- 优化搜索表情\n- 支持搜索在线表情\n- 完善部门群组功能\n- 选择器的优化\n- Task进程添加执行记录\n\n## [0.20.71]\n\n### Bug Fixes\n\n- 未聊天过的任务无法发送聊天表情\n- 离职仍受到推送的问题\n- 任务详情无法右键的问题\n\n### Features\n\n- 聊天支持联想表情\n\n### Performance\n\n- 优化编辑器对象销毁的问题\n\n## [0.20.65]\n\n### Bug Fixes\n\n- Android进入会议没有声音的问题\n- IOS点击发送图片表情偶尔不显示的情况\n\n### Performance\n\n- 优化会议聊天\n- Win通知标题\n- 主窗口可以单独关闭到后台\n- 会议支持最小化窗口\n- 优化录音、优化会议\n\n## [0.20.51]\n\n### Bug Fixes\n\n- 搜索文件选择在上层文件夹中显示时如果已经当前文件夹时没有反应的问题\n- 离职员工仍可以接收到邮件的问题\n- 首次聊天因网络问题聊天记录清空的情况\n- 编译已发送的消息中含有任务信息时的未定义问题\n- 新安装出现无法打开其他人员会话的问题\n\n### Features\n\n- 新增任务过期app推送提醒\n\n### Performance\n\n- 优化客户端图片浏览器\n- 聊天内容图片支持下载\n- 优化隐私政策弹窗\n- 自己可以转为任务协助人员\n- 升级element/view-design\n- 优化任务队列\n\n## [0.20.35]\n\n### Bug Fixes\n\n- Umeng mi push\n\n### Performance\n\n- 升级election框架\n\n## [0.20.23]\n\n### Performance\n\n- 搜索排序\n\n## [0.20.20]\n\n### Features\n\n- 工作报告支持批量标记已读\n\n### Performance\n\n- 操作离职隐藏退出群通知\n- 调整文件表格列表重命名输入框尺寸\n- 群内鼠标悬停成员头像显示聊天按钮\n- 优化消息已读\n- 文件分享链接显示文件名称\n\n## [0.20.12]\n\n### Performance\n\n- 优化通知\n\n## [0.20.5]\n\n### Performance\n\n- 优化聊天页面cpu占用\n\n## [0.20.3]\n\n### Bug Fixes\n\n- 聊天、任务中的md文件预览无法滚动\n- 修改工作报告弹出多次成功提示的问题\n- 安装数据库初始化失败\n- 消息已读\n\n### Features\n\n- Window客户端任务栏闪烁\n\n### Performance\n\n- 升级框架内核\n- 优化消息发送失败\n\n## [0.19.95]\n\n### Bug Fixes\n\n- 无法添加任务的问题\n\n### Features\n\n- 消息粘贴excel内容自动转成图片\n\n### Performance\n\n- 优化发送图片出现空白的情况\n- 消息发送失败支持再次编辑\n- 对话支持拼音搜索\n- 新增注册自动进入全员群开关\n- 移动客户端群消息通知加上群名称\n- 消息菜单新增复制图片、链接功能\n\n## [0.19.75]\n\n### Bug Fixes\n\n- 无法下载转发文件的问题\n- 无法操作离职的问题\n- 编辑@消息的问题\n- 删除账号-提示文案修改\n- 删除账号-提示文案修改\n- 修改邮箱-”发送验证码“倒计时未结束修改\n- 删除账户必填加星号；邮箱验证码可以多发送\n- 修改/删除账号接口无权限问题修改；根据env文件'SYSTEM_SETTING'变量判断是否能修改/删除账号\n\n### Performance\n\n- 按录音时停止正在播放的\n- 优化消息列表\n- 优化移除群成员与打开成员对话冲突的情况\n- 优化国际化\n- 优化删除成员\n- 优化编辑带有图片的消息\n- 支持搜索共享文件\n- 优化发消息时有时候出现空白需要滚动才出现内容的情况\n\n## [0.19.40]\n\n### Bug Fixes\n\n- 音频/视频都不选时无法进入会议的情况\n- 修改邮箱-校验邮箱去掉前后空格\n\n### Features\n\n- 新增删除账户功能\n- 新增修改邮箱功能\n\n## [0.19.26]\n\n### Bug Fixes\n\n- 待办数量与实际的数量不一致\n\n### Performance\n\n- 角标提示待办跟@一起\n- 移动端任务打开聊天按钮优化\n- 支持转发给最近聊天\n- 可以通过群成员点击打开对话\n- 展示消息回应详情\n- 优化通知类消息字符长度\n- 去除通知里的&nbsp;\n\n## [0.19.10]\n\n### Bug Fixes\n\n- 任务列表无法修改优先级的问题\n\n### Performance\n\n- 客户端窗口激活自动获取聊天焦点\n- 个人对话窗口支持拨打电话\n- 新增联系电话\n- [notice|tag|todo]类型的消息静默推送\n- 只给一个月内登录App的帐号推送\n- 显示待办消息数量\n- 待办消息支持指定成员\n- 支持查看待办完成情况\n\n## [0.18.80]\n\n### Bug Fixes\n\n- 任务详情不出现聊天的情况\n\n### Features\n\n- 新增待办消息功能\n\n### Performance\n\n- 优化抖动提示\n- 消息新增#我协助的任务\n\n## [0.18.71]\n\n### Bug Fixes\n\n- 移动文件夹内文件所有者不变的问题\n- 通知消息一直未读的情况\n\n### Performance\n\n- 回复或修改消息发送时立即隐藏引用显示\n- 搜索对话可以搜索远程的对话\n\n## [0.18.58]\n\n### Bug Fixes\n\n- 移动文件所有者错误\n- 无法通过项目点击聊天的情况\n\n### Features\n\n- 新增全员群组\n- 支持编辑已发送的消息\n\n### Performance\n\n- 所有项目列表支持筛选个人项目\n- 调整消息标签位置\n- 添加邮件忽略功能\n\n## [0.18.44]\n\n### Bug Fixes\n\n- 任务聊天出现空白的情况\n- 新建文件不显示的问题\n\n### Features\n\n- 新增消息类型筛选\n- 新增标注消息功能\n\n### Performance\n\n- 优化协助任务的更新\n- 管理员新增修改成员邮箱功能\n\n## [0.18.22]\n\n### Bug Fixes\n\n- 输入框粘贴后出错的问题\n- 任务重复周期\n\n### Performance\n\n- 支持查看回复列表\n- 优化消息分页加载\n- 添加消息回复量\n\n## [0.18.1]\n\n### Features\n\n- 新增任务重复周期\n\n### Performance\n\n- 仪表盘列表新增显示协助的任务\n\n## [0.17.98]\n\n### Bug Fixes\n\n- 任务成员应该禁止退出任务群聊\n- 撤回消息导致未读数错误的问题\n- 部分长按菜单移位的问题\n- 无法点击图片预览的问题\n\n### Features\n\n- 支持通过拼音搜索联系人\n\n### Performance\n\n- 优化@其他成员在线状态\n- 仅(群聊)且(是群主或没有群主)才可以@成员以外的人\n- 优化pdf浏览方式\n- 支持@群聊以外成员\n- 项目群、任务群可添加成员\n\n## [0.17.75]\n\n### Features\n\n- 支持搜索历史消息\n\n### Performance\n\n- 优化文件操作菜单样式\n- 文件浏览支持滑动返回上一个文件夹\n- 桌面客户端出现无法关闭窗口的情况\n- 优化触发回复页面滚动\n- 优化对话详情页\n\n## [0.17.53]\n\n### Features\n\n- 新增回复消息功能\n\n### Performance\n\n- 取消置顶标签\n- 优化移动客户端滚动穿透\n- 优化消息列表\n\n## [0.17.30]\n\n### Performance\n\n- 默认使用文字头像\n- 使用系统浏览器打开新窗口链接\n\n## [0.17.20]\n\n### Bug Fixes\n\n- 员工删除后对话还存在的问题\n\n### Performance\n\n- 优化通讯录刷新机制\n- 通知自动关闭\n- 优化excel菜单\n\n## [0.17.7]\n\n### Performance\n\n- 优化文件重名的问题\n- 优化图片预览缩放\n- 优化预览文件\n- 优化同时加载同个任务\n\n## [0.16.85]\n\n### Bug Fixes\n\n- Win客户端升级签名报错的问题\n- 文件md、text互转时文件格式没有变的问题\n- 移动客户端访问本站链接出现需要登录的情况\n- 不是任务负责人不能通过小窗口发送任务消息的问题\n- 桌面客户端任务独立窗口无法操作任务状态的问题\n\n### Performance\n\n- 优化键盘关闭\n- 优化office右上角菜单按钮重叠的问题\n- 优化录音效果\n- 移动端只读文件\n- 优化任务窗口输入框草稿\n- 头像显示已离职效果\n- 文件文本编辑支持command+s保存\n- 长文本消息的处理\n- 客户端新窗口皮肤不统一的问题\n- 流程图支持搜索远程图标\n\n## [0.16.62]\n\n### Performance\n\n- 升级office插件\n\n## [0.16.60]\n\n### Bug Fixes\n\n- 修复任务窗口无法发送表情的问题\n\n### Performance\n\n- 优化消息已读未读\n- 预览图片尺寸的优化\n- 新窗口打开任务时保持日志显示状态\n- 优化首页加载失败的情况\n- 文字发送太长转成文件发送\n- 任务详情窗口尺寸\n- 优化全局任务操作菜单\n\n## [0.16.22]\n\n### Features\n\n- 新增消息回复表情功能\n\n## [0.15.83]\n\n### Features\n\n- 添加会议功能\n\n### Performance\n\n- 优化移动端图片预览\n- 移动端长按菜单\n\n## [0.15.60]\n\n### Bug Fixes\n\n- 文件共享人数太多内容溢出\n- 聊天内容加载中刷新导致无法再继续加载的情况\n- 对话列表点击任务状态标签无法打开对话\n- 任务弹窗无法发送语音\n- 焦点会超出输入框的情况\n- 获取首字母失败的情况\n\n### Features\n\n- 支持发送录音\n- 对话窗口新增会员最后在线时间\n\n### Performance\n\n- 触摸返回中禁止滚动消息列表\n- 撤回语音消息时停止正在播放\n- 保留粘贴的a标签\n- 支持会话自己\n- 聊天内容链接可点击\n- 优化搜索加载提示\n- 项目-任务状态的数量，实时更新数据\n- 优化聊天窗口样式\n- 移动端聊天窗口返回按钮显示未读信息数\n- 优化加载状态\n- 客户端本地通知\n- 聊天输入框草稿\n- Ws重连后重新获取会员基本信息\n- 聊天窗口样式\n- 优化信息邮件格式\n- 优化移交个人项目\n\n## [0.14.94]\n\n### Bug Fixes\n\n- 邮件通知消息未读对象可能会出错的情况\n\n### Performance\n\n- 优化适配ipad\n- 优化客户端生命周期重连ws机制\n- 优化更新对话列表机制\n- 7天内显示时间m-d H:i\n- 消息也推送给在其它地方登录的自己\n\n## [0.14.72]\n\n### Performance\n\n- 聊天输入框支持粘贴文件\n- 优化UserAvatar组件\n- 上传或发送图片太大时压缩显示\n- 仪表盘任务数量、最近打开的任务\n- 优化消息移动端打开动画效果\n\n## [0.14.62]\n\n### Bug Fixes\n\n- 未读消息邮件头像不显示的问题\n- 修复手机客户端无法预览文件的问题\n- 客户端选择sso登录输入相同地址时提交无反应的问题\n- 推送收到的群组名称为空的情况\n- 任务开始邮件提醒错误的问题\n- Ios键盘遮挡输入框的问题\n\n### Performance\n\n- 未读消息邮件提醒，提醒时把所有未读消息都加上，而不是只提示指定时间的\n- 优化modal内滚动会传播给其他组件的问题\n- 优化任务过多加载卡的情况\n- 点击聊天输入框窗口跳动\n- 支持上传plist格式文件\n- DrawerOverlay 使用 Model\n- 手机客户端登录页优化sso登录样式\n- 优化手机客户端登录页切换主题提示\n- 优化消息列表\n- 优化移动端\n\n## [0.14.8]\n\n### Features\n\n- 邮件通知未读消息\n\n### Performance\n\n- 优化聊天输入框计算样式\n- 优化正则表达式\n\n## [0.13.98]\n\n### Bug Fixes\n\n- 修复共享文件移动到共享文件夹内共享属性错乱的问题\n\n### Performance\n\n- 移交项目和任务时记录被移交对象\n- 共享的文件禁止移动到另一个共享文件夹内\n- 优化自定义sso登录\n\n## [0.13.88]\n\n### Bug Fixes\n\n- 同时删除多个任务负责人或协助人员任务动态显示错误的问题\n- 修复 ETooltip 组件 disabled 取消后错位的问题\n- 添加任务窗口选择其他项目无效的问题\n\n### Performance\n\n- 优化文件历史查看\n- 查看文件修改历史时文本编辑器、图表点击编辑历史窗口不隐藏\n\n## [0.13.78]\n\n### Bug Fixes\n\n- 修复上传文件夹不立即显示的问题\n\n### Performance\n\n- 优化任务详情右键预览图片\n\n## [0.13.74]\n\n### Bug Fixes\n\n- 修复已打开文件需刷新网页才显示最新内容的情况\n\n### Features\n\n- 新增查看文件历史版本\n\n### Performance\n\n- 文件打开保存机制\n- 客户端升级日志\n\n## [0.13.63]\n\n### Bug Fixes\n\n- 修复打开pdf因为文件名内容出错的问题\n\n### Features\n\n- 新增聊天选择内容粗体、斜体、删除线、序号等工具\n\n### Performance\n\n- 发送消息未设置昵称的优化\n- 优化共享文件夹图标\n- 优化重复共享提示\n- 优化聊天窗口群聊已读列表\n- 优化任务窗口\n\n## [0.13.48]\n\n### Performance\n\n- 优化暗黑模式\n- 客户端填写周报后保存关闭窗口\n- 文件浏览保存排序\n\n"

const verOffset = 1250; // 版本号偏移量
const codeOffset = -56; // 代码版本号偏移量

function runExec(command, cb) {
    return new Promise((resolve, reject) => {
        exec(command, function (err, stdout, stderr) {
            if (err != null) {
                reject(err);
            } else if (typeof (stderr) != "string") {
                reject(stderr);
            } else {
                resolve(stdout)
            }
        });
    })
}

function removeDuplicateLines(log) {
    const logs = log.split(/(\n## \[.*?\])/)
    if (logs) {
        log = logs.map(str => {
            const array = [];
            const items = str.split("\n");
            items.some(item => {
                if (/^-/.test(item)) {
                    if (array.indexOf(item) === -1) {
                        array.push(item);
                    }
                } else {
                    array.push(item);
                }
            })
            return array.join("\n");
        }).join('');
    }
    return log;
}

runExec("git rev-list --count HEAD $(git branch | sed -n -e 's/^\* \(.*\)/\1/p')").then(ver => {
    runExec("git tag | wc -l").then(code => {
        const num = verOffset + parseInt(ver)
        if (isNaN(num) || Math.floor(num % 100) < 0) {
            console.error("get version error " + ver);
            return;
        }
        const version = Math.floor(num / 10000) + "." + Math.floor(num % 10000 / 100) + "." + Math.floor(num % 100)
        const codeVerson = codeOffset + parseInt(code)
        //
        let newResult = fs.readFileSync(packageFile, 'utf8')
        newResult = newResult.replace(/"version":\s*"(.*?)"/, `"version": "${version}"`);
        newResult = newResult.replace(/"codeVerson":(.*?)(,|$)/, `"codeVerson": ${codeVerson}$2`);
        fs.writeFileSync(packageFile, newResult, 'utf8');
        //
        console.log("New version: " + version);
        console.log("New code verson: " + codeVerson);
        //
        runExec("docker run -t --rm -v \"$(pwd)\":/app/ orhunp/git-cliff:1.3.0 > CHANGELOG.md").then(_ => {
            if (!fs.existsSync(changeFile)) {
                console.error("Change file does not exist");
                return "";
            }
            let newContent = removeDuplicateLines(fs.readFileSync(changeFile, 'utf8'));
            if (newContent.indexOf("## [Unreleased]") !== -1) {
                newContent = newContent.replace("## [Unreleased]", `## [${version}]`);
            } else {
                newContent = newContent.replace(/## \[(.*?)\]/, `## [${version}]`);
            }
            newContent = newContent.replace("## [0.13.0]", `${changeCross}## [0.13.0]`);
            fs.writeFileSync(changeFile, newContent, 'utf8');
            console.log("Log file: CHANGELOG.md");
        }).catch(err => {
            console.error(err);
        })
    }).catch(err => {
        console.error(err);
    })
}).catch(err => {
    console.error(err);
})
