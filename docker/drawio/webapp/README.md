# Change

## js/diagramly/ElectronApp.js

- 隐藏文件中的无用菜单

## js/app.min.js

- 隐藏帮助菜单
- 取消未保存关闭窗口提示
- `EmbedFile.prototype.getTitle=...` 改 `EmbedFile.prototype.getTitle=function(){return this.desc.title||(urlParams.title?decodeURIComponent(urlParams.title):"")}`
- `c.insertTemplateEnabled&&!c.isOffline()&&this.addMenuItems(b,["insertTemplate"],d)` 改 `c.insertTemplateEnabled&&this.addMenuItems(b,["insertTemplate"],d)`
- `390:270` 改 `390:285`
- `!this.editorUi.isOffline()&&d.length<=c/4?(f=b-Math.ceil((e-c/4)/c),mxUtils.get(ICONSEARCH_PATH` 改 `d.length<=c/4?(f=b-Math.ceil((e-c/4)/c),mxUtils.get(ICONSEARCH_PATH`

## index.html

- 隐藏加载中的提示
- 加入
```js
window.EXPORT_URL = window.location.origin + "/drawio/export/";
window.DRAWIO_LIGHTBOX_URL = window.location.origin + "/drawio/webapp";
setInterval(function() {window.ICONSEARCH_PATH = window.location.origin + "/drawio/iconsearch";}, 1000)
```
