# Install (Docker)

English | **[中文文档](./README_CN.md)**

- [Screenshot preview](./README_PREVIEW.md)
- [Demo site](http://www.dootask.com/)

**QQ Group**

Group No.: `546574618`

## Setup

- `Docker v20.10+` & `Docker Compose v2.0+` must be installed
- System: `Centos/Debian/Ubuntu/macOS/Windows`
- Hardware suggestion: 2 cores and above 4G memory
- Special note: Windows users please use `git bash` or `cmder` to run the command

### Deployment (Pro Edition)

```bash
# 1、Clone the repository

# Clone projects on github
git clone -b pro --depth=1 https://github.com/kuaifan/dootask.git
# Or you can use gitee
git clone -b pro --depth=1 https://gitee.com/aipaw/dootask.git

# 2、Enter directory
cd dootask

# 3、Installation（Custom port installation, as: ./cmd install --port 80）
./cmd install
```

### Reset password

```bash
# Reset default account password
./cmd repassword
```

### Change port

```bash
# This method only replaces the HTTP port. To replace the HTTPS port, please read the SSL configuration below
./cmd port 80
```

### Change App Url

```bash
# This URL only affects the email reply.
./cmd url {Your domain url}

# example:
./cmd url https://domain.com
```

### Stop server

```bash
./cmd stop

# P.S: Once application is set up, whenever you want to start the server (if it is stopped) run below command
./cmd start
```

### Development compilation

- `NodeJs 20+` must be installed

```bash
# Development
./cmd dev
   
# Production (This is web client. For App/PC/Mac clients, Please read README-CLIENT.md)
./cmd prod  
```

### Shortcuts for running command

```bash
# You can do this using the following command
./cmd artisan "your command"          # To run a artisan command
./cmd php "your command"              # To run a php command
./cmd nginx "your command"            # To run a nginx command
./cmd redis "your command"            # To run a redis command
./cmd composer "your command"         # To run a composer command
./cmd supervisorctl "your command"    # To run a supervisorctl command
./cmd mysql "your command"            # To run a mysql command (backup: Backup database, recovery: Restore database)
```

### SSL configuration

#### Method 1: Automatic configuration

```bash 
# Running commands in a project
./cmd https
```

#### Or Method 2: Nginx Agent Configuration

```bash 
# 1、Nginx config add
proxy_set_header X-Forwarded-Host $http_host;
proxy_set_header X-Forwarded-Proto $scheme;
proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;

# 2、Running commands in a project (If you unconfigure the NGINX agent, run: ./cmd https close)
./cmd https agent
```

## Upgrade

**Note: Please back up your data before upgrading!**

```bash
# Method 1: Running commands in a project
./cmd update

# Or method 2: use this method if method 1 fails
git pull
./cmd mysql backup
./cmd uninstall
./cmd install
./cmd mysql recovery
```

* Please try again if the upgrade fails across a large version.
* If 502 after the upgrade please run `./cmd restart` restart the service.

## Transfer

Follow these steps to complete the project migration after the new project is installed:

1. Backup original database

```bash
# Run command under old project
./cmd mysql backup
```

2. Copy `database backup file` and `public/uploads` directory to the new project.

3. Restore database to new project
```bash
# Run command under new project
./cmd mysql recovery
```

## Uninstall

```bash
# Running commands in a project
./cmd uninstall
```
