## 修改了

`themes/silver/theme.js`、`themes/silver/theme.min.js`

加上，自定义弹窗z-index

```js
if(typeof window.modalTransferIndex==="number"){dirAttributes.styles={'z-index':(window.modalTransferIndex+100).toString()}}
```
