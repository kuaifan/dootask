# Install (Docker)

English | **[中文文档](./README_CN.md)**

- [Screenshot Preview](README_PREVIEW.md)
- [Demo site](http://www.dootask.com/)

## Setup

> `Docker` & `Docker Compose v2.0+` must be installed


### Deployment project

```bash
# 1、Clone the repository

# Clone projects on github
git clone https://github.com/kuaifan/dootask.git
# Or you can use gitee
git clone https://gitee.com/aipaw/dootask.git

# 2、Enter directory
cd dootask

# 3、Installation（Custom port installation: ./cmd install --port 2222）
./cmd install
```

### Reset password

```bash
# Reset default account password
./cmd repassword
```

### Change port

```bash
./cmd port 2222
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
./cmd nginx "your command"            // To run a nginx command
./cmd redis "your command"            // To run a redis command
./cmd composer "your command"         // To run a composer command
./cmd supervisorctl "your command"    // To run a supervisorctl command
./cmd test "your command"             // To run a phpunit command
./cmd mysql "your command"            // To run a mysql command (backup: Backup database, recovery: Restore database)
```

### NGINX PROXY SSL

```bash 
# 1、Nginx config add
proxy_set_header X-Forwarded-Host $http_host;
proxy_set_header X-Forwarded-Proto $scheme;
proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;

# 2、Enter directory and run command
./cmd https
```

## Upgrade

**Note: Please back up your data before upgrading!**

```bash
# Method 1: enter directory and run command
./cmd update

# Or method 2: use this method if method 1 fails
git pull
./cmd mysql backup
./cmd uninstall
./cmd install
./cmd mysql recovery
./cmd artisan migrate
```

## Uninstall

```bash
# Enter directory and run command
./cmd uninstall
```

## Contact us

QQ Group: 546574618
