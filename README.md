# Install (Docker)

English | **[中文文档](./README_CN.md)**

- [Screenshot Preview](README_PREVIEW.md)
- [Demo site](http://www.dootask.com/)

## Setup

> `Docker` & `Docker Compose` must be installed


### Deployment project

```bash
# 1、Clone the repository

# using ssh
git clone git@github.com:kuaifan/dootask.git
# or you can use https
git clone https://github.com/kuaifan/dootask.git

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
./cmd npm "your command"              // To run a npm command
./cmd yarn "your command"             // To run a yarn command
./cmd mysql "your command"            // To run a mysql command (use `./cmd mysql bak` Backup database)
```

## Upgrade

**Note: Please back up your data before upgrading!**

```bash
# Enter directory and run command
./cmd update
```

## Uninstall

```bash
# Enter directory and run command
./cmd uninstall
```
