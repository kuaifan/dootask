# Install (Docker)

**[English](./README.md)** | 中文文档

- [截图预览](./README_PREVIEW.md)
- [演示站点](http://www.dootask.com/)

**QQ交流群**

- QQ群号: `546574618`

## 安装程序

- 必须安装：`Docker v20.10+` 和 `Docker Compose v2.0+`
- 支持环境：`Centos/Debian/Ubuntu/macOS/Windows`
- 硬件建议：2核4G以上
- 特别说明：Windows 用户请使用 `git bash` 或者 `cmder` 运行命令

### 部署项目（Pro版）

```bash
# 1、克隆项目到您的本地或服务器

# 通过github克隆项目
git clone -b pro --depth=1 https://github.com/kuaifan/dootask.git
# 或者你也可以使用gitee
git clone -b pro --depth=1 https://gitee.com/aipaw/dootask.git

# 2、进入目录
cd dootask

# 3、一键安装项目（自定义端口安装，如：./cmd install --port 80）
./cmd install
```

### 重置密码

```bash
# 重置默认管理员密码
./cmd repassword
```

### 更换端口

```bash
# 此方法仅更换http端口，更换https端口请阅读下面SSL配置
./cmd port 80
```

### 更换URL

```bash
# 此地址仅影响邮件回复功能
./cmd url {域名地址}

# 例如:
./cmd url https://domain.com
```

### 停止服务

```bash
./cmd stop

# 一旦应用程序被设置，无论何时你想要启动服务器(如果它被停止)运行以下命令
./cmd start
```

### 开发编译

- 请确保你已经安装了 `NodeJs 20+`

```bash
# 开发模式
./cmd dev
   
# 编译项目（这是网页端的，App/Pc/Mac客户端请查看 README_CLIENT.md）
./cmd prod  
```


### 运行命令的快捷方式

```bash
# 你可以使用以下命令来执行
./cmd artisan "your command"          # 运行 artisan 命令
./cmd php "your command"              # 运行 php 命令
./cmd nginx "your command"            # 运行 nginx 命令
./cmd redis "your command"            # 运行 redis 命令
./cmd composer "your command"         # 运行 composer 命令
./cmd supervisorctl "your command"    # 运行 supervisorctl 命令
./cmd mysql "your command"            # 运行 mysql 命令 (backup: 备份数据库，recovery: 还原数据库)
```

### SSL 配置

#### 方法1：自动配置

```bash 
# 在项目下运行命令，根据提示执行即可
./cmd https
```

#### （或者）方法2：Nginx 代理配置

```bash 
# 1、Nginx 代理配置添加
proxy_set_header X-Forwarded-Host $http_host;
proxy_set_header X-Forwarded-Proto $scheme;
proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;

# 2、在项目下运行命令（如果取消 Nginx 代理配置请运行：./cmd https close）
./cmd https agent
```

## 升级更新

**注意：在升级之前请备份好你的数据！**

```bash
# 方法1：在项目下运行命令
./cmd update

# （或者）方法2：如果方法1失败请使用此方法
git pull
./cmd mysql backup
./cmd uninstall
./cmd install
./cmd mysql recovery
```

* 跨越大版本升级失败时请重试执行一次。
* 如果升级后出现502请运行 `./cmd restart` 重启服务即可。

## 迁移项目

在新项目安装好之后按照以下步骤完成项目迁移：

1、备份原数据库

```bash
# 在旧的项目下运行命令
./cmd mysql backup
```

2、将`数据库备份文件`及`public/uploads`目录拷贝至新项目

3、还原数据库至新项目
```bash
# 在新的项目下运行命令
./cmd mysql recovery
```

## 卸载项目

```bash
# 在项目下运行命令
./cmd uninstall
```
