# 语言翻译工具说明

`language/translate.php` 脚本用于根据 `original-web.txt` 和 `original-api.txt` 中的内容，自动生成/更新 `translate.json` 以及前端使用的多语言文件。

## 使用步骤

1. 在项目根目录 `.env` 文件中配置：

   ```dotenv
   OPENAI_API_KEY=你的OpenAI密钥
   OPENAI_PROXY_URL=可选的代理地址
   ```

2. 在 `language` 目录下执行：

   ```bash
   php translate.php
   ```

3. 查看生成的翻译结果：

   - 翻译详情：`language/translate.json`
   - API 文件：`public/language/api/*.json`
   - Web 文件：`public/language/web/*.js`

## 注意事项

- 若 `.env` 未设置 `OPENAI_API_KEY`，脚本会直接退出。
- `OPENAI_PROXY_URL` 可选，留空时不会设置代理。
