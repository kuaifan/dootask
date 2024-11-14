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
- 客户端 (Windows、Mac、Android) 会通过 GitHub Actions 自动生成并发布；所以，如果要自动发布只需要提交git并推送即可；
- 如果想手动生成客户端执行 `./cmd electron` 根据提示选择操作。


## 编译 App

```shell
./cmd appbuild publish  # 编译生成App需要的资源
```

编译完后进入 `resources/mobile` EEUI框架目录内打包 Android 或 iOS 应用（Android 以实现 GitHub Actions 自动发布）
