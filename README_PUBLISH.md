# 发布

## 准备工作

1. 添加环境变量 `APPLEID`、`APPLEIDPASS` 用于公证
2. 添加环境变量 `CSC_LINK`、`CSC_KEY_PASSWORD` 用于签名
3. 添加环境变量 `GITHUB_TOKEN`、`GITHUB_REPOSITORY` 用于发布到GitHub（GitHub Actions 发布不需要）
4. 添加环境变量 `PUBLISH_KEY` 用于发布到私有服务器

## 发布版本

```shell
npm run translate   # 翻译（可选）
npm run version     # 生成版本
npm run build       # 编译前端
```

说明：

- 执行 `npm run build` 作用是生成网页端；
- 桌面客户端（Windows、Mac）会通过 GitHub Actions 自动生成并发布；所以，如果要自动发布只需要提交 git 并推送即可；
- 如果想手动生成桌面客户端执行 `./cmd electron` 根据提示选择操作。

## 编译移动端 App

移动端（iOS / Android）已迁移到独立仓库 [kuaifan/dootask-app](https://github.com/kuaifan/dootask-app)
（Expo + EAS Build）。构建流程：

```shell
# 1. 本仓库：构建前端资源
./cmd appbuild

# 2. 拷贝到 dootask-app 仓库
cp -r public/* ~/workspaces/dootask-app/assets/web/

# 3. dootask-app 仓库：EAS Build 本地或 CI 触发
cd ~/workspaces/dootask-app
npx eas build --platform android --profile preview
# 或在 dootask-app 的 GitHub Actions 里手动触发 "EAS Build" workflow
```

详见 dootask-app 仓库的 README。
