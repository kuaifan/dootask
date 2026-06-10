---
id: electron-client.uninstall.howto
title: 卸载桌面端
type: howto
feature: electron-client
scope: end-user
locale: zh
aliases:
  - 卸载桌面端
  - 卸载客户端
  - 删除 DooTask
  - 彻底删除
  - 清理桌面端
  - 重装前清理
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 标准卸载不会删除用户数据(配置、登录态);需手动清理
  - 卸载不会注销服务器侧登录,需要在网页端 / 移动端手动登出
  - 服务器上的项目、任务、聊天数据与本地无关,不会被卸载影响
last_verified: v1.7.90
---

# 卸载桌面端

## 入口与步骤

### macOS
1. 退出 DooTask(菜单 → 「DooTask」→「退出」或 Cmd + Q)
2. 打开 Finder → 「应用程序」
3. 找到 DooTask 拖到「废纸篓」
4. 清空废纸篓

### Windows
1. 退出 DooTask(托盘右键 → 退出)
2. 「设置」→「应用」→「应用和功能」(或控制面板 → 程序与功能)
3. 列表找到 DooTask → 「卸载」
4. 跟随卸载向导完成

### Linux(deb)
```bash
sudo apt remove dootask
# 或同时清掉配置
sudo apt purge dootask
```

### Linux(rpm)
```bash
sudo rpm -e dootask
```

## 清理残留数据
默认卸载不会删除以下个人数据,如需彻底清理需手动删:

- **macOS**:
  - 配置:`~/Library/Application Support/DooTask/`
  - 日志:`~/Library/Logs/DooTask/`
  - 缓存:`~/Library/Caches/DooTask/`
- **Windows**:
  - 配置 / 日志:`%USERPROFILE%\AppData\Roaming\DooTask\`
  - 缓存:`%USERPROFILE%\AppData\Local\DooTask\`
- **Linux**:
  - 配置:`~/.config/DooTask/`
  - 缓存:`~/.cache/DooTask/`

## 卸载前是否需要注销
**建议先注销**:在客户端内手动点头像 → 退出登录,再卸载。否则:
- 服务器侧仍记录该设备的登录令牌(过期前可被攻击者复用)
- 推送服务可能继续向该设备投递,造成路由失败日志

## 重装提示
- 卸载后保留数据再重装,会自动恢复登录态、设置
- 完全干净重装请按上面「清理残留数据」清空目录

## 不支持
- 不支持「一键卸载并清理所有数据」按钮
- 不支持「卸载时自动注销服务器登录」(请提前手动)
- 不支持远程拉黑客户端(只能在管理员后台禁用账号)
