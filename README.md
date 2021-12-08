# Install (Docker)

English | **[中文文档](./README_CN.md)**

- [Screenshot Preview](README_PREVIEW.md)
- [Demo site](http://www.dootask.com/)

## Setup

> `Docker` & `Docker Compose` must be installed


### Deployment project

```bash
# 1、Clone the repository

# Clone projects on github
git clone https://github.com/kuaifan/dootask.git
# or you can use gitee
git clone https://gitee.com/aipaw/dootask.git

# 2、enter directory
cd dootask

# 3、Build project
./cmd install
```
Installed, project url: **`http://IP:PORT`**（`PORT`Default is`2222`）。

### Default Account

```text
account: admin@dootask.com
password: 123456
```

### Change port

```bash
./cmd php bin/run --port=2222
./cmd up -d
```

### Stop server

```bash
./cmd stop

# P.S: Once application is set up, whenever you want to start the server (if it is stopped) run below command
./cmd start
```

### Shortcuts for running command

```bash
# You can do this using the following command
./cmd artisan "your command"          // To run a artisan command
./cmd php "your command"              // To run a php command
./cmd composer "your command"         // To run a composer command
./cmd supervisorctl "your command"    // To run a supervisorctl command
./cmd test "your command"             // To run a phpunit command
./cmd mysql "your command"            // To run a mysql command (backup: Backup database, recovery: Restore database)
```

### NGINX OPEN HTTPS
``` 
// .env add
APP_SCHEME=1

// nginx add
proxy_set_header X-Forwarded-Proto $scheme;
```

## Upgrade

**Note: Please back up your data before upgrading!**

```bash
# Method 1: Enter directory and run command
./cmd update

# Or method 2: use this method if method 1 fails
git pull
./cmd mysql backup
./cmd uninstall
./cmd install
./cmd mysql recovery
```

## Uninstall

```bash
# Enter directory and run command
./cmd uninstall
```
