# 发布说明

## 发布前

1. 添加环境变量 `APPLEID`、`APPLEIDPASS`、`CSC_LINK`
2. 发布GitHub还需要添加 `GH_TOKEN`
3. 发布私有服务器还需要添加 `DP_KEY`

## 通过 GitHub Actions 发布

1. 执行 `npm run version` 生成版本
2. 执行 `npm run build` 编译前端
3. 执行 `git commit` 提交并推送
4. 添加并推送标签

## 本地发布

1. 执行 `npm run version` 生成版本
2. 执行 `npm run build` 编译前端
3. 执行 `./cmd electron` 相关操作

## 编译App

1. 执行 `./cmd appbuild` 或 `./cmd appbuild setting` 编译
2. 进入 `resources/mobile` eeui框架内打包Android或iOS应用
