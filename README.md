# DooTask - Open Source Task Management System

English | **[中文文档](./README_CN.md)**

- [Screenshot Preview](./README_PREVIEW.md)
- [Demo Site](http://www.dootask.com/)

**QQ Group**

- Group Number: `546574618`

## Installation Requirements

- Required: `Docker v20.10+` and `Docker Compose v2.0+`
- Supported Systems: `CentOS/Debian/Ubuntu/macOS` and other Linux/Unix systems
- Hardware Recommendation: 2+ cores, 4GB+ memory
- Database: MariaDB (provided by the default Docker Compose `mariadb` service)
- Special Note: Windows users can install Linux environment using WSL2 before installing DooTask.

### Deploy Project

**Option 1: One-line script (recommended)**

Run it in an empty directory to clone and install automatically; run it inside an existing installation to check and upgrade:

```bash
curl -fsSL https://raw.githubusercontent.com/kuaifan/dootask/pro/bin/install | bash
```

**Option 2: Manual deployment**

```bash
# 1、Clone the project to your local machine or server

# Clone project from GitHub
git clone --depth=1 https://github.com/kuaifan/dootask.git
# Or you can use Gitee
git clone --depth=1 https://gitee.com/aipaw/dootask.git

# 2、Enter directory
cd dootask

# 3、One-click installation (Custom port installation: ./cmd install --port 80)
./cmd install
```

### Reset Password

```bash
# Reset default administrator password
./cmd repassword
```

### Change Port

```bash
# This method only changes HTTP port. For HTTPS port, please read SSL configuration below
./cmd port 80
```

### Stop Service

```bash
./cmd down
```

### Start Service

```bash
./cmd up
```

### Development & Build

Please ensure you have installed `NodeJs 20+`

```bash
# Development mode
./cmd dev
   
# Build project (This is for web client. For desktop apps, refer to ".github/workflows/publish.yml")
./cmd prod  
```

### SSL Configuration

#### Method 1: Automatic Configuration

```bash 
# Run command and follow the prompts
./cmd https
```

#### Method 2: Nginx Proxy Configuration

```bash 
# 1、Add Nginx proxy configuration
proxy_set_header X-Forwarded-Host $http_host;
proxy_set_header X-Forwarded-Proto $scheme;
proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;

# 2、Run command (To cancel Nginx proxy configuration: ./cmd https close)
./cmd https agent
```

## Upgrade & Update

**Note: Please backup your data before upgrading!**

Recommended: use the one-line script (run it inside an existing installation; it pulls the latest code and finishes the upgrade in a single run):

```bash
curl -fsSL https://raw.githubusercontent.com/kuaifan/dootask/pro/bin/install | bash
```

Or use the local command:

```bash
./cmd update
```

* If you encounter 502 errors after upgrade, run `./cmd reup` to restart services.

## Project Migration

After installing the new project, follow these steps to complete migration:

1、Backup the MariaDB database

```bash
# Run command in the old project
./cmd mysql backup
```

> `./cmd mysql` is the CLI subcommand name; backups run against the MariaDB container.

2、Copy the following files and directories from old project to the same paths in new project

 - `Database backup file`
 - `docker/appstore`
 - `public/uploads`

3、Restore database to new project
```bash
# Run command in the new project
./cmd mysql recovery
```

## Uninstall Project

```bash
./cmd uninstall
```

### More Commands

```bash
./cmd help
```
