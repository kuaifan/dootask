# 发布说明

## 发布前

1. 添加环境变量 `APPLEID`、`APPLEIDPASS` 用于公证
2. 添加环境变量 `CSC_LINK`、`CSC_KEY_PASSWORD` 用于签名
3. 添加环境变量 `GH_TOKEN`、`GH_REPOSITORY` 用于发布到GitHub
4. 添加环境变量 `DP_KEY` 用于发布到私有服务器

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

1. 执行 `./cmd appbuild [setting]` 编译
2. 进入 `resources/mobile` eeui框架内打包Android或iOS应用
