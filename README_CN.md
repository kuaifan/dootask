# DooTask - 开源任务管理系统

**[English](./README.md)** | 中文文档

- [截图预览](./README_PREVIEW.md)
- [演示站点](http://www.dootask.com/)

**QQ交流群**

- QQ群号: `546574618`

## 📍 0.x 迁移到 1.x

- 升级时请务必备份好数据！
- 如果升级失败请尝试执行 `./cmd update` 重试几次。
- 如果升级中出现 `没有找到 xxx 容器` 的提示，请运行 `./cmd reup` 后再执行 `./cmd update`。
- 如果升级后出现502错误请运行 `./cmd reup` 重启服务即可。
- 如果升级后出现 `应用「xxx」未安装` 的提示，请使用管理员账号进入应用商店安装相关应用。

## 安装程序

- 必须安装：`Docker v20.10+` 和 `Docker Compose v2.0+`
- 支持环境：`Centos/Debian/Ubuntu/macOS` 等 linux/unix 系统
- 硬件建议：2核4G以上
- 特别说明：Windows 可以使用 WSL2 安装 Linux 环境后再安装 DooTask。

### 部署项目

```bash
# 1、克隆项目到您的本地或服务器

# 通过github克隆项目
git clone --depth=1 https://github.com/kuaifan/dootask.git
# 或者你也可以使用gitee
git clone --depth=1 https://gitee.com/aipaw/dootask.git

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

### 停止服务

```bash
./cmd down
```

### 启动服务

```bash
./cmd up
```

### 开发编译

请确保你已经安装了 `NodeJs 20+`

```bash
# 开发模式
./cmd dev
   
# 编译项目（这是网页端的，客户端请参考“.github/workflows/publish.yml”文件）
./cmd prod  
```

### SSL 配置

#### 方法1：自动配置

```bash 
# 执行指令，根据提示执行即可
./cmd https
```

#### 方法2：Nginx 代理配置

```bash 
# 1、Nginx 代理配置添加
proxy_set_header X-Forwarded-Host $http_host;
proxy_set_header X-Forwarded-Proto $scheme;
proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;

# 2、执行指令（如果取消 Nginx 代理配置请运行：./cmd https close）
./cmd https agent
```

## 升级更新

**注意：在升级之前请备份好你的数据！**

```bash
./cmd update
```

* 跨越大版本升级失败时请重试执行一次。
* 如果升级后出现502请运行 `./cmd reup` 重启服务即可。

## 迁移项目

在新项目安装好之后按照以下步骤完成项目迁移：

1、备份原数据库

```bash
# 在旧的项目下执行指令
./cmd mysql backup
```

2、将旧项目以下文件和目录拷贝至新项目同路径位置

 - `数据库备份文件`
 - `docker/appstore`
 - `public/uploads`

3、还原数据库至新项目
```bash
# 在新的项目下执行指令
./cmd mysql recovery
```

## 卸载项目

```bash
./cmd uninstall
```

### 更多指令

```bash
./cmd help
```
