# 发布说明

## 发布前

1. 添加环境变量 `APPLEID`、`APPLEIDPASS`、`CSC_LINK`
2. 发布GitHub还需要添加 `GH_PAT`

## 通过 GitHub Actions 发布

1. 执行 `./cmd prod` 编译
2. 执行 `node ./version.js` 制作版本
3. 执行 `git commit` 相关操作
4. 制作标签
5. 推送标签

## 本地发布

1. 执行 `./cmd prod` 编译
2. 执行 `node ./version.js` 制作版本
3. 执行 `git commit` 相关操作
4. 制作标签
5. 执行 `./cmd electron` 相关操作


## 编译App

1. 执行 `./cmd appbuild` 编译
2. 进入 `resources/mobile` eeui框架内打包Android或iOS应用
