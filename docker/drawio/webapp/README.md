# Change

## js/diagramly/ElectronApp.js

- 隐藏文件中的无用菜单

## js/app.min.js

- 隐藏帮助菜单
- 取消未保存关闭窗口提示
- `EmbedFile.prototype.getTitle=...` 改为 `EmbedFile.prototype.getTitle=function(){return this.desc.title||(urlParams.title?decodeURIComponent(urlParams.title):"")}`
- `390:270` 改为 `390:285`

## index.html

- 隐藏加载中的提示
