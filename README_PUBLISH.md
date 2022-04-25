# 发布说明

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
