# 发布

## 准备工作

1. 添加环境变量 `APPLEID`、`APPLEIDPASS` 用于公证
2. 添加环境变量 `CSC_LINK`、`CSC_KEY_PASSWORD` 用于签名
3. 添加环境变量 `GITHUB_TOKEN`、`GITHUB_REPOSITORY` 用于发布到GitHub（GitHub Actions 发布不需要）
4. 添加环境变量 `PUBLISH_KEY` 用于发布到私有服务器

## 发布版本

> 翻译、版本号、更新日志改由 `dootask-release` 技能完成（见 `.claude/skills/dootask-release/`）。

```shell
npm run build       # 编译前端
```

说明：

- 执行 `npm run build` 作用是生成网页端；
- 客户端 (Windows、Mac、Android) 会通过 GitHub Actions 自动生成并发布；所以，如果要自动发布只需要提交git并推送即可；
- 如果想手动生成客户端执行 `./cmd electron` 根据提示选择操作。


## App（Expo，`resources/app` 子模块）

- **Android**：推送到 `pro` 时随 Publish 工作流自动构建（`.github/workflows/app-build.yml`），以正式签名出包，上传官网下载（R2）并附到 GitHub Release；App 内「检查更新」读取官网下载索引。
- **iOS**：手动运行 **iOS Publish** 工作流（`gh workflow run ios-publish.yml --ref pro`），构建后上传 App Store Connect，再到 App Store Connect 提交审核。
- **内测包**：手动运行 **App Internal** 工作流，只产出 artifact。
- 本地出包见 `resources/app/scripts/mac-build.sh`（Android 正式签名读取构建机 iCloud 云盘 `DooTaskKeys/android/`）。

| Secret | 用途 |
|---|---|
| `ANDROID_KEYSTORE_BASE64` / `ANDROID_KEYSTORE_PASSWORD` / `ANDROID_KEY_ALIAS` / `ANDROID_KEY_PASSWORD` | Android 正式签名（与旧版证书一致，CI 校验指纹） |
| `IOS_CERTIFICATE_BASE64` / `IOS_CERTIFICATE_PASSWORD` | Apple Distribution 证书 |
| `IOS_PROVISION_PROFILE_BASE64` / `IOS_SHARE_EXTENSION_PROVISION_PROFILE_BASE64` | App Store profiles（主 App 需含推送与 App Group `group.im.dootask`） |
| `ASC_API_KEY_ID` / `ASC_ISSUER_ID` / `ASC_API_KEY_P8_BASE64` | App Store Connect API（构建号查询与上传） |
| `IOS_INTERNAL_PROVISION_PROFILE_BASE64` / `IOS_INTERNAL_SHARE_EXTENSION_PROVISION_PROFILE_BASE64` | 可选，iOS 内测 profiles |
