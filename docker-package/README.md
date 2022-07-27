1. 修改`.env.example`配置文件
启动前请修改`.env.example`配置，不然之后有些配置需要修改docker环境变量重启容器之后才能生效

如果需要启用ldap认证请修改ldap相关配置，并且暂时未集成到默认登录页面，需要手动在地址栏中输入地址`/ldap/login`

2. 启动服务
```shell
# 初始化脚本
sudo ./init.sh
```

3. 修改环境变量
第一次执行`./init.sh`会复制`.env.example`生成`.env`文件，之后需要修改`.env`然后执行`docker-compose up -d`重启生效
