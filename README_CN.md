# Install (Docker)

**[English](./README.md)** | 中文文档

- [截图预览](README_PREVIEW.md)
- [演示站点](http://www.dootask.com/)

## 安装程序

> 必须安装 `Docker` 和 `Docker Compose`


### 部署项目

```bash
# 1、克隆项目到您的本地或服务器

# 通过github克隆项目
git clone https://github.com/kuaifan/dootask.git
# 或者你也可以使用gitee
git clone https://gitee.com/aipaw/dootask.git

# 2、进入目录
cd dootask

# 3、一键构建项目
./cmd install
```
安装完毕，项目地址为：**`http://IP:PORT`**（`PORT`默认为`2222`）。

### 默认账号

```text
account: admin@dootask.com
password: 123456
```

### 更换端口

```bash
./cmd php bin/run --port=2222
./cmd up -d
```

### 停止服务

```bash
./cmd stop

# 一旦应用程序被设置，无论何时你想要启动服务器(如果它被停止)运行以下命令
./cmd start
```

### 运行命令的快捷方式

```bash
# 你可以使用以下命令来执行
./cmd artisan "your command"          // 运行 artisan 命令
./cmd php "your command"              // 运行 php 命令
./cmd composer "your command"         // 运行 composer 命令
./cmd supervisorctl "your command"    // 运行 supervisorctl 命令
./cmd test "your command"             // 运行 phpunit 命令
./cmd mysql "your command"            // 运行 mysql 命令 (backup: 备份数据库，recovery: 还原数据库)
```

### 代理开启 HTTPS
``` 
// .env 文件添加
APP_SCHEME=1

// nginx 代理配置添加
proxy_set_header X-Forwarded-Proto $scheme;
```

## 升级更新

**注意：在升级之前请备份好你的数据！**

```bash
# 方法1：进入项目所在目录，运行一下命令即可
./cmd update

# （或者）方法2：如果方法1失败请使用此方法
git pull
./cmd mysql backup
./cmd uninstall
./cmd install
./cmd mysql recovery
```

## 卸载项目

```bash
# 进入项目所在目录，运行一下命令即可
./cmd uninstall
```
