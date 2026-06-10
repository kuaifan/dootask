---
id: common-faq.deploy-ssl.faq
title: HTTPS / SSL 证书怎么配
type: faq
feature: common-faq
scope: admin
locale: zh
aliases:
  - 配 SSL
  - 装 HTTPS
  - 证书过期
  - 证书续期
  - acme.sh
  - 自签证书
  - https 配置失败
  - 浏览器不安全提示
related_tools: []
related_pages: []
prerequisites:
  - 域名已解析到部署机
  - 80 / 443 端口对外开放
negative:
  - bin/https 脚本只支持 acme.sh 申请 Let's Encrypt 免费证书
  - 不支持上传自定义商业证书（要自己改 nginx 配置）
  - 自签证书浏览器会报「不安全」，仅适合内网测试
last_verified: v1.7.90
---

# HTTPS / SSL 证书怎么配

## 问题
- 默认 http://，想升级 https://
- 证书过期要续期
- 配完 https 浏览器还提示不安全

## 自带 HTTPS 工具
`bin/https` 用 acme.sh + Let's Encrypt 免费签：

```bash
sudo ./cmd https install <域名>
```

会自动：拉临时 nginx 走 acme.sh 验证 → 证书放 `docker/nginx/site/ssl/` → 生成 `ssl.conf` → 改 `.env` 的 `APP_URL` 为 https → 重启 nginx。

## 续期
acme.sh 自动续期，也可手动 `sudo ./cmd https renew <域名>`。证书 90 天有效。

## 配置失败常见原因
- **80 端口被占**：acme.sh 要 80 端口验证 → 停占用进程
- **域名未解析**：先 `dig <域名>` 确认指向当前机器
- **防火墙没开 443**：云服务器开安全组
- **APP_URL 没改**：检查 `.env`

## 上传商业证书
脚本不支持直接导入，手动：
1. 把 crt + key 放 `docker/nginx/site/ssl/<域名>.{crt,key}`
2. 复制 `bin/https` 中 ssl.conf 模板到 `docker/nginx/site/ssl.conf`
3. 改 `.env` 的 `APP_URL=https://<域名>`
4. `./cmd restart nginx`

## 仍提示不安全
- 证书 / 私钥配错（检查中间证书完整性）
- `APP_URL` 与浏览器访问域名不一致
- 清浏览器缓存 / 无痕模式
- WebSocket 没升级到 wss → [[common-faq.sync-websocket-disconnect.faq]]

## 不支持
- 不支持一个实例跑多域名
- 不支持泛域名证书自动签（要手 acme.sh 走 DNS-01）
