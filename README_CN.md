# DooTask - 开源任务管理系统

**[English](./README.md)** | 中文文档

- [截图预览](./README_PREVIEW.md)
- [演示站点](http://www.dootask.com/)

**QQ交流群**

- QQ群号: `546574618`

## 安装程序

- 必须安装：`Docker v20.10+` 和 `Docker Compose v2.0+`
- 支持环境：`Centos/Debian/Ubuntu/macOS` 等 linux/unix 系统
- 硬件建议：2核4G以上
- 数据库：MariaDB（默认 Docker Compose 中的 `mariadb` 服务）
- 特别说明：Windows 可以使用 WSL2 安装 Linux 环境后再安装 DooTask。

### 部署项目

**方式一：一键脚本（推荐）**

在空目录中执行即自动克隆并安装；在已安装目录中执行则自动检查并升级：

```bash
curl -fsSL https://raw.githubusercontent.com/kuaifan/dootask/pro/bin/install | bash
```

**方式二：手动部署**

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

推荐使用一键脚本升级（在已安装目录中执行，自动拉取最新代码并完成升级，无需重复执行）：

```bash
curl -fsSL https://raw.githubusercontent.com/kuaifan/dootask/pro/bin/install | bash
```

或使用本地命令：

```bash
./cmd update
```

* 如果升级后出现502请运行 `./cmd reup` 重启服务即可。

## 迁移项目

在新项目安装好之后按照以下步骤完成项目迁移：

1、备份 MariaDB 数据库

```bash
# 在旧的项目下执行指令
./cmd mysql backup
```

> `./cmd mysql` 为 CLI 子命令名称，实际操作的是 MariaDB 容器。

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
